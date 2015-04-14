#!/usr/bin/env php
<?php

/*
 * This file is part of the Scr.be Application.
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

require __DIR__.'/../resources/config/app_prod.php';
require __DIR__.'/../src/app.php';
require __DIR__.'/../src/controllers.php';

list($_, $method, $path) = $argv;

$request = Symfony\Component\HttpFoundation\Request::create($path, $method);

$app->run($request);

/* EOF */
