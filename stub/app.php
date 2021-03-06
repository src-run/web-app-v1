<?php

/*
 * This file is part of the `src-run/web-app-v1` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Silex\Application;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;
use SR\Config\ConfigHandlerDevServices;
use SR\Config\ConfigHandlerGitHub;
use SR\Error\ErrorHandler;
use SR\Generator\ImageGenerator;
use SR\Generator\UrlGenerator;
use SR\Request\RequestHandler;

if (!defined('APP_ENV')) {
    define('APP_ENV', 'prod');
}

$app = new Application();

require_once __DIR__.'/../config/app_'.APP_ENV.'.php';

$app->register(new HttpCacheServiceProvider());

$app->register(new SessionServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new UrlGeneratorServiceProvider());

$app->register(new MonologServiceProvider(), [
    'monolog.logfile' => __DIR__.'/../var/log/app.log',
    'monolog.name' => 'app',
    'monolog.level' => 300, // = Logger::WARNING
]);

$app->register(new SilexMemcache\MemcacheExtension(), [
    'memcache.library' => $app['s.mheap.memcache.library'],
    'memcache.server' => $app['s.mheap.memcache.servers'],
]);

if ($app['debug'] && isset($app['cache.path'])) {
    $app->register(new ServiceControllerServiceProvider());
    $app->register(new WebProfilerServiceProvider(), [
        'profiler.cache_dir' => $app['cache.path'].'/profiler',
    ]);
}

$app['s.ehr'] = new ErrorHandler();
$app['s.csv'] = new ConfigHandlerDevServices();
$app['s.cgh'] = new ConfigHandlerGitHub();
$app['s.gen'] = new UrlGenerator();
$app['s.img'] = new ImageGenerator();

$app['s.ehr']->attach($app);
$app['s.csv']->setApp($app)->loadFileContents(__DIR__.'/../config/services.yml')->parseYamlToConfig();
$app['s.cgh']->setApp($app)->init();
$app['s.gen']->setApp($app);
$app['s.img']->setApp($app);

// Include helper functions
require __DIR__.'/../stub/functions.php';

// ROUTE: Root redirect to main website
$app->get('/', function () use ($app) {
    return RequestHandler::returnRedirect('https://github.com/src-run', $app);
});

// ROUTE: Short link redirect for root-level links (no prefix before link name)
$shortLinksRoot(
    $app['s.csv']->getValueForKeyPath('root_redirects', 'links'),
    $app['s.csv']->getValueForKeyPath('root_redirects', 'alias')
);


// ROUTE: Short link redirects for links, aliases, and files
$shortLinks(
    ['go', 'sl', 'redirect'],
    $app['s.csv']->getValueForKeyPath('shortlinks', 'go'),
    $app['s.csv']->getValueForKeyPath('shortlinks', 'aliases')
);
$shortLinks(
    ['get', 'file'],
    $app['s.csv']->getValueForKeyPath('shortlinks', 'file'),
    $app['s.csv']->getValueForKeyPath('shortlinks', 'aliases')
);

// ROUTE: External shields (with branch)
$app->get('/shield/{org}/{repo}/{branch}/{service}.svg',

    function ($org, $repo, $branch, $service) use ($app) {
        $response = $app['s.img']->getExternalRepoServiceShieldResponse($service, $org, $repo, $branch);

        if (!$response) {
            return RequestHandler::returnRedirect('/', $app);
        }

        return $response;
    })
    ->assert('org', '[^/]+')
    ->assert('repo', '[^/]+')
    ->assert('branch', '[^/]+')
    ->assert('service', '[\w]{0,}');

// ROUTE: External shields
$app->get('/shield/{org}/{repo}/{service}.svg',

    function ($org, $repo, $service) use ($app) {
        $response = $app['s.img']->getExternalRepoServiceShieldResponse($service, $org, $repo);

        if (!$response) {
            return RequestHandler::returnRedirect('/', $app);
        }

        return $response;
    })
    ->assert('org', '[^/]+')
    ->assert('repo', '[^/]+')
    ->assert('service', '[\w]{0,}');

$goExternalRepoBranch = function ($org, $repo, $branch, $service) use ($app) {
    if (null === ($response = $app['s.gen']->getExternalRepoServiceUrl($service, $org, $repo, $branch))) {
        return RequestHandler::returnRedirect('/', $app);
    }

    return RequestHandler::returnRedirect($response, $app);
};

$goExternalRepo = function ($org, $repo, $service) use ($app) {
    if (null === ($response = $app['s.gen']->getExternalRepoServiceUrl($service, $org, $repo))) {
        return RequestHandler::returnRedirect('/', $app);
    }

    return RequestHandler::returnRedirect($response, $app);
};

// ROUTE: External repos (with branch)
$app->get('/repo/{org}/{repo}/{branch}/{service}', $goExternalRepoBranch)
    ->assert('org', '[^/]+')
    ->assert('repo', '[^/]+')
    ->assert('branch', '[^/]+')
    ->assert('service', '[\w]{0,}');

// ROUTE: External repos
$app->get('/repo/{org}/{repo}/{service}', $goExternalRepo)
    ->assert('org', '[^/]+')
    ->assert('repo', '[^/]+')
    ->assert('service', '[\w]{0,}');

// ROUTE: External repos (with branch)
$app->get('/service/{org}/{repo}/{branch}/{service}', $goExternalRepoBranch)
    ->assert('org', '[^/]+')
    ->assert('repo', '[^/]+')
    ->assert('branch', '[^/]+')
    ->assert('service', '[\w]{0,}');

// ROUTE: External repos
$app->get('/service/{org}/{repo}/{service}', $goExternalRepo)
    ->assert('org', '[^/]+')
    ->assert('repo', '[^/]+')
    ->assert('service', '[\w]{0,}');

$app->get('/api/xml/travis_cc',

    function () use ($app) {
        $projects = array_keys($app['s.csv']->getValueForKeyPath('projects'));
        $xml = new SimpleXMLElement('<Projects></Projects>');

        foreach ($projects as $repo) {
            $collection = array_values((array) $app['s.cgh']->repositoryNames);
            $project = $app['s.csv']->getClosestCollectionMatch($repo, $collection);
            $key = array_search($project, $collection);
            $url = $app['s.gen']->getRepoServiceUrl($key, 'travis_api_cc', $repo);
            $remoteXml = new SimpleXMLElement(file_get_contents($url));
            $childXml = $xml->addChild('Project');
            foreach (['name', 'activity', 'lastBuildStatus', 'lastBuildLabel', 'lastBuildTime', 'webUrl'] as $attribute) {
                $childXml->addAttribute($attribute, $remoteXml->Project[$attribute]);
            }
        }

        return RequestHandler::returnXml($xml, $app);
    });

$serviceShieldRoute = function ($repo, $service) use ($app) {
    $service = $service.'_shield';
    $collection = array_values((array) $app['s.cgh']->repositoryNames);
    $project = $app['s.csv']->getClosestCollectionMatch($repo, $collection);
    $key = array_search($project, $collection);
    $response = $app['s.img']->getRepoServiceShieldResponse($key, $service, $repo);

    if (!$response) {
        return RequestHandler::returnRedirect('/', $app);
    }

    return $response;
};

$app->get('/r/{repo}/{service}_shield', $serviceShieldRoute)
    ->assert('repo', '[^/]+')
    ->assert('service', '[\w]{0,}');

$app->get('/r/{repo}/{service}.svg', $serviceShieldRoute)
    ->assert('repo', '[^/]+')
    ->assert('service', '[\w]{0,}');

$app->get('/r/{repo}/{service}',

    function ($repo, $service) use ($app) {
        $collection = array_values((array) $app['s.cgh']->repositoryNames);
        $project = $app['s.csv']->getClosestCollectionMatch($repo, $collection);
        $key = array_search($project, $collection);

        if (null === ($externalRedirect = $app['s.gen']->getRepoServiceUrl($key, $service, $repo))) {
            return RequestHandler::returnRedirect('/', $app);
        }

        return RequestHandler::returnRedirect($externalRedirect, $app);
    })
    ->assert('repo', '[^/]+')
    ->assert('service', '[\w]{0,}');

// ROUTE: Repository redirects
$app->get('/r/{repo}',

    function ($repo) use ($app) {
        $collection = array_values((array) $app['s.cgh']->repositoryNames);
        $project = $app['s.csv']->getClosestCollectionMatch($repo, $collection);
        $key = array_search($project, $collection);

        if (!$key) {
            return RequestHandler::returnRedirect('/', $app);
        }

        return RequestHandler::returnRedirect($app['s.cgh']->repositoryUrls[$key], $app);
    })
    ->assert('repo', '.+');

// ROUTE: Github user service redirects (wakatime, etc)
$app->get('/u/{user}/{service}',

    function ($user, $service) use ($app) {
        if (true === empty($service)) {
            return RequestHandler::returnRedirect('/u/'.$user, $app);
        }

        if (null === ($externalRedirect = $app['s.gen']->getUserServiceUrl($user, $service))) {
            return RequestHandler::returnRedirect('/r/'.$user.'/'.$service, $app);
        }

        return RequestHandler::returnRedirect($externalRedirect, $app);
    })
    ->assert('user', '[\w]{3,}')
    ->assert('service', '[\w]{0,}');

// ROUTE: Github user redirects
$app->get('/u/{user}',

    function ($user) use ($app) {
        if (null === ($externalRedirect = $app['s.gen']->getUserServiceUrl($user, 'git'))) {
            return RequestHandler::returnRedirect('/r/'.$user, $app);
        }

        return RequestHandler::returnRedirect($externalRedirect, $app);
    })
    ->assert('user', '[\w]{3,}');

// ROUTE: Github user service redirects (wakatime, etc) with no prefix
$app->get('/{user}/{service}',

    function ($user, $service) use ($app) {
        if (true === empty($service)) {
            return RequestHandler::returnRedirect('/u/', $app);
        }

        if (null === ($externalRedirect = $app['s.gen']->getUserServiceUrl($user, $service))) {
            return RequestHandler::returnRedirect('/r/'.$user.'/'.$service, $app);
        }

        return RequestHandler::returnRedirect($externalRedirect, $app);
    })
    ->assert('user', '[^/]+')
    ->assert('service', '[\w]{0,}');

// ROUTE: Github user redirects with no prefix
$app->get('/{user}',

    function ($user) use ($app) {
        if (null === ($externalRedirect = $app['s.gen']->getUserServiceUrl($user))) {
            return RequestHandler::returnRedirect('/r/'.$user, $app);
        }

        return RequestHandler::returnRedirect($externalRedirect, $app);
    })
    ->assert('user', '.+');

return $app;

/* EOF */
