<?php

/*
 * This file is part of the Scr.be Application.
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Scribe\Composer;

/**
 * Class Script.
 */
class Script
{
    /**
     * Function called on Composer install.
     */
    public static function install()
    {
        self::assignFilesystemPermissions();
    }

    /**
     * Function called on Composer update.
     */
    public static function update()
    {
        self::assignFilesystemPermissions();
    }

    /**
     * Assign proper permissions to the cache, log, and console filesystem items.
     */
    public static function assignFilesystemPermissions()
    {
        if (is_dir($cacheDir = 'resources/cache')) {
            chmod($cacheDir, 0777);
        }

        if (is_dir($logDir = 'resources/log')) {
            chmod($logDir, 0777);
        }

        if (file_exists($consoleFile = 'console')) {
            chmod($consoleFile, 0500);
        }
    }
}

/* EOF */
