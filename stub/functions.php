<?php

/*
 * This file is part of the `src-run/web-app-v1` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace {

    $shortLinks = function (array $startUris = [], array $links = null, array $aliases = null) use ($app) {
        if (false === (count($links) > 0)) {
            return;
        }

        foreach ($startUris as $uri) {
            foreach ($links as $k => $r) {
                $app->get('/'.$uri.'/'.$k, function () use ($app, $r) {
                    return \SR\Request\RequestHandler::returnRedirect($r, $app);
                });

                if (false === (count($aliases) > 0) || false === ($a = array_search($k, $aliases, true))) {
                    continue;
                }

                $app->get('/'.$uri.'/'.$a, function () use ($app, $r) {
                    return \SR\Request\RequestHandler::returnRedirect($r, $app);
                });
            }
        }
    };

}

/* EOF */
