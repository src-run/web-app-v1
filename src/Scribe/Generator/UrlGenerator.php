<?php
/*
 * This file is part of the Scr.be Website
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Scribe\Generator;

use Scribe\Model\AppAwareModel;
use Scribe\Model\CfgAwareModel;
use Silex\Application;
use Symfony\Component\Yaml;

/**
 * Class ConfigURLGen
 *
 * @package Scribe
 */
class UrlGenerator
{
    use AppAwareModel,
        CfgAwareModel;

    /**
     * Get the preferred schema or provide the default
     *
     * @return string
     */
    public function getPreferredSchema()
    {
        return (string) ($this->getCsv()->getValueForKeyPath('general', 'preferred_scheme') ?: "https") . ':';
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
            $this->getCsv()->getValueForKeyPath('redirects', 'users', 'profile_types', 'github');

        if (null === $gitHubUrl) {
            throw new \LogicException('Could not find GitHub URL base path.');
        }

        return str_replace('%username%', $username, $gitHubUrl);
    }

    /**
     * Return GitHub profile path from given username
     *
     * @param string $project
     *
     * @return string|null
     */
    public function getGitHubScribeProjectUrl($project)
    {
        $gitHubUrl = $this->getPreferredSchema().':'.
            $this->getCsv()->getValueForKeyPath('redirects', 'services', 'github');

        if (null === $gitHubUrl) {
            return null;
        }

        return $this->renderedFinalizedUrl(
            $gitHubUrl,
            ['%bundle-name%', $project]
        );
    }

    /**
     * Return the service URL for the provided username
     *
     * @param string        $user
     * @param string        $service
     *
     * @return string|null
     */
    public function getUserServiceUrl($user, $service = 'github')
    {
        if (false === ($userKey = array_search($user, $this->app['s.cgh']->userAlias)) &&
            false === ($userKey = array_search($user, $this->app['s.cgh']->userLogin))) {
            return null;
        }

        if (null !== ($serviceAlias = $this->getCsv()->getValueForKeyPath('redirects', 'users', 'services', $service, 'alias'))) {
            $service = $serviceAlias;
        }

        if ($service === 'github' && array_key_exists('html_url', $this->app['s.cgh']->users[$userKey])) {
            return $this->app['s.cgh']->users[$userKey]['html_url'];
        }

        if (null === ($serviceUrl = $this->getCsv()->getValueForKeyPath('redirects', 'users', 'services', $service, 'url'))) {
            return null;
        }

        if (null !== ($userAlias = $this->getCsv()->getValueForKeyPath('redirects', 'users', 'services', $service, 'usermap', $user))) {
            $user = $userAlias;
        }

        return $this->renderedFinalizedUrl(
            $serviceUrl,
            ['%user%', $user]
        );
    }

    /**
     * @param int    $key
     * @param string $service
     *
     * @return null|string
     */
    public function getRepoServiceUrl($key, $service)
    {
        if ($service === 'stash' || $service === 'sensiolabs') {
            return null;
        }

        if ($service === 'travis') {
            $typeKey = ($this->app['s.cgh']->repositories[$key]['private'] ? 'private' : 'public');

            if (null === ($serviceUrl = $this->getCsv()->getValueForKeyPath('redirects', 'services', $service, $typeKey))) {
                return null;
            }
        } else {
            if (null === ($serviceUrl = $this->getCsv()->getValueForKeyPath('redirects', 'services', $service))) {
                return null;
            }
        }

        return $this->renderedFinalizedUrl(
            $serviceUrl,
            ['%bundle-name%', $this->app['s.cgh']->repositoryNames[$key]]
        );
    }

    /**
     * Render a final URL, concatinating our default schema and performing any string replacements
     * per the passed replacement instruction arrays.
     *
     * @param string  $url
     * @param array[] $replacements
     *
     * @return string
     */
    protected function renderedFinalizedUrl($url, array ...$replacements)
    {
        return (string)
            $this->getPreferredSchema() .
            $this->performStringReplacementInstructions($url, $replacements)
        ;
    }

    /**
     * Perform string replacements based on the passed array of instruction sets. An instruction must be
     * an array of string values, the last of which is the name of the function to call, with the former
     * passed as arguments to the function. You can optionally pass an array with only two values and the
     * default function call will be made (str_replace). The string itself is always passed as the last
     * argument to the called function.
     *
     * @param string  $string
     * @param array[] $instructions
     * @param string  $defaultCall
     *
     * @return string|null
     */
    protected function performStringReplacementInstructions($string, array $instructions = [], $defaultCall = 'str_replace')
    {
        if (null === $string || empty($string)) {
            return '';
        }

        foreach ($instructions as $inst) {

            if (!is_array($inst) || ($count = count($inst)) < 2) {
                continue;
            }

            $call = ($count === 2) ? $defaultCall : array_pop($inst);

            if (false === is_string($call) || false === function_exists($call)) {
                continue;
            }

            $string = call_user_func_array($call, array_merge($inst, (array) $string));
        }

        return (string) $string;
    }
}

/* EOF */
