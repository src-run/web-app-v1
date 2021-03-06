<?php

/*
 * This file is part of the `src-run/web-app-v1` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Generator;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class ImageGenerator.
 */
class ImageGenerator extends UrlGenerator
{
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

    /**
     * @return $this
     */
    protected function init()
    {
        if ($this->cacher instanceof \Memcache) {
            return $this;
        }

        if (false === ($this->cacherEnabled = $this->getAppParam('s.shield.cache_enabled'))) {
            return $this;
        }

        $this->cacher = $this->getAppParam('memcache');
        $this->cacher->setOptions([
            \Memcached::OPT_COMPRESSION => true,
            \Memcached::OPT_SERIALIZER => (\Memcached::HAVE_IGBINARY ? \Memcached::SERIALIZER_IGBINARY : \Memcached::SERIALIZER_PHP),
            \Memcached::OPT_PREFIX_KEY => 'src-run_',
        ]);
        $this->cacherTtl = $this->getAppParam('s.shield.cache_ttl');

        return $this;
    }

    public function getExternalRepoServiceShieldResponse($service, $org, $repo, $branch = null)
    {
        $this->init();

        if (!$branch) {
            $branch = 'master';
        }

        $shieldKey = $this->getShieldKey($service, $org, $repo, $branch);

        if (false === $blob = $this->getShieldBlobCached($shieldKey)) {
            $blob = $this->getExternalShieldBlobFetched($service, $org, $repo, $branch);
            $this->setShieldBlobCached($shieldKey, $blob);
        }

        return new Response($blob, 200, ['Content-Type' => 'image/svg+xml']);
    }

    public function getRepoServiceShieldResponse($key, $service, $repo)
    {
        $this->init();

        $shieldKey = $this->getShieldKey($service, $repo);

        if (false === $blob = $this->getShieldBlobCached($shieldKey)) {
            $blob = $this->getShieldBlobFetched($repo, $service, $key);
            $this->setShieldBlobCached($shieldKey, $blob);
        }

        return new Response($blob, 200, ['Content-Type' => 'image/svg+xml']);
    }

    /**
     * @param string $service
     * @param string ...$repoParameters
     *
     * @return string
     */
    protected function getShieldKey($service, ...$repoParameters)
    {
        $repo = implode('-', $repoParameters);

        return strtolower(preg_replace('{[-]+}i', '-', preg_replace('{[^a-z-]}i', '-', sprintf('shield-%s-%s', $service, $repo))));
    }

    /**
     * @param string $key
     *
     * @return bool|mixed
     */
    protected function getShieldBlobCached($key)
    {
        if (!$this->cacherEnabled || false === $result = $this->cacher->get($key)) {
            return false;
        }

        return $result;
    }

    /**
     * @param string $key
     * @param string $blob
     */
    protected function setShieldBlobCached($key, $blob)
    {
        if ($this->cacherEnabled) {
            $this->cacher->set($key, $blob, $this->cacherTtl);
        }
    }

    /**
     * @param string $service
     * @param string $org
     * @param string $repo
     *
     * @return bool|string
     */
    protected function getExternalShieldBlobFetched($service, $org, $repo, $branch)
    {
        $url = $this->getApp()['s.gen']->getExternalRepoServiceUrl($service.'_shield', $org, $repo, $branch);

        if (1 === preg_match('{\%id\%}', $url)) {
            return $this->getShieldBlobUnknown($service);
        }

        if (!$blob = @file_get_contents($url)) {
            return $this->getShieldBlobUnknown($service);
        }

        return $blob;
    }

    /**
     * @param string $repo
     * @param string $service
     * @param string $key
     *
     * @return bool|string
     */
    protected function getShieldBlobFetched($repo, $service, $key)
    {
        $url = $this->getApp()['s.gen']->getRepoServiceUrl($key, $service, $repo);

        if (1 === preg_match('{\%id\%}', $url)) {
            return $this->getShieldBlobUnknown($service);
        }

        if (!$blob = @file_get_contents($url)) {
            return $this->getShieldBlobUnknown($service);
        }

        return $blob;
    }

    /**
     * @param string $service
     *
     * @return string
     */
    protected function getShieldBlobUnknown($service)
    {
        $url = sprintf(
            'https://img.shields.io/badge/%s-unknown-orange.svg?style=flat-square',
            preg_replace('{[^a-z0-9-]+}i', '', str_replace('_', '--', str_replace('_shield', '', $service))));

        return file_get_contents($url);
    }
}

/* EOF */
