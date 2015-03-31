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
use Scribe\Error\ErrorHandler;

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
        $this->yaml   = $this->parser->parse($this->fileContents);

        return $this;
    }

    /**
     * Get YAML array contents
     *
     * @return array
     */
    public function getYamlArray()
    {
        return (array) $this->yaml;
    }

    public function getValueForKeyPath(...$keys)
    {
        $interm = $this->yaml;

        foreach ((array)$keys as $k) {
            if (false === array_key_exists($k, $interm)) {
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
}

/* EOF */
