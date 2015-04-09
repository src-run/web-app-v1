<?php

/*
 * This file is part of the Scr.be Application.
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Scribe\Request;

use Silex\Application;

/**
 * Class RequestHandler
 */
class RequestHandler
{
    public static function returnRedirect($to, Application $app, $code = 302)
    {
        return $app->redirect($to, $code);
    }
}

/* EOF */
