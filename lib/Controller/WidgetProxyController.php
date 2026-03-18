<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\ServiceService;
use OCA\LinkBoard\Service\ResourceService;
use OCA\LinkBoard\Service\NotFoundException;
use OCA\LinkBoard\Widget\WebSocketJsonRpcClient;
use OCA\LinkBoard\Widget\WidgetRegistry;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Proxy controller that fetches widget data from external services.
 * Credentials stay server-side — the frontend only sees mapped values.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
class WidgetProxyController extends ApiController {

    public function __construct(
        IRequest $request,
        private WidgetRegistry $widgetRegistry,
        private ServiceService $serviceService,
        private ResourceService $resourceService,
        private LoggerInterface $logger,
        private IL10N $l10n,
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
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
    public function getAllData(): DataResponse {
        $services = $this->serviceService->findAll($this->userId);
        $result = [];

        foreach ($services as $service) {
            $widgetType = $service->getWidgetType();
            if (empty($widgetType)) continue;

            $widget = $this->widgetRegistry->get($widgetType);
            if (!$widget) continue;

            $baseUrl = $service->getHref();
            if (empty($baseUrl) && !$widget->isLocal()) continue;

            $configRaw = $service->getWidgetConfig();
            $config = $configRaw ? json_decode($configRaw, true) : [];
            if (!is_array($config)) $config = [];

            try {
                $data = $this->fetchWidgetData($widget, $baseUrl, $config);
                $result[$service->getId()] = [
                    'data' => $data,
                    'fieldLabels' => $widget->getFieldLabelsForConfig($config),
                ];
            } catch (\Throwable $e) {
                $this->logger->warning('LinkBoard: Widget fetch failed for service ' . $service->getId(), [
                    'exception' => $e,
                ]);
                $result[$service->getId()] = [
                    'error' => $this->l10n->t('Widget data fetch failed: %s', [$e->getMessage()]),
                ];
            }
        }

        return new DataResponse($result);
    }

    /**
     * GET /api/v1/widgets/{serviceId}/data
     * Fetch widget data for a single service.
     */
    #[NoAdminRequired]
    public function getData(int $serviceId): DataResponse {
        try {
            $service = $this->serviceService->find($serviceId, $this->userId);
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

        $configRaw = $service->getWidgetConfig();
        $config = $configRaw ? json_decode($configRaw, true) : [];
        if (!is_array($config)) $config = [];

        try {
            $data = $this->fetchWidgetData($widget, $baseUrl, $config);
            return new DataResponse([
                'data' => $data,
                'fieldLabels' => $widget->getFieldLabelsForConfig($config),
            ]);
        } catch (\Throwable $e) {
            $this->logger->warning('LinkBoard: Widget fetch failed for service ' . $serviceId, [
                'exception' => $e,
            ]);
            return new DataResponse(
                ['error' => $this->l10n->t('Widget data fetch failed: %s', [$e->getMessage()])],
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
    private function fetchWidgetData(\OCA\LinkBoard\Widget\AbstractWidget $widget, ?string $baseUrl, array $config): array {
        if ($widget->isLocal()) {
            $data = $this->getLocalWidgetData($widget, $config);
            return $widget->mapResponse([$data], $config);
        }

        $requestSpecs = $widget->buildRequests($baseUrl, $config);
        $responses = [];
        $authToken = null;
        $cookieJar = null;

        foreach ($requestSpecs as $spec) {
            // Handle WebSocket JSON-RPC requests
            if (!empty($spec['_websocket_jsonrpc'])) {
                $wsClient = new WebSocketJsonRpcClient();
                $results = $wsClient->execute(
                    $spec['url'],
                    $spec['auth'] ?? null,
                    $spec['calls'] ?? [],
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

        // Clean up cookie jar
        if ($cookieJar && file_exists($cookieJar)) {
            @unlink($cookieJar);
        }

        return $widget->mapResponse($responses, $config);
    }

    /**
     * Execute a Transmission RPC request with CSRF retry.
     * On 409, extracts X-Transmission-Session-Id and retries.
     */
    private function executeTransmissionRequest(array $spec): mixed {
        $ch = curl_init();
        $this->applyCurlOptions($ch, $spec);

        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$responseHeaders) {
            if (stripos($header, 'X-Transmission-Session-Id:') === 0) {
                $responseHeaders['session_id'] = trim(explode(':', $header, 2)[1]);
            }
            return strlen($header);
        });

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        // 409 means we need the session ID — retry with it
        if ($httpCode === 409 && !empty($responseHeaders['session_id'])) {
            $spec['headers'][] = 'X-Transmission-Session-Id: ' . $responseHeaders['session_id'];
            return $this->executeRequest($spec);
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException('HTTP ' . $httpCode . ' from ' . $spec['url']);
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
        $this->applyCurlOptions($ch, $spec, $cookieJar);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException('HTTP ' . $httpCode . ' from ' . $spec['url']);
        }

        $decoded = json_decode((string)$body, true);
        return $decoded ?? [];
    }

    /**
     * Fetch data for a local widget (no HTTP requests).
     */
    private function getLocalWidgetData(\OCA\LinkBoard\Widget\AbstractWidget $widget, array $config): array {
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

    /**
     * Apply common cURL options to a handle.
     */
    private function applyCurlOptions(\CurlHandle $ch, array $spec, ?string $cookieJar = null): void {
        $method = strtoupper($spec['method'] ?? 'GET');
        $url = $spec['url'];
        $headers = $spec['headers'] ?? [];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'LinkBoard/1.0 WidgetProxy',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_UNRESTRICTED_AUTH => true,
        ]);

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
    }
}
