<?php

/*
 * This file is part of the Scr.be Application.
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Scribe\Error\ErrorHandler;
use Scribe\Config\ConfigHandlerDevServices;
use Scribe\Config\ConfigHandlerGitHub;
use Scribe\Generator\UrlGenerator;
use Scribe\Request\RequestHandler;

$app['s.ehr'] = new ErrorHandler();
$app['s.csv'] = new ConfigHandlerDevServices();
$app['s.cgh'] = new ConfigHandlerGitHub();
$app['s.gen'] = new UrlGenerator();

$app['s.ehr']->attach($app);
$app['s.csv']->setApp($app)->loadFileContents(__DIR__.'/../resources/fixtures/dev_services.yml')->parseYamlToConfig();
$app['s.cgh']->setApp($app)->init();
$app['s.gen']->setApp($app);

$app->get('/',

    function() use ($app) {

        return RequestHandler::returnRedirect('https://scribenet.com', $app);

    })
;

$app->get('/r/{repo}/{service}',

    function($repo, $service) use ($app) {

        $collection = array_values((array) $app['s.cgh']->repositoryNames);
        $project    = $app['s.csv']->getClosestCollectionMatch($repo, $collection);
        $key        = array_search($project, $collection);

        if (null === ($externalRedirect = $app['s.gen']->getRepoServiceUrl($key, $service))) {
            return RequestHandler::returnRedirect('/', $app);
        }

        return RequestHandler::returnRedirect($externalRedirect, $app);

    })
    ->assert('repo', '[^/]+')
    ->assert('service', '[\w]{0,}')
;

$app->get('/r/{repo}',

    function($repo) use ($app) {

        $collection = array_values((array) $app['s.cgh']->repositoryNames);
        $project    = $app['s.csv']->getClosestCollectionMatch($repo, $collection);
        $key        = array_search($project, $collection);

        if (!$key) {
            return RequestHandler::returnRedirect('/', $app);
        }

        return RequestHandler::returnRedirect($app['s.cgh']->repositoryUrls[$key], $app);

    })
    ->assert('repo', '.+')
;

$app->get('/u/{user}/{service}',

    function($user, $service) use ($app) {

        if (true === empty($service)) {
            return RequestHandler::returnRedirect('/u/'.$user, $app);
        }

        if (null === ($externalRedirect = $app['s.gen']->getUserServiceUrl($user, $service))) {
            return RequestHandler::returnRedirect('/r/'.$user.'/'.$service, $app);
        }

        return RequestHandler::returnRedirect($externalRedirect, $app);

    })
    ->assert('user', '[\w]{3,}')
    ->assert('service', '[\w]{0,}')
;

$app->get('/u/{user}',

    function($user) use ($app) {

        if (null === ($externalRedirect = $app['s.gen']->getUserServiceUrl($user, 'git'))) {
            return RequestHandler::returnRedirect('/r/'.$user, $app);
        }

        return RequestHandler::returnRedirect($externalRedirect, $app);

    })
    ->assert('user', '[\w]{3,}')
;

$app->get('/{user}/{service}',

    function($user, $service) use ($app) {

        if (true === empty($service)) {
            return RequestHandler::returnRedirect('/u/', $app);
        }

        if (null === ($externalRedirect = $app['s.gen']->getUserServiceUrl($user, $service))) {
            return RequestHandler::returnRedirect('/r/'.$user.'/'.$service, $app);
        }

        return RequestHandler::returnRedirect($externalRedirect, $app);

    })
    ->assert('user', '[^/]+')
    ->assert('service', '[\w]{0,}')
;

$app->get('/{user}',

    function($user) use ($app) {

        if (null === ($externalRedirect = $app['s.gen']->getUserServiceUrl($user))) {
            return RequestHandler::returnRedirect('/r/'.$user, $app);
        }

        return RequestHandler::returnRedirect($externalRedirect, $app);

    })
    ->assert('user', '.+')
;

return $app;

/* EOF */
