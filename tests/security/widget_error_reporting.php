<?php

declare(strict_types=1);

namespace OCP {
    interface IL10N {
        public function t(string $text, $parameters = []): string;
    }
}

namespace OCP\AppFramework {
    class Controller {
    }

    class ApiController extends Controller {
    }
}

namespace {
    use OCA\LinkBoard\Controller\WidgetProxyController;
    use OCA\LinkBoard\Widget\WidgetRequestException;
    use OCP\IL10N;

    require dirname(__DIR__, 2) . '/vendor/autoload.php';

    // SPDX-License-Identifier: AGPL-3.0-or-later

    function expectSameValue(mixed $expected, mixed $actual, string $description): void {
        if ($expected !== $actual) {
            throw new \RuntimeException($description . ' (expected ' . var_export($expected, true)
                . ', got ' . var_export($actual, true) . ')');
        }
    }

    final class TestL10N implements IL10N {
        public function t(string $text, $parameters = []): string {
            return vsprintf($text, (array)$parameters);
        }
    }

    $controller = (new \ReflectionClass(WidgetProxyController::class))->newInstanceWithoutConstructor();
    $l10nProperty = new \ReflectionProperty(WidgetProxyController::class, 'l10n');
    $l10nProperty->setValue($controller, new TestL10N());

    $contextMethod = new \ReflectionMethod(WidgetProxyController::class, 'widgetErrorContext');
    $messageMethod = new \ReflectionMethod(WidgetProxyController::class, 'widgetErrorMessage');
    $context = fn(\Throwable $e): array => $contextMethod->invoke($controller, $e);
    $message = fn(\Throwable $e): string => $messageMethod->invoke($controller, $e);

    // Upstream HTTP failures name their status in both the log context and the tile.
    $httpFailure = WidgetRequestException::httpStatus(403);
    expectSameValue(403, $context($httpFailure)['httpStatus'] ?? null, 'HTTP status missing from log context');
    expectSameValue(
        'Widget data fetch failed: HTTP 403',
        $message($httpFailure),
        'HTTP status missing from user-facing message',
    );

    // Transport failures name the cURL error number instead.
    $curlFailure = WidgetRequestException::curlError(28);
    expectSameValue(28, $context($curlFailure)['curlErrno'] ?? null, 'cURL errno missing from log context');
    expectSameValue(null, $context($curlFailure)['httpStatus'] ?? null, 'cURL failure reported an HTTP status');
    expectSameValue(
        'Widget data fetch failed: cURL 28',
        $message($curlFailure),
        'cURL errno missing from user-facing message',
    );

    // Every failure keeps the exception class and code for correlation.
    foreach ([$httpFailure, $curlFailure] as $failure) {
        expectSameValue(
            WidgetRequestException::class,
            $context($failure)['exceptionClass'] ?? null,
            'Exception class missing from log context',
        );
    }

    // Any other throwable stays limited to class and code — its message may carry
    // request details, so it must reach neither the log context nor the dashboard.
    $secret = 'https://user:hunter2@immich.example.com/api/server/statistics';
    $foreignFailure = new \RuntimeException('Request to ' . $secret . ' failed', 7);
    $foreignContext = $context($foreignFailure);
    expectSameValue(
        ['exceptionClass' => \RuntimeException::class, 'exceptionCode' => 7],
        $foreignContext,
        'Foreign exception leaked detail into the log context',
    );
    expectSameValue(
        'Widget data fetch failed',
        $message($foreignFailure),
        'Foreign exception leaked detail into the user-facing message',
    );
    foreach (['immich.example.com', 'hunter2', 'server/statistics'] as $needle) {
        if (str_contains(json_encode($foreignContext) . $message($foreignFailure), $needle)) {
            throw new \RuntimeException('Request detail "' . $needle . '" leaked out of a widget failure');
        }
    }

    // The bounded detail never carries anything but the status or errno itself.
    foreach ([WidgetRequestException::httpStatus(404), WidgetRequestException::curlError(60)] as $failure) {
        if (preg_match('/^(HTTP \d+|cURL \d+)$/D', $failure->getPublicDetail()) !== 1) {
            throw new \RuntimeException('Public detail is not a bare status or errno: ' . $failure->getPublicDetail());
        }
    }

    echo "Widget error reporting checks passed\n";
}
