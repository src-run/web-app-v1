<?php
/*
 * This file is part of the Scr.be Website
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Scribe\Error;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ErrorHandler
 *
 * @package ScriBe
 */
class ErrorHandler
{
    /**
     * Setup the enviornment variable on the app
     *
     * @param Application $app
     * @param bool        $enabled
     *
     * @return $this
     */
    public function setDebug(Application $app, $enabled = false)
    {
        $app['debug'] = (bool) $enabled;

        return $this;
    }

    /**
     * Attach error handlers to application
     *
     * @param Application $app
     *
     * @return $this
     */
    public function attach(Application $app)
    {
        $this
            ->attachHttp($app)
            ->attachLogic($app)
        ;

        return $this;
    }

    /**
     * Attach http error-code handler
     *
     * @param Application $app
     *
     * @return $this
     */
    public function attachHttp(Application $app)
    {
        $app->error(function (\Exception $e, $code) {
            switch ($code) {
                case 500:
                    $message = 'An internal server error occurred. We\'ll try harder next time.';
                    break;
                case 400:
                    $message = 'This appears to be an unauthorized location for you; naughty.';
                    break;
                default:
                    $message = 'It seems something did not pan out as expected. You\'r guess is as good as mine as to what went wrong.';
            }

            return new Response($message);
        });

        return $this;
    }

    /**
     * Attach Logic exception handler
     *
     * @param Application $app
     *
     * @return $this
     */
    public function attachLogic(Application $app)
    {
        $app->error(function (\LogicException $e, $code) {
            return new Response(sprintf('An internal logic error [%n]: %s.', $code, $e->getMessage()));
        });

        return $this;
    }
}

/* EOF */
