<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException as FilesNotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IL10N;
use OCP\IRequest;

class IconApiController extends ApiController {

    private const ALLOWED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/svg+xml',
        'image/webp',
        'image/gif',
        'image/x-icon',
    ];

    private const MAX_FILE_SIZE = 512 * 1024; // 512 KB

    public function __construct(
        IRequest $request,
        private IAppData $appData,
        private IL10N $l10n,
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function index(): DataResponse {
        try {
            $folder = $this->getUserIconFolder();
            $files = $folder->getDirectoryListing();

            $icons = [];
            foreach ($files as $file) {
                $icons[] = [
                    'name' => $file->getName(),
                    'size' => $file->getSize(),
                ];
            }

            return new DataResponse($icons);
        } catch (FilesNotFoundException) {
            return new DataResponse([]);
        }
    }

    #[NoAdminRequired]
    public function upload(): DataResponse {
        $file = $this->request->getUploadedFile('icon');

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            return new DataResponse(
                ['error' => $this->l10n->t('No file uploaded or upload error')],
                Http::STATUS_BAD_REQUEST
            );
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return new DataResponse(
                ['error' => $this->l10n->t('File too large (max 512 KB)')],
                Http::STATUS_BAD_REQUEST
            );
        }

        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return new DataResponse(
                ['error' => $this->l10n->t('Invalid file type. Allowed: PNG, JPEG, SVG, WebP, GIF, ICO')],
                Http::STATUS_BAD_REQUEST
            );
        }

        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        if (empty($filename)) {
            $filename = 'icon_' . time() . '.png';
        }

        try {
            $folder = $this->getUserIconFolder(true);
            $newFile = $folder->newFile($filename);
            $newFile->putContent(file_get_contents($file['tmp_name']));

            return new DataResponse([
                'name' => $filename,
                'size' => $file['size'],
            ], Http::STATUS_CREATED);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $this->l10n->t('Failed to save icon: %s', [$e->getMessage()])],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function serve(string $filename): Http\Response {
        try {
            $folder = $this->getUserIconFolder();
            $file = $folder->getFile($filename);

            $response = new FileDisplayResponse($file);
            $response->cacheFor(3600 * 24 * 7); // 7 days cache
            return $response;
        } catch (FilesNotFoundException) {
            return new DataResponse(['error' => $this->l10n->t('Icon not found')], Http::STATUS_NOT_FOUND);
        }
    }

    #[NoAdminRequired]
    public function destroy(string $filename): DataResponse {
        try {
            $folder = $this->getUserIconFolder();
            $file = $folder->getFile($filename);
            $file->delete();

            return new DataResponse(null, Http::STATUS_NO_CONTENT);
        } catch (FilesNotFoundException) {
            return new DataResponse(['error' => $this->l10n->t('Icon not found')], Http::STATUS_NOT_FOUND);
        }
    }

    private function getUserIconFolder(bool $create = false): ISimpleFolder {
        $folderName = 'icons_' . $this->userId;

        try {
            return $this->appData->getFolder($folderName);
        } catch (FilesNotFoundException $e) {
            if ($create) {
                return $this->appData->newFolder($folderName);
            }
            throw $e;
        }
    }
}
