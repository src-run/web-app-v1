<?php
/*
 * This file is part of the Scr.be Website
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Scribe\Config;

use Symfony\Component\Yaml;

/**
 * Class ConfigURLGen
 *
 * @package Scribe
 */
class ConfigURLGen
{
    /**
     * @var null|ConfigHandler
     */
    protected $cfg;

    /**
     * Optionally sets the config handler instance
     *
     * @param null|ConfigHandler $cfg
     */
    public function __construct(ConfigHandler $cfg = null)
    {
        $this->cfg = $cfg;
    }

    /**
     * Sets the config handler instance
     *
     * @param ConfigHandler $cfg
     */
    public function setConfigHandler(ConfigHandler $cfg)
    {
        $this->cfg = $cfg;
    }

    /**
     * Get the preferred schema or provide the default
     *
     * @return string
     */
    public function getPreferredSchema()
    {
        return (string) ($this->cfg->getValueForKeyPath('general', 'preferred_scheme') ?: "https");
    }

    /**
     * Return GitHub profile path from given username
     *
     * @param string $username
     *
     * @return mixed
     */
    public function getGitHubProfileUrl($username)
    {
        $gitHubUrl = $this->getPreferredSchema().':'.
            $this->cfg->getValueForKeyPath('redirects', 'users', 'profile_types', 'github');

        if (true !==$this->cfg->validateResult($gitHubUrl)) {
            throw new \LogicException('Could not find GitHub URL base path.');
        }

        return str_replace('%username%', $username, $gitHubUrl);
    }

    /**
     * Return GitHub profile path from given username
     *
     * @param string $username
     *
     * @return mixed
     */
    public function getGitHubScribeProjectUrl($project)
    {
        $gitHubUrl = $this->getPreferredSchema().':'.
            $this->cfg->getValueForKeyPath('redirects', 'services', 'github');

        if (true !==$this->cfg->validateResult($gitHubUrl)) {
            throw new \LogicException('Could not find GitHub URL base path.');
        }

        return str_replace('%bundle-name%', $project, $gitHubUrl);
    }
}

/* EOF */
