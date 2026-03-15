<?php
declare(strict_types=1);
namespace OCA\LinkBoard\BackgroundJob;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\StatusCheckService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

class StatusCheckJob extends TimedJob {

    public function __construct(
        ITimeFactory $time,
        private StatusCheckService $statusCheckService,
        private LoggerInterface $logger,
        private IAppConfig $appConfig,
    ) {
        parent::__construct($time);
        $interval = $this->appConfig->getValueInt(Application::APP_ID, 'status_check_interval', 300);
        $interval = max(60, min(1800, $interval));
        $this->setInterval($interval);
    }

    protected function run($argument): void {
        try {
            $checked = $this->statusCheckService->checkAllEnabled();
            $this->statusCheckService->purgeOldHistory();
            if ($checked > 0) {
                $this->logger->info("LinkBoard: Status check completed for {$checked} services");
            }
        } catch (\Throwable $e) {
            $this->logger->error('LinkBoard: Status check job failed', [
                'exception' => $e,
            ]);
        }
    }
}
