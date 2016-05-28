<?php

namespace {

    $shortlinks = function(array $startUris = [], array $links = null, array $aliases = null) use ($app)
    {
        if (false === (count($links) > 0)) {
            return;
        }

        foreach ($startUris as $uri) {
            foreach ($links as $k => $r) {
                $app->get('/'.$uri.'/'.$k, function () use ($app, $r) {
                    return \Scribe\Request\RequestHandler::returnRedirect($r, $app);
                });

                if (false === (count($aliases) > 0) || false === ($a = array_search($k, $aliases, true))) {
                    continue;
                }

                $app->get('/'.$uri.'/'.$a, function () use ($app, $r) {
                    return \Scribe\Request\RequestHandler::returnRedirect($r, $app);
                });
            }
        }
    };

}

/* EOF */
