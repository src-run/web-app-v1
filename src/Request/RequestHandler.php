<?php
/*
 * This file is part of the Scr.be Website
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Scribe\Request;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class RequestHandler
 *
 * @package ScriBe
 */
class RequestHandler
{
    public static function returnSubRequest($to, Application $app, $method = 'GET')
    {
        $subRequest = Request::create($app, $to, $method);

        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    public static function returnRedirect($to, Application $app, $code = 302)
    {
        return $app->redirect($to, $code);
    }
}

/* EOF */
