<?php

/*
 * This file is part of the `src-run/web-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

function loadParametersFileAndMapToApp(\Silex\Application $app, $file) {
    if (file_exists($parametersFile =  __DIR__.DIRECTORY_SEPARATOR.$file)) {
        $params = include($parametersFile);

        foreach ($params as $key => $value) {
            $app[(string) 's.'.$key] = $params[$key];
        }
    }
}

$app['locale'] = 'en';
$app['session.default_locale'] = $app['locale'];
$app['cache.path'] = __DIR__ . '/../cache';
$app['http_cache.cache_dir'] = $app['cache.path'] . '/http';

loadParametersFileAndMapToApp($app, 'parameters.php');

/* EOF */
