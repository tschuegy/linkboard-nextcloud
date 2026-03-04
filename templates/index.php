<?php

declare(strict_types=1);

/**
 * LinkBoard - Main template
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCP\Util;

// Expose the app ID for the Vue.js frontend
Util::addScript('linkboard', 'linkboard-main');

?>

<div id="linkboard-app"></div>
