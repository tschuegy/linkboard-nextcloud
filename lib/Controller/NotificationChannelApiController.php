<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Db\NotificationChannel;
use OCA\LinkBoard\Db\NotificationChannelMapper;
use OCA\LinkBoard\NotificationProvider\NotificationProviderRegistry;
use OCA\LinkBoard\Service\NotificationDispatcherService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class NotificationChannelApiController extends ApiController {

    public function __construct(
        IRequest $request,
        private NotificationChannelMapper $mapper,
        private NotificationProviderRegistry $providerRegistry,
        private NotificationDispatcherService $dispatcher,
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    /**
     * GET /api/v1/notification-channels/providers
     */
    #[NoAdminRequired]
    public function providers(): DataResponse {
        return new DataResponse($this->providerRegistry->getCatalog());
    }

    /**
     * GET /api/v1/notification-channels
     */
    #[NoAdminRequired]
    public function index(): DataResponse {
        $channels = $this->mapper->findAllByUser($this->userId);
        return new DataResponse(array_map(fn($c) => $this->serializeChannel($c), $channels));
    }

    /**
     * POST /api/v1/notification-channels
     */
    #[NoAdminRequired]
    public function create(string $name, string $providerType, string $config = '{}', bool $enabled = true): DataResponse {
        $provider = $this->providerRegistry->get($providerType);
        if (!$provider) {
            return new DataResponse(['error' => 'Unknown provider type'], Http::STATUS_BAD_REQUEST);
        }

        $channel = new NotificationChannel();
        $channel->setUserId($this->userId);
        $channel->setName($name);
        $channel->setProviderType($providerType);
        $channel->setConfig($config);
        $channel->setEnabled($enabled);

        $channel = $this->mapper->insert($channel);
        return new DataResponse($this->serializeChannel($channel), Http::STATUS_CREATED);
    }

    /**
     * PUT /api/v1/notification-channels/{id}
     */
    #[NoAdminRequired]
    public function update(int $id, ?string $name = null, ?string $providerType = null, ?string $config = null, ?bool $enabled = null): DataResponse {
        $channel = $this->mapper->findById($id, $this->userId);
        if (!$channel) {
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }

        if ($providerType !== null) {
            $provider = $this->providerRegistry->get($providerType);
            if (!$provider) {
                return new DataResponse(['error' => 'Unknown provider type'], Http::STATUS_BAD_REQUEST);
            }
            $channel->setProviderType($providerType);
        }

        if ($name !== null) $channel->setName($name);
        if ($config !== null) { $channel->setConfig($this->mergeSecretConfig($channel, $providerType ?? $channel->getProviderType(), $config)); }
        if ($enabled !== null) $channel->setEnabled($enabled);

        $channel = $this->mapper->update($channel);
        return new DataResponse($this->serializeChannel($channel));
    }

    /**
     * DELETE /api/v1/notification-channels/{id}
     */
    #[NoAdminRequired]
    public function destroy(int $id): DataResponse {
        $channel = $this->mapper->findById($id, $this->userId);
        if (!$channel) {
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }

        $this->mapper->delete($channel);
        return new DataResponse(['status' => 'ok']);
    }

    /**
     * POST /api/v1/notification-channels/{id}/test
     */
    #[NoAdminRequired]
    public function test(int $id): DataResponse {
        $result = $this->dispatcher->testChannel($id, $this->userId);
        $status = $result['success'] ? Http::STATUS_OK : Http::STATUS_BAD_REQUEST;
        return new DataResponse($result, $status);
    }
    private function serializeChannel(NotificationChannel $channel): array {
        $data = $channel->jsonSerialize();
        $provider = $this->providerRegistry->get($channel->getProviderType());
        foreach ($provider?->getConfigFields() ?? [] as $field) {
            if ($field['type'] === 'password') { unset($data['config'][$field['key']]); }
        }
        return $data;
    }

    private function mergeSecretConfig(NotificationChannel $channel, string $providerType, string $config): string {
        $new = json_decode($config, true);
        if (!is_array($new)) { throw new \InvalidArgumentException('Invalid channel configuration'); }
        $old = json_decode($channel->getConfig(), true);
        $old = is_array($old) ? $old : [];
        foreach ($this->providerRegistry->get($providerType)?->getConfigFields() ?? [] as $field) {
            $key = $field['key'];
            if ($field['type'] === 'password' && empty($new[$key]) && isset($old[$key])) { $new[$key] = $old[$key]; }
        }
        return json_encode($new, JSON_THROW_ON_ERROR);
    }
}
