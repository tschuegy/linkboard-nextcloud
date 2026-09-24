<?php

declare(strict_types=1);

/**
 * LinkBoard - Main template
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCP\Util;

// Expose the app ID for the Vue.js frontend
Util::addScript('linkboard', 'linkboard-vendors');
Util::addScript('linkboard', 'linkboard-main');

// No markup: the Vue app mounts on the server's #content element
