<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {

    public function __construct(
        IRequest $request,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): TemplateResponse {
        Util::addScript(Application::APP_ID, 'linkboard-main');
        Util::addStyle(Application::APP_ID, 'styles');

        return new TemplateResponse(Application::APP_ID, 'index');
    }
}
