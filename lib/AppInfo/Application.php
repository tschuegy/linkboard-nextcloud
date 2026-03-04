<?php
declare(strict_types=1);
namespace OCA\LinkBoard\AppInfo;

use OCA\LinkBoard\Listener\CSPListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class Application extends App implements IBootstrap {

    public const APP_ID = 'linkboard';
    public const MAX_SERVICES_PER_USER = 500;

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(AddContentSecurityPolicyEvent::class, CSPListener::class);
    }

    public function boot(IBootContext $context): void {
    }
}
