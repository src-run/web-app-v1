<?php
/*
 * This file is part of the Scr.be Website
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Scribe\Error\ErrorHandler;
use Scribe\Config\ConfigHandler;
use Scribe\Config\ConfigURLGen;
use Scribe\Request\RequestHandler;
use Silex\Application;

/*
 * Include composer auto-loader config.
 * Instantiate Silex and custom classes.
 */
$ldr = require_once __DIR__.'/../vendor/autoload.php';
$app = new Application();
$err = new ErrorHandler();
$cfg = new ConfigHandler();
$gen = new ConfigURLGen();

/*
 * Config error handlers on Silex app and runmode (debug/no-debug).
 * Load/parse/config YAML configuration file.
 * Pass config object to URL generator.
 */
$err->setDebug($app, true)->attach($app);
$cfg->loadFileContents(__DIR__.'/../conf/knowledge.yml')->parseYamlToConfig();
$gen->setConfigHandler($cfg);

/*
 * ROUTE: Default
 * Redirects to GitLab
 */
$app->get('/', function() use ($app) {

    return RequestHandler::returnRedirect('https://git.scr.be', $app);

});

/*
 * ROUTE: GitHub Profile
 * Attempts to take user initials passed to URL and redirect to GitHub profile. Falls back to default route.
 */
$app->get('/{user}', function($user) use ($app, $cfg, $gen) {

    $username = $cfg->getValueForKeyPath('redirects', 'users', 'mapping', $user);

    if (null === $username || false === ($url = $gen->getGitHubProfileUrl($username))) {
        return RequestHandler::returnSubRequest('/', $app);
    }

    return RequestHandler::returnRedirect($url, $app);

})->assert('user', '[\w]{3}');

/*
 * ROUTE: GitHub Public Repo
 * Attempts to tale the given string and find the closest matching GitHub public repo to said strng
 */
$app->get('/{repo}', function($repo) use ($app, $cfg, $gen) {

    $project = $cfg->getClosestProjectMatch($repo);

    if (null === $project || false === ($url = $gen->getGitHubScribeProjectUrl($project))) {
        return RequestHandler::returnSubRequest('/', $app);
    }

    return RequestHandler::returnRedirect($url, $app);

})->assert('user', '[\w\d\._-]');

/*
 * ROUTE: Catch-All
 * Performs sub-request to default route.
 */
$app->get('{url}', function() use ($app, $cfg, $gen) {

    return RequestHandler::returnSubRequest('/', $app);

})->assert('url', '.+');

/*
 * Run controller!
 */
$app->run();

/* EOF */
