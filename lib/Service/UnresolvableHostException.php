<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Service;

/** Thrown when an outbound target hostname has no A/AAAA records. */
class UnresolvableHostException extends \InvalidArgumentException {
}
