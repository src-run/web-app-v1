<?php

/*
 * This file is part of the `src-run/web-app-v1` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Config;

use Symfony\Component\Yaml;

/**
 * Class ConfigHandler.
 */
class ConfigHandlerYaml extends ConfigHandler
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
     * Load file contents.
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
     * Read and parse yaml file contents.
     *
     * @return $this
     */
    public function parseYamlToConfig()
    {
        $this->parser = new Yaml\Parser();
        $this->yaml = $this->parser->parse($this->fileContents);

        return $this;
    }

    /**
     * Get YAML array contents.
     *
     * @return array
     */
    public function getYamlArray()
    {
        return (array) $this->yaml;
    }

    /**
     * @param string ...$keys
     *
     * @return array|null
     */
    public function getValueForKeyPath(...$keys)
    {
        $interm = $this->yaml;

        foreach ((array) $keys as $k) {
            if (false === array_key_exists($k, (array) $interm)) {
                return null;
            }

            $interm = $interm[$k];
        }

        return $interm;
    }
}

/* EOF */
