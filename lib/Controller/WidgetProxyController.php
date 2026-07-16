<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Db\Service;
use OCA\LinkBoard\Service\BulkOperationGuard;
use OCA\LinkBoard\Service\BulkOperationInProgressException;
use OCA\LinkBoard\Service\GlobalBoardService;
use OCA\LinkBoard\Service\ServiceService;
use OCA\LinkBoard\Service\ResourceService;
use OCA\LinkBoard\Service\NotFoundException;
use OCA\LinkBoard\Service\OutboundRequestGuard;
use OCA\LinkBoard\Widget\WebSocketJsonRpcClient;
use OCA\LinkBoard\Widget\WidgetRegistry;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * Proxy controller that fetches widget data from external services.
 * These endpoints return mapped response values without exposing request details.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
class WidgetProxyController extends ApiController {

    private const BATCH_PAGE_SIZE = 20;
    private const BATCH_TIME_BUDGET_SECONDS = 25;
    private const WIDGET_TIME_BUDGET_SECONDS = 20;
    private const MAX_REQUESTS_PER_WIDGET = 12;
    private const CACHE_TTL_SECONDS = 15;

    private ICache $widgetCache;

    public function __construct(
        IRequest $request,
        private WidgetRegistry $widgetRegistry,
        private ServiceService $serviceService,
        private ResourceService $resourceService,
        private LoggerInterface $logger,
        private IL10N $l10n,
        private GlobalBoardService $globalBoardService,
        private ?string $userId,
        private IAppConfig $appConfig,
        private OutboundRequestGuard $requestGuard,
        private BulkOperationGuard $operationGuard,
        ICacheFactory $cacheFactory,
    ) {
        parent::__construct(Application::APP_ID, $request);
        $this->widgetCache = $cacheFactory->createDistributed('linkboard_widget_data_');
    }

    private function effectiveUserId(): string {
        return $this->globalBoardService->resolve($this->userId)['sourceUserId'];
    }

    /**
     * GET /api/v1/widgets/catalog
     * Returns widget definitions for the editor UI.
     */
    #[NoAdminRequired]
    public function catalog(): DataResponse {
        return new DataResponse($this->widgetRegistry->getCatalog());
    }

    /**
     * GET /api/v1/widgets/data
     * Batch-fetch widget data for all services that have a widget configured.
     */
    #[NoAdminRequired]
    #[UserRateLimit(limit: 60, period: 300)]
    public function getAllData(int $offset = 0, int $limit = self::BATCH_PAGE_SIZE): DataResponse {
        $effectiveUserId = $this->effectiveUserId();
        $services = $this->getEligibleWidgetServices($effectiveUserId);
        $total = count($services);
        $offset = max(0, min($offset, $total));
        $limit = max(1, min($limit, self::BATCH_PAGE_SIZE));
        $page = array_slice($services, $offset, $limit);

        try {
            [$result, $processed] = $this->operationGuard->run(
                'widget-batch', $effectiveUserId, 60,
                fn(): array => $this->fetchWidgetPage($page, $effectiveUserId),
            );
        } catch (BulkOperationInProgressException) {
            return new DataResponse([], Http::STATUS_TOO_MANY_REQUESTS);
        }

        $headers = [];
        $nextOffset = $offset + $processed;
        if ($nextOffset < $total) {
            $headers['X-LinkBoard-Widget-Next-Offset'] = (string)$nextOffset;
        }

        return new DataResponse($result, Http::STATUS_OK, $headers);
    }

    /** @return Service[] */
    private function getEligibleWidgetServices(string $effectiveUserId): array {
        return array_values(array_filter(
            $this->serviceService->findAll($effectiveUserId),
            function (Service $service): bool {
                $widgetType = $service->getWidgetType();
                if (empty($widgetType)) {
                    return false;
                }

                $widget = $this->widgetRegistry->get($widgetType);
                return $widget !== null && (!empty($service->getHref()) || $widget->isLocal());
            },
        ));
    }

    /**
     * @param Service[] $services
     * @return array{0: array<int, array>, 1: int}
     */
    private function fetchWidgetPage(array $services, string $effectiveUserId): array {
        $result = [];
        $processed = 0;
        $deadline = microtime(true) + self::BATCH_TIME_BUDGET_SECONDS;

        foreach ($services as $service) {
            if ($processed > 0 && microtime(true) >= $deadline) {
                break;
            }

            try {
                $result[$service->getId()] = $this->getWidgetPayload(
                    $service,
                    $effectiveUserId,
                    min($deadline, microtime(true) + self::WIDGET_TIME_BUDGET_SECONDS),
                );
            } catch (\Throwable $e) {
                $this->logger->warning('LinkBoard: Widget fetch failed for service ' . $service->getId(), [
                    'exceptionClass' => $e::class,
                    'exceptionCode' => $e->getCode(),
                ]);
                $result[$service->getId()] = [
                    'error' => $this->l10n->t('Widget data fetch failed'),
                ];
            }
            $processed++;
        }

        return [$result, $processed];
    }

    private function getWidgetPayload(Service $service, string $effectiveUserId, float $deadline): array {
        $widgetType = $service->getWidgetType();
        if (empty($widgetType)) {
            throw new \RuntimeException('No widget configured');
        }

        $widget = $this->widgetRegistry->get($widgetType);
        if ($widget === null) {
            throw new \RuntimeException('Unknown widget type');
        }

        $baseUrl = $service->getHref();
        if (empty($baseUrl) && !$widget->isLocal()) {
            throw new \RuntimeException('Service has no URL configured');
        }

        $configRaw = $service->getWidgetConfig();
        $cacheKey = hash('sha256', implode("\0", [
            $effectiveUserId,
            (string)$service->getId(),
            (string)$service->getUpdatedAt(),
            $widgetType,
            (string)$baseUrl,
            (string)$configRaw,
            $service->getIgnoreTls() ? '1' : '0',
        ]));
        $cached = $this->getCachedWidgetPayload($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        return $this->operationGuard->run(
            'widget-service',
            (string)$service->getId(),
            45,
            function () use ($cacheKey, $configRaw, $widget, $baseUrl, $service, $deadline): array {
                $cached = $this->getCachedWidgetPayload($cacheKey);
                if ($cached !== null) {
                    return $cached;
                }

                $config = $configRaw ? json_decode($configRaw, true) : [];
                if (!is_array($config)) {
                    $config = [];
                }

                $data = $this->fetchWidgetData(
                    $widget,
                    $baseUrl,
                    $config,
                    $this->shouldVerifyTls($service->getIgnoreTls()),
                    $deadline,
                );
                $payload = [
                    'data' => $data,
                    'fieldLabels' => $widget->getFieldLabelsForConfig($config),
                ];
                $this->widgetCache->set($cacheKey, ['payload' => $payload], self::CACHE_TTL_SECONDS);

                return $payload;
            },
        );
    }

    private function getCachedWidgetPayload(string $cacheKey): ?array {
        $cached = $this->widgetCache->get($cacheKey);
        if (!is_array($cached) || !isset($cached['payload']) || !is_array($cached['payload'])) {
            return null;
        }

        return $cached['payload'];
    }

    /**
     * GET /api/v1/widgets/{serviceId}/data
     * Fetch widget data for a single service.
     */
    #[NoAdminRequired]
    #[UserRateLimit(limit: 30, period: 60)]
    public function getData(int $serviceId): DataResponse {
        $effectiveUserId = $this->effectiveUserId();
        try {
            $service = $this->serviceService->find($serviceId, $effectiveUserId);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $this->l10n->t('Service not found')], Http::STATUS_NOT_FOUND);
        }

        $widgetType = $service->getWidgetType();
        if (empty($widgetType)) {
            return new DataResponse(['error' => $this->l10n->t('No widget configured')], Http::STATUS_BAD_REQUEST);
        }

        $widget = $this->widgetRegistry->get($widgetType);
        if (!$widget) {
            return new DataResponse(['error' => $this->l10n->t('Unknown widget type: %s', [$widgetType])], Http::STATUS_BAD_REQUEST);
        }

        $baseUrl = $service->getHref();
        if (empty($baseUrl) && !$widget->isLocal()) {
            return new DataResponse(['error' => $this->l10n->t('Service has no URL configured')], Http::STATUS_BAD_REQUEST);
        }

        try {
            $payload = $this->getWidgetPayload(
                $service,
                $effectiveUserId,
                microtime(true) + self::WIDGET_TIME_BUDGET_SECONDS,
            );
            return new DataResponse($payload);
        } catch (BulkOperationInProgressException) {
            return new DataResponse([], Http::STATUS_TOO_MANY_REQUESTS);
        } catch (\Throwable $e) {
            $this->logger->warning('LinkBoard: Widget fetch failed for service ' . $serviceId, [
                'exceptionClass' => $e::class,
                'exceptionCode' => $e->getCode(),
            ]);
            return new DataResponse(
                ['error' => $this->l10n->t('Widget data fetch failed')],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Execute HTTP requests defined by a widget and map responses.
     *
     * Supports multiple auth patterns via request spec flags:
     * - _npm_login / _npm_needs_token: NPM two-step token auth
     * - _session_login / _session_needs_cookie: Cookie-jar session auth (qBittorrent, Deluge, etc.)
     * - _*_login / _*_needs_token: Generic token auth (Kavita, PhotoPrism, Flood, etc.)
     * - _transmission_rpc: Transmission CSRF retry (409 → X-Transmission-Session-Id)
     */
    private function fetchWidgetData(\OCA\LinkBoard\Widget\AbstractWidget $widget, ?string $baseUrl, array $config, bool $verifyTls, float $deadline): array {
        if ($widget->isLocal()) {
            $data = $this->getLocalWidgetData($widget, $config);
            return $widget->mapResponse([$data], $config);
        }

        $requestSpecs = $widget->buildRequests($baseUrl, $config);
        $requestCount = 0;
        $responses = [];
        $authToken = null;
        $cookieJar = null;

        try {
            foreach ($requestSpecs as $spec) {
                $this->assertWidgetRequestBudget($requestCount, $deadline);
                $timeoutSeconds = $this->remainingWidgetTimeout($deadline);
                $spec['_timeout_seconds'] = $timeoutSeconds;
                $spec['_verify_tls'] = $verifyTls;
                // Handle WebSocket JSON-RPC requests
                if (!empty($spec["_websocket_jsonrpc"])) {
                    $wsClient = new WebSocketJsonRpcClient($this->requestGuard);
                    $results = $wsClient->execute(
                        $spec['url'],
                        $spec['auth'] ?? null,
                        $spec['calls'] ?? [],
                        $timeoutSeconds,
                        $verifyTls,
                    );
                    foreach ($results as $r) {
                        $responses[] = $r;
                    }
                    continue;
                }

                // Inject auth token for any *_needs_token flag
                if ($authToken) {
                    foreach ($spec as $key => $val) {
                        if (str_starts_with($key, '_') && str_ends_with($key, '_needs_token') && $val) {
                            $spec['headers'][] = 'Authorization: Bearer ' . $authToken;
                            break;
                        }
                    }
                }

                // Use cookie jar for session-based auth
                $useSession = !empty($spec['_session_login']) || !empty($spec['_session_needs_cookie']);
                if ($useSession && $cookieJar === null) {
                    $cookieJar = tempnam(sys_get_temp_dir(), 'lb_cookie_');
                    if ($cookieJar === false) {
                        throw new \RuntimeException('Unable to create temporary widget session storage');
                    }
                    @chmod($cookieJar, 0600);
                }

                // Handle Transmission CSRF retry
                if (!empty($spec['_transmission_rpc'])) {
                    $response = $this->executeTransmissionRequest($spec);
                    $responses[] = $response;
                    continue;
                }

                $response = $this->executeRequest($spec, $useSession ? $cookieJar : null);

                // Extract token from any *_login response
                foreach ($spec as $key => $val) {
                    if (str_starts_with($key, '_') && str_ends_with($key, '_login') && $val) {
                        $token = $response['token'] ?? $response['result'] ?? null;
                        if (is_string($token) && $token !== '') {
                            $authToken = $token;
                        }
                        break;
                    }
                }

                $responses[] = $response;
            }

            // Execute follow-up requests (two-stage widgets)
            $followUpSpecs = $widget->buildFollowUpRequests($responses, $baseUrl, $config);
            foreach ($followUpSpecs as $spec) {
                $this->assertWidgetRequestBudget($requestCount, $deadline);
                $spec['_timeout_seconds'] = $this->remainingWidgetTimeout($deadline);
                $spec['_verify_tls'] = $verifyTls;
                $responses[] = $this->executeRequest($spec, null);
            }

            return $widget->mapResponse($responses, $config);
        } finally {
            if (is_string($cookieJar)) {
                @unlink($cookieJar);
            }
        }
    }

    private function assertWidgetRequestBudget(int &$requestCount, float $deadline): void {
        if ($requestCount >= self::MAX_REQUESTS_PER_WIDGET || microtime(true) >= $deadline) {
            throw new \RuntimeException('Widget request budget exceeded');
        }
        $requestCount++;
    }

    private function remainingWidgetTimeout(float $deadline): int {
        $remaining = (int)ceil($deadline - microtime(true));
        if ($remaining <= 0) {
            throw new \RuntimeException('Widget request budget exceeded');
        }

        return min(15, $remaining);
    }

    /**
     * Execute a Transmission RPC request with CSRF retry.
     * On 409, extracts X-Transmission-Session-Id and retries.
     */
    private function executeTransmissionRequest(array $spec): mixed {
        $ch = curl_init();
        try {
            $target = $this->applyCurlOptions($ch, $spec);

            $responseHeaders = [];
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$responseHeaders) {
                if (stripos($header, 'X-Transmission-Session-Id:') === 0) {
                    $responseHeaders['session_id'] = trim(explode(':', $header, 2)[1]);
                }
                return strlen($header);
            });

            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->requestGuard->assertCurlConnection($ch, $target['addresses']);
            $errorCode = curl_errno($ch);
        } finally {
            curl_close($ch);
        }

        if ($errorCode !== CURLE_OK) {
            throw new \RuntimeException('Widget HTTP request failed with cURL error ' . $errorCode);
        }

        // 409 means we need the session ID — retry with it
        if ($httpCode === 409 && !empty($responseHeaders['session_id'])) {
            $spec['headers'][] = 'X-Transmission-Session-Id: ' . $responseHeaders['session_id'];
            return $this->executeRequest($spec);
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException('Widget HTTP request returned status ' . $httpCode);
        }

        return json_decode((string)$body, true) ?? [];
    }

    /**
     * Execute a single HTTP request via cURL.
     *
     * @param string|null $cookieJar  Path to cookie jar file for session auth
     */
    private function executeRequest(array $spec, ?string $cookieJar = null): mixed {
        $ch = curl_init();
        try {
            $target = $this->applyCurlOptions($ch, $spec, $cookieJar);

            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->requestGuard->assertCurlConnection($ch, $target['addresses']);
            $errorCode = curl_errno($ch);
        } finally {
            curl_close($ch);
        }

        if ($errorCode !== CURLE_OK) {
            throw new \RuntimeException('Widget HTTP request failed with cURL error ' . $errorCode);
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException('Widget HTTP request returned status ' . $httpCode);
        }

        $decoded = json_decode((string)$body, true);
        return $decoded ?? [];
    }

    /**
     * Fetch data for a local widget (no HTTP requests).
     */
    private function getLocalWidgetData(\OCA\LinkBoard\Widget\AbstractWidget $widget, array $config): array {
        if ($widget->getId() === 'resources') {
            $diskPathsRaw = $config['diskPaths'] ?? '/';
            $diskPaths = array_filter(array_map('trim', explode(',', (string)$diskPathsRaw)));
            if (empty($diskPaths)) {
                $diskPaths = ['/'];
            }
            $tempUnit = $config['tempUnit'] ?? 'C';

            return $this->resourceService->getResources([
                'showCpu' => true,
                'showMemory' => true,
                'showUptime' => true,
                'showCpuTemp' => true,
                'tempUnit' => $tempUnit,
                'diskPaths' => $diskPaths,
            ]);
        }

        // For table and other local widgets: pass config through as data
        return $config;
    }

    /**
     * Apply common cURL options to a handle.
     */
    private function shouldVerifyTls(bool $ignoreTls): bool {
        return $this->appConfig->getValueBool(Application::APP_ID, 'tls_verification_enabled', true) || !$ignoreTls;
    }

    /**
     * @return array{host: string, port: int, addresses: list<string>}
     */
    private function applyCurlOptions(\CurlHandle $ch, array $spec, ?string $cookieJar = null): array {
        $method = strtoupper($spec['method'] ?? 'GET');
        $url = $spec['url'];
        $headers = $spec['headers'] ?? [];
        $verifyTls = $spec['_verify_tls'] ?? true;
        $timeoutSeconds = max(1, min(15, (int)($spec['_timeout_seconds'] ?? 15)));

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeoutSeconds),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_MAXFILESIZE => OutboundRequestGuard::MAX_RESPONSE_BYTES,
            CURLOPT_SSL_VERIFYPEER => $verifyTls,
            CURLOPT_SSL_VERIFYHOST => $verifyTls ? 2 : 0,
            CURLOPT_USERAGENT => 'LinkBoard/1.0 WidgetProxy',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $target = $this->requestGuard->pinCurl($ch, $url);

        if ($cookieJar) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (isset($spec['body'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $spec['body']);
            }
        }

        return $target;
    }
}
