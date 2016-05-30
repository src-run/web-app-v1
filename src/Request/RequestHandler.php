<?php

/*
 * This file is part of the `src-run/web-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Request;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RequestHandler
 */
class RequestHandler
{
    /**
     * @param string      $to
     * @param Application $app
     * @code  int         $code
     *
     * @return RedirectResponse
     */
    public static function returnRedirect($to, Application $app, $code = 302)
    {
        return $app->redirect($to, $code);
    }

    /**
     * @param \SimpleXMLElement $xml
     * @param Application       $app
     * @code  int               $code
     *
     * @return Response
     */
    public static function returnXml(\SimpleXMLElement $xml, Application $app, $code = 200)
    {
        return new Response($xml->asXML(), 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
}

/* EOF */
