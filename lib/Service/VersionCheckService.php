<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Service;

use OCP\App\IAppManager;
use OCP\ICacheFactory;
use Psr\Log\LoggerInterface;

class VersionCheckService {

    public function __construct(
        private IAppManager $appManager,
        private ICacheFactory $cacheFactory,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{version: string, latestVersion: ?string, latestVersionUrl: ?string}
     */
    public function getVersionInfo(bool $checkForUpdates): array {
        $version = $this->appManager->getAppVersion('linkboard');

        if (!$checkForUpdates) {
            return [
                'version' => $version,
                'latestVersion' => null,
                'latestVersionUrl' => null,
            ];
        }

        $cache = $this->cacheFactory->createDistributed('linkboard');
        $cached = $cache->get('latest_release');

        if ($cached !== null) {
            return $this->buildResponse($version, $cached);
        }

        try {
            $releaseData = $this->fetchLatestRelease();
            $cache->set('latest_release', $releaseData, 3600);
            return $this->buildResponse($version, $releaseData);
        } catch (\Throwable $e) {
            $this->logger->warning('LinkBoard: Failed to check for updates', ['exception' => $e]);
            return [
                'version' => $version,
                'latestVersion' => null,
                'latestVersionUrl' => null,
            ];
        }
    }

    private function fetchLatestRelease(): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.github.com/repos/tschuegy/linkboard-nextcloud/releases/latest',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'LinkBoard/1.0 UpdateCheck',
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new \RuntimeException('GitHub API returned HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['tag_name'])) {
            throw new \RuntimeException('Invalid GitHub API response');
        }

        $tagName = $data['tag_name'];
        $latestVersion = ltrim($tagName, 'vV');

        return [
            'version' => $latestVersion,
            'url' => $data['html_url'] ?? null,
        ];
    }

    private function buildResponse(string $installedVersion, array $releaseData): array {
        $latestVersion = $releaseData['version'] ?? null;
        $latestUrl = $releaseData['url'] ?? null;

        if ($latestVersion && version_compare($installedVersion, $latestVersion, '<')) {
            return [
                'version' => $installedVersion,
                'latestVersion' => $latestVersion,
                'latestVersionUrl' => $latestUrl,
            ];
        }

        return [
            'version' => $installedVersion,
            'latestVersion' => null,
            'latestVersionUrl' => null,
        ];
    }
}
