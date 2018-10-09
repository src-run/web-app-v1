<?php

/*
 * This file is part of the `src-run/web-app-v1` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

define('APP_ENV', 'prod');

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../stub/app.php';

$app['http_cache']->run();

/* EOF */

