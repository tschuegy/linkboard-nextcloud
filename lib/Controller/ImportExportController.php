<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\ImportExportService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ImportExportController extends ApiController {

    public function __construct(
        IRequest $request,
        private ImportExportService $importExportService,
        private LoggerInterface $logger,
        private IL10N $l10n,
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function exportJson(): DataDownloadResponse {
        $data = $this->importExportService->export($this->userId);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return new DataDownloadResponse($json, 'linkboard-export.json', 'application/json');
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function exportYaml(): DataDownloadResponse {
        $data = $this->importExportService->export($this->userId);
        $yaml = $this->importExportService->toYaml($data);
        return new DataDownloadResponse($yaml, 'linkboard-export.yaml', 'text/yaml');
    }

    #[NoAdminRequired]
    public function importJson(): DataResponse {
        // Debug: log everything we can see
        $allParams = $this->request->getParams();
        $paramKeys = array_keys($allParams);
        $this->logger->info('LinkBoard import: getParams keys=[' . implode(', ', $paramKeys) . ']');

        // Try multiple ways to get the payload
        $payload = $this->request->getParam('payload', '');
        $mode = $this->request->getParam('mode', 'replace');

        $this->logger->info('LinkBoard import: getParam(payload) length=' . strlen($payload) . ', mode=' . $mode);

        // If payload is empty, maybe the whole body IS the data (not wrapped)
        if (empty($payload)) {
            // Try getting 'categories' directly - maybe NC flattened the nested JSON
            $categories = $this->request->getParam('categories');
            if (!empty($categories)) {
                $this->logger->info('LinkBoard import: found categories param directly, type=' . gettype($categories));
                $data = $allParams;
                // Remove NC internal params
                unset($data['_route'], $data['_method']);
                return $this->doImport($data, $mode);
            }

            // Last resort: check if data was sent as 'data' param
            $dataParam = $this->request->getParam('data');
            if (!empty($dataParam)) {
                $this->logger->info('LinkBoard import: found data param, type=' . gettype($dataParam));
                if (is_string($dataParam)) {
                    $data = json_decode($dataParam, true);
                } else {
                    $data = $dataParam;
                }
                if (is_array($data)) {
                    return $this->doImport($data, $mode);
                }
            }

            // Log what we actually have for debugging
            foreach ($paramKeys as $key) {
                $val = $allParams[$key];
                $type = gettype($val);
                $preview = is_string($val) ? substr($val, 0, 100) : json_encode($val);
                $this->logger->info("LinkBoard import param: {$key} ({$type}) = {$preview}");
            }

            return new DataResponse([
                'error' => $this->l10n->t('No import data found.'),
            ], Http::STATUS_BAD_REQUEST);
        }

        // Payload is a JSON string - decode it
        $data = json_decode($payload, true);
        if (!is_array($data)) {
            return new DataResponse([
                'error' => $this->l10n->t('Invalid JSON in payload: %s', [json_last_error_msg()]),
            ], Http::STATUS_BAD_REQUEST);
        }

        return $this->doImport($data, $mode);
    }

    #[NoAdminRequired]
    public function importYaml(): DataResponse {
        $payload = $this->request->getParam('payload', '');
        $mode = $this->request->getParam('mode', 'replace');

        if (empty($payload)) {
            $dataParam = $this->request->getParam('data');
            if (!empty($dataParam)) {
                $data = is_string($dataParam) ? json_decode($dataParam, true) : $dataParam;
                if (is_array($data)) return $this->doImport($data, $mode);
            }
            return new DataResponse(['error' => $this->l10n->t('No import data found.')], Http::STATUS_BAD_REQUEST);
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            return new DataResponse(['error' => $this->l10n->t('Invalid JSON')], Http::STATUS_BAD_REQUEST);
        }

        return $this->doImport($data, $mode);
    }

    private function doImport(array $data, string $mode): DataResponse {
        $this->logger->info('LinkBoard import: starting import, keys=' . implode(',', array_keys($data)) . ', mode=' . $mode);

        try {
            $stats = $this->importExportService->import($this->userId, $data, $mode);
            return new DataResponse([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('LinkBoard import failed: ' . $e->getMessage(), ['exception' => $e]);
            return new DataResponse([
                'error' => $this->l10n->t('Import failed: %s', [$e->getMessage()]),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
