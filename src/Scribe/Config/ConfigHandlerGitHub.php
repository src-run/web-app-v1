<?php

/*
 * This file is part of the Scr.be Application
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Scribe\Config;
use Github\Client;
use Github\Exception\RuntimeException;
use Github\HttpClient\HttpClient;
use Github\HttpClient\Message\ResponseMediator;
use Github\ResultPager;

/**
 * Class ConfigHandlerGitHub.
 */
class ConfigHandlerGitHub extends ConfigHandler
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    public $repositories;

    /**
     * @var array
     */
    public $repositoryNames;

    /**
     * @var array
     */
    public $repositoryUrls;

    /**
     * @var array
     */
    public $repositoryLicenseSlug;

    /**
     * @var array
     */
    public $repositoryLicenseHtml;

    /**
     * @var array
     */
    public $repositoryReadmeHtml;

    /**
     * @var \Memcached
     */
    protected $cacher;

    /**
     * @var int
     */
    protected $cacherTtl;

    /**
     * @var bool
     */
    protected $cacherEnabled;

    /*
     * Initialize github config handler.
     */
    public function init()
    {
        $this
            ->instantiateNewCacher()
            ->instantiateNewClient()
            ->authenticateClient()
            ->getRepositories(
                $this->getAppParam('s.github.api.organization')
            )
        ;
    }

    /**
     * @return $this
     */
    protected function instantiateNewCacher()
    {
        if (false === ($this->cacherEnabled = $this->getAppParam('s.github.api.cache_enabled'))) {
            return $this;
        }

        $this->cacher = $this->getAppParam('memcache');
        $this->cacher->setOptions([
            \Memcached::OPT_COMPRESSION => true,
            \Memcached::OPT_SERIALIZER => (\Memcached::HAVE_IGBINARY ? \Memcached::SERIALIZER_IGBINARY : \Memcached::SERIALIZER_PHP),
            \Memcached::OPT_PREFIX_KEY => 'scr.be_'
        ]);
        $this->cacherTtl = $this->getAppParam('s.github.api.cache_ttl');

        return $this;
    }

    /**
     * @return $this
     */
    protected function instantiateNewClient()
    {
        $this->client = new Client();

        return $this;
    }

    /**
     * @return $this
     */
    protected function authenticateClient()
    {
        $this->client->authenticate(
            $this->getAppParam('s.github.api.username'),
            $this->getAppParam('s.github.api.password'),
            $this->getAppParam('s.github.api.method')
        );

        return $this;
    }

    /**
     * @param array $owners
     *
     * @return $this
     */
    protected function getRepositories(array $owners = [])
    {
        $cacheKey = (string) __CLASS__.'_repository-data';

        if ($this->cacherEnabled && false !== ($repoData = $this->cacher->get($cacheKey))) {
            $this->repositories = $repoData['repos'];
            $this->repositoryNames = $repoData['names'];
            $this->repositoryUrls = $repoData['urls'];
            $this->repositoryLicenseSlug = $repoData['license_slugs'];
            $this->repositoryLicenseHtml = $repoData['license_contents'];
            $this->repositoryReadmeHtml = $repoData['readme_contents'];

            return $this;
        }

        foreach ($owners as $o) {
            $apiRepo     = $this->client->api('organization');
            $http        = $this->client->getHttpClient();
            $resultPager = new ResultPager($this->client);
            $getParams   = [$o];
            $http->setOption('api_version', 'drax-preview');
            $http->setHeaders(['Accept' => 'application/vnd.github.drax-preview+json']);
            $results     = $resultPager->fetchAll($apiRepo, 'repositories', $getParams);

            if (!is_array($results) || !(count($results) > 0)) {
                continue;
            }

            foreach ($results as $repository) {
                if (!is_array($repository) ||
                    !array_key_exists('name', $repository) ||
                    !array_key_exists('html_url', $repository)) {
                    continue;
                }

                $this->repositories[] = ($this->attemptToGetFullRepository($repository) ?: $repository);
                $this->repositoryNames[] = $repository['name'];
                $this->repositoryUrls[] = $repository['html_url'];
                $this->repositoryLicenseSlug[] =
                    array_key_exists('license', $repository) && array_key_exists('key', $repository['license']) ?
                        $repository['license']['key'] : null
                ;
                $this->repositoryLicenseHtml[] = $this->attemptToGetLicense($repository);
                $this->repositoryReadmeHtml[] = $this->attemptToGetReadme($repository);
            }
        }

        if ($this->cacherEnabled) {
            $repoData['repos'] = $this->repositories;
            $repoData['names'] = $this->repositoryNames;
            $repoData['urls'] = $this->repositoryUrls;
            $repoData['license_slugs'] = $this->repositoryLicenseSlug;
            $repoData['license_contents'] = $this->repositoryLicenseHtml;
            $repoData['readme_contents'] = $this->repositoryReadmeHtml;

            $this->cacher->set($cacheKey, $repoData, $this->cacherTtl);
        }
    }

    /**
     * @param array $repository
     *
     * @return null|array
     */
    protected function attemptToGetFullRepository(array $repository)
    {
        try {
            $http = new HttpClient(['api_version' => 'drax-preview']);
            $response = $http->get('repos/' . $repository['full_name']);
            $repo = ResponseMediator::getContent($response);
        } catch (RuntimeException $e) {
            return null;
        }

        return $repo;
    }

    /**
     * @param array $repository
     *
     * @return null|array
     */
    protected function attemptToGetLicense(array $repository)
    {
        $license = null;

        try {
            $http = new HttpClient();
            $http->setHeaders(['Accept' => 'application/vnd.github.VERSION.html']);
            $response = $http->get('repos/' . $repository['full_name'] . '/contents/LICENSE.md');
            $license = ResponseMediator::getContent($response);
        } catch (RuntimeException $e) {
            $license = null;
        }

        if ($license !== null) {
            return $license;
        }

        try {
            $http = new HttpClient();
            $http->setHeaders(['Accept' => 'application/vnd.github.VERSION.raw']);
            $response = $http->get('repos/' . $repository['full_name'] . '/contents/LICENSE');
            $license = ResponseMediator::getContent($response);
        } catch (RuntimeException $e) {
            $license = null;
        }

        return $license;
    }

    /**
     * @param array $repository
     *
     * @return null|array
     */
    protected function attemptToGetReadme(array $repository)
    {
        $readme = null;

        try {
            $http = new HttpClient();
            $http->setHeaders(['Accept' => 'application/vnd.github.VERSION.html']);
            $response = $http->get('repos/' . $repository['full_name'] . '/readme');
            $readme = ResponseMediator::getContent($response);
        } catch (RuntimeException $e) {
            $readme = null;
        }

        if ($readme !== null) {
            return $readme;
        }

        try {
            $http = new HttpClient();
            $http->setHeaders(['Accept' => 'application/vnd.github.VERSION.raw']);
            $response = $http->get('repos/' . $repository['full_name'] . '/readme');
            $readme = ResponseMediator::getContent($response);
        } catch (RuntimeException $e) {
            $readme = null;
        }

        return $readme;
    }
}

/* EOF */
