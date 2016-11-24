<?php

/*
 * This file is part of the `src-run/web-app-v1` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Sami\Sami;
use Symfony\Component\Finder\Finder;

$projectRootPath = realpath(__DIR__.DIRECTORY_SEPARATOR);

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in($projectRootPath.DIRECTORY_SEPARATOR.'src')
    ->in($projectRootPath.DIRECTORY_SEPARATOR.'web')
;

return new Sami($iterator, [
    'theme' => 'default',
    'title' => 'src-run/web-app',
    'build_dir' => $projectRootPath.DIRECTORY_SEPARATOR.'.build'.DIRECTORY_SEPARATOR.'docs',
    'cache_dir' => $projectRootPath.DIRECTORY_SEPARATOR.'.build'.DIRECTORY_SEPARATOR.'tmp',
    'default_opened_level' => 2,
]);

/* EOF */
