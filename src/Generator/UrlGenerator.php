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

use SR\Model\AppAwareModel;
use SR\Model\CfgAwareModel;

/**
 * Class ConfigURLGen.
 */
class UrlGenerator
{
    use AppAwareModel;
    use CfgAwareModel;

    /**
     * Get the preferred schema or provide the default.
     *
     * @return string
     */
    public function getPreferredSchema()
    {
        return (string) ($this->getCsv()->getValueForKeyPath('general', 'preferred_scheme') ?: 'https').':';
    }

    /**
     * Return GitHub profile path from given username.
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
     * Return GitHub profile path from given username.
     *
     * @param string $project
     *
     * @return string|null
     */
    public function getGitHubOrgSrProjectUrl($project)
    {
        $gitHubUrl = $this->getPreferredSchema().':'.
            $this->getCsv()->getValueForKeyPath('redirects', 'services', 'github');

        if (null === $gitHubUrl) {
            return null;
        }

        return $this->renderedFinalizedUrl(
            $gitHubUrl,
            ['%bundle-name%' => $project]
        );
    }

    /**
     * Return the service URL for the provided username.
     *
     * @param string $user
     * @param string $service
     *
     * @return string|null
     */
    public function getUserServiceUrl($user, $service = 'github')
    {
        $user = strtolower($user);

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
            ['%user%' => $user]
        );
    }

    /**
     * @param int    $key
     * @param string $service
     * @param string $repo
     *
     * @return null|string
     */
    public function getExternalRepoServiceUrl($service, $org, $repo, $branch = null)
    {
        if (!$branch) {
            $branch = 'master';
        }

        if (false !== strpos($service, 'packagist')) {
            $repoParts = explode('++++', strtolower(preg_replace('#(?<=\\w)(?=[A-Z])#', '++++$1', $repo)));
            if (false !== $key = array_search($org, $repoParts)) {
                unset($repoParts[$key]);
            }
            $repo = implode('-', $repoParts);
        }

        $serviceUrl = null;
        $paramsUrl = [
            '%org%' => $org,
            '%bundle-name%' => $repo,
            '%branch%' => $branch,
        ];
        $service = [$service];

        if ($service[0] === 'travis') {
            $service[] = 'public';
        }

        if (strpos($repo, ':')) {
            $parts = explode(':', $repo);
            $paramsUrl['%bundle-name%'] = array_shift($parts);
            $paramsUrl['%id%'] = array_shift($parts);
        }

        if (null === $serviceUrl && null === ($serviceUrl = $this->getCsv()->getValueForKeyPath('redirects', 'services', ...$service))) {
            return null;
        }

        return $this->renderedFinalizedUrl($serviceUrl, $paramsUrl);
    }

    /**
     * @param int    $key
     * @param string $service
     * @param string $repo
     *
     * @return null|string
     */
    public function getRepoServiceUrl($key, $service, $repo)
    {
        $paramsUrl = [
            '%org%' => 'src-run',
            '%branch%' => 'master',
        ];
        $serviceUrl = null;

        if ($service === 'travis') {
            $typeKey = ($this->app['s.cgh']->repositories[$key]['private'] ? 'private' : 'public');

            if (null === ($serviceUrl = $this->getCsv()->getValueForKeyPath('redirects', 'services', $service, $typeKey))) {
                return null;
            }
        } elseif ($service === 'codacy_shield') {
            if (null === ($serviceUrl = $this->getCsv()->getValueForKeyPath('redirects', 'services', $service)) ||
                null === ($serviceKey = $this->getCsv()->getValueForKeyPath('projects', $repo, 'codacy_id'))) {
                return null;
            }

            $paramsUrl = array_merge($paramsUrl, [
                '%id%' => $serviceKey,
            ]);
        }

        $repoName = $this->app['s.cgh']->repositoryNames[$key];

        $paramsUrl = array_merge($paramsUrl, [
            '%bundle-name%' => $this->app['s.cgh']->repositoryNames[$key],
        ]);

        if ($service === 'group') {
            $paramsUrl = array_merge($paramsUrl, [
                '%search%' => substr($repoName, 0, strpos($repoName, '-')),
            ]);
        }

        if ($service === 'group_explanation') {
            $groupVideo = $this->getCsv()->getValueForKeyPath('group_explanation', substr($repoName, 0, strpos($repoName, '-')), 'watch');
            $groupSearch = $this->getCsv()->getValueForKeyPath('group_explanation', substr($repoName, 0, strpos($repoName, '-')), 'search');

            if ($groupVideo !== null) {
                $serviceUrl = $this->getCsv()->getValueForKeyPath('redirects', 'services', $service.'_video');
                $paramsUrl = array_merge($paramsUrl, ['%video%' => $groupVideo]);
            } elseif ($groupSearch !== null) {
                $serviceUrl = $this->getCsv()->getValueForKeyPath('redirects', 'services', $service.'_search');
                $paramsUrl = array_merge($paramsUrl, ['%search%' => urlencode($groupSearch)]);
            } else {
                return null;
            }
        }

        if ($service === 'styleci' || $service === 'styleci_shield') {
            if (null !== $styleciId = $this->getCsv()->getValueForKeyPath('projects', $repoName, 'styleci_id')) {
                $paramsUrl = array_merge($paramsUrl, ['%id%' => $styleciId]);
            }
        }

        if (null === $serviceUrl && null === ($serviceUrl = $this->getCsv()->getValueForKeyPath('redirects', 'services', $service))) {
            return null;
        }

        return $this->renderedFinalizedUrl($serviceUrl, $paramsUrl);
    }

    /**
     * Render a final URL, concatinating our default schema and performing any string replacements
     * per the passed replacement instruction arrays.
     *
     * @param string   $url
     * @param string[] $replacements
     *
     * @return string
     */
    protected function renderedFinalizedUrl($url, array $replacements)
    {
        return (string)
            $this->getPreferredSchema().
            $this->performStringReplacementInstructions($url, $replacements)
        ;
    }

    /**
     * @param string  $string
     * @param array[] $instructions
     *
     * @return string|null
     */
    protected function performStringReplacementInstructions($string, array $instructions = [])
    {
        if (null === $string || empty($string)) {
            return '';
        }

        foreach ($instructions as $search => $replace) {
            $string = str_replace($search, $replace, $string);
        }

        return (string) $string;
    }
}

/* EOF */
