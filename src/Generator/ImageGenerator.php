<?php
/*
 * This file is part of the `src-run/web-app` project
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Generator;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml;

/**
 * Class ImageGenerator
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
            \Memcached::OPT_PREFIX_KEY => 'src-run_'
        ]);
        $this->cacherTtl = $this->getAppParam('s.shield.cache_ttl');

        return $this;
    }

    public function getRepoServiceShieldResponse($key, $service, $repo)
    {
        $this->init();

        $shieldKey = $this->getShieldKey($repo, $service);

        if (false === $blob = $this->getShieldBlobCached($shieldKey)) {
            $blob = $this->getShieldBlobFetched($repo, $service, $key);
            $this->setShieldBlobCached($shieldKey, $blob);
        }

        return new Response($blob, 200, ['Content-Type' => 'image/svg+xml']);
    }

    /**
     * @param string $repo
     * @param string $service
     *
     * @return string
     */
    protected function getShieldKey($repo, $service)
    {
        return sprintf('shield-%s-%s', preg_replace('{[^a-z-]}i', '', $repo), $service);
    }

    protected function getShieldBlobCached($key)
    {
        if (!$this->cacherEnabled || false === $result = $this->cacher->get($key)) {
            return false;
        }

        return $result;
    }

    protected function setShieldBlobCached($key, $blob)
    {
        if ($this->cacherEnabled) {
            $this->cacher->set($key, $blob, $this->cacherTtl);
        }
    }

    protected function getShieldBlobFetched($repo, $service, $key)
    {
        $url = $this->getApp()['s.gen']->getRepoServiceUrl($key, $service, $repo);

        if (false === $blob = file_get_contents($url)) {
            return false;
        }

        return $blob;
    }
}

/* EOF */
