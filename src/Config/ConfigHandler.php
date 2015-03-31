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
use Eloquent\Lcs\LcsSolver;

/**
 * Class Manager
 *
 * @package Scribe
 */
class ConfigHandler
{
    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var string
     */
    protected $fileContents;

    /**
     * @var Yaml\Parser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $yaml;

    /**
     * Load file contents
     *
     * @param string $filePath
     *
     * @throws \LogicException
     *
     * @return $this
     */
    public function loadFileContents($filePath)
    {
        $this->filePath = realpath($filePath);

        if (false === file_exists($this->filePath)) {
            throw new \LogicException(
                sprintf(
                    'Could not find required config file at %s.',
                    $this->filePath
                )
            );
        }

        $this->fileContents = @file_get_contents($this->filePath);

        if (false === $this->fileContents || true === empty($this->fileContents)) {
            throw new \LogicException(
                sprintf(
                    'Could not read contents of required config file at %s.',
                    $this->filePath
                )
            );
        }

        return $this;
    }

    /**
     * Read and parse yaml file contents
     *
     * @return $this
     */
    public function parseYamlToConfig()
    {
        $this->parser = new Yaml\Parser;
        $this->yaml = $this->parser->parse($this->fileContents);

        return $this;
    }

    /**
     * Get YAML array contents
     *
     * @return array
     */
    public function getYamlArray()
    {
        return (array)$this->yaml;
    }

    public function getValueForKeyPath(...$keys)
    {
        $interm = $this->yaml;

        foreach ((array)$keys as $k) {
            if (false === array_key_exists($k, (array)$interm)) {
                return null;
            }

            $interm = $interm[$k];
        }

        return $interm;
    }

    /**
     * Validate the result of a config value search
     *
     * @param mixed $result
     *
     * @return bool
     */
    public function validateResult($result)
    {
        if (null === $result) {
            return false;
        }

        return true;
    }

    public function getClosestProjectMatch($search)
    {
        $projects  = array_values((array) $this->getValueForKeyPath('projects_search'));

        if (false === (count($projects) > 0)) {
            return null;
        }

        $projectsSortedByLcs = $this->getProjectsSortedByLcs($search, $projects);
        $projectsTopByLcs    = $this->getProjectsTopByLcs($projectsSortedByLcs);

        if (false === (count($projectsTopByLcs) > 0)) {
            return null;
        }

        $projectSelectedByLev = $this->getProjectsSortedByLev($search, $projectsTopByLcs);

        if (null === $projectSelectedByLev) {
            return null;
        }

        if (array_key_exists($projectSelectedByLev['key'], $projects)) {
            return $projects[$projectSelectedByLev['key']];
        }

        return null;
    }

    public function getProjectsSortedByLcs($search, array $projects = [])
    {
        $solver = new LcsSolver;

        array_walk($projects, function(&$value, $key) use ($search, $solver) {
            $value = [
                'lcs' => $solver->longestCommonSubsequence(str_split($value), str_split($search)),
                'val' => $value,
                'key' => $key,
            ];
        });

        uasort($projects, function(&$a, $b) {
            $aChars = str_split($a['val'], 1);
            $bChars = str_split($b['val'], 1);

            if ($aChars === $bChars) {
                return 0;
            }

            return (count($a['lcs']) < count($b['lcs'])) ? -1 : 1;
        });

        return (array) $projects;
    }

    public function getProjectsTopByLcs(array $projects = [])
    {
        if (false === (count($projects) > 0)) {
            return [];
        }

        $projects = array_values($projects);
        $highest  = 0;
        $total    = count($projects) - 1;
        $last     = $total;

        for ($i = $total; $i > 0; $i--) {
            if (count($projects[$i]['lcs']) < $highest) {
                break;
            }

            $highest = count($projects[$i]['lcs']);
            $last    = $i;
        }

        return (array) @array_splice($projects, $last);
    }

    public function getProjectsSortedByLev($search, array $projects = [])
    {
        $shortest = -1;
        $closest  = null;
        $projects = array_values(array_map(function($value) {
            $value['lwr'] = strtolower($value['val']);

            return $value;
        }, $projects));

        foreach ($projects as $p) {
            $lev = levenshtein($search, $p['lwr']);

            if ($lev == 0) {
                $closest  = $p;
                $shortest = 0;

                break;
            }

            if ($lev <= $shortest || $shortest < 0) {
                $closest  = $p;
                $shortest = $lev;
            }
        }

        if ($shortest > 12) {
            return null;
        }

        return $closest;
    }
}

/* EOF */
