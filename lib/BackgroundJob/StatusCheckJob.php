<?php
declare(strict_types=1);
namespace OCA\LinkBoard\BackgroundJob;

use OCA\LinkBoard\Service\StatusCheckService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class StatusCheckJob extends TimedJob {

    public function __construct(
        ITimeFactory $time,
        private StatusCheckService $statusCheckService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($time);
        // Run every 5 minutes
        $this->setInterval(300);
    }

    protected function run($argument): void {
        try {
            $checked = $this->statusCheckService->checkAllEnabled();
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
