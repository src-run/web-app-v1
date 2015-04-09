<?php

/*
 * This file is part of the Scr.be Application.
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/**
 * @param \Silex\Application $app
 * @param string             $file
 */
function loadParametersFileAndMapToApp(\Silex\Application $app, $file) {
    if (file_exists($parametersFile =  __DIR__.DIRECTORY_SEPARATOR.$file)) {
        $params = include($parametersFile);

        foreach ($params as $key => $value) {
            $app[(string) 's.'.$key] = $params[$key];
        }
    }
}

// Locale
$app['locale'] = 'en';
$app['session.default_locale'] = $app['locale'];

// Cache dir path
$app['cache.path'] = __DIR__.'/../cache';

// HTTP cache dir path
$app['http_cache.cache_dir'] = $app['cache.path'] . '/http';

// Twig cache dir path
$app['twig.options.cache'] = $app['cache.path'] . '/twig';

// Include config parameters, if available
loadParametersFileAndMapToApp($app, 'parameters.php');

/* EOF */
