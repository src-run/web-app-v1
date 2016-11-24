<?php

/*
 * This file is part of the `src-run/web-app-v1` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Composer;

/**
 * Class Script.
 */
class Script
{
    /**
     * @var array[]
     */
    const INSTRUCTIONS = [
        'web-app-file' => [
            __DIR__.'/../../bin/web-app', false, 0500,
        ],
        'cache-directory' => [
            __DIR__.'/../../var/cache', true, 0777,
        ],
        'log-directory' => [
            __DIR__.'/../../var/log', true, 0777,
        ],
    ];

    public static function install()
    {
        self::handleInstructionSet();
    }

    public static function update()
    {
        self::handleInstructionSet();
    }

    private static function handleInstructionSet()
    {
        foreach (static::INSTRUCTIONS as $name => $instruction) {
            static::handleInstruction($name, $instruction);
        }
    }

    private static function handleInstruction($index, $instruction)
    {
        if (false === $path = realpath($instruction[0])) {
            $path = $instruction[0];
        }

        $path = str_replace(realpath(__DIR__.'/../../'), '.', $path);

        echo sprintf('> Setting up %s ... ', $path);

        if (substr($index, -4, 4) === 'file') {
            static::handleFile($instruction);
        } elseif (substr($index, -9, 9) === 'directory') {
            static::handleDirectory($instruction);
        }
    }

    private static function handleFile($instruction)
    {
        list($path, , $mod) = $instruction;

        if (!file_exists($path)) {
            echo 'skipping'.PHP_EOL;
            return;
        }

        @chmod($path, $mod);

        echo 'okay'.PHP_EOL;
    }

    private static function handleDirectory($instruction)
    {
        list($path, $create, $mod) = $instruction;

        if (!is_dir($path) && $create) {
            mkdir($path, $mod, true);
            echo 'created'.PHP_EOL;
        } else {
            @chmod($path, $mod);
            echo 'okay'.PHP_EOL;
        }
    }
}

/* EOF */
