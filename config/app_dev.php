<?php

/*
 * This file is part of the `src-run/web-app-v1` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

// Include the production configuration
require __DIR__.DIRECTORY_SEPARATOR.'app_prod.php';

// Enable debug mode
$app['debug'] = true;

/* EOF */
