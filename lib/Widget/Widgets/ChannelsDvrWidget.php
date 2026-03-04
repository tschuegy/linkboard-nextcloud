<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget\Widgets;

use OCA\LinkBoard\Widget\AbstractWidget;

class ChannelsDvrWidget extends AbstractWidget {

    public function getId(): string { return 'channelsdvr'; }
    public function getLabel(): string { return 'Channels DVR'; }

    public function getConfigFields(): array { return []; }

    public function getAllowedFields(): array { return ['passes', 'recordings', 'scheduled']; }

    public function getFieldLabels(): array {
        return ['passes' => 'Passes', 'recordings' => 'Recordings', 'scheduled' => 'Scheduled'];
    }

    public function buildRequests(string $baseUrl, array $config): array {
        $base = rtrim($baseUrl, '/');
        return [
            ['url' => $base . '/dvr'],
        ];
    }

    public function mapResponse(array $responses, array $config): array {
        $data = $responses[0] ?? [];
        return [
            'passes' => (string)($data['passes'] ?? 0),
            'recordings' => (string)($data['recordings'] ?? 0),
            'scheduled' => (string)($data['scheduled'] ?? 0),
        ];
    }
}
