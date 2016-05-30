<?php

/*
 * This file is part of the `src-run/web-app` project.
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
        if (is_dir($cacheDir = __DIR__.'../../resources/cache')) {
            chmod($cacheDir, 0777);
        }

        if (is_dir($logDir = __DIR__.'../../resources/log')) {
            chmod($logDir, 0777);
        }

        if (file_exists($consoleFile = __DIR__.'../../bin/web-app')) {
            chmod($consoleFile, 0500);
        }
    }
}

/* EOF */
