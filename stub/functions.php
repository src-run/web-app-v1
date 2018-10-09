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

    $shortLinksRoot = function (array $links = [], array $aliases = []) use ($app) {
        if (true === empty($links)) {
            return;
        }

        foreach ($links as $key => $url) {
            $app->get(sprintf('/%s', $key), function () use ($app, $url) {
                return \SR\Request\RequestHandler::returnRedirect($url, $app);
            });

            if (true !== empty($refs = buildReferencingAliasGroups($aliases, $key))) {
                foreach ($refs as $refKey => $refUrl) {
                    $app->get(sprintf('/%s', $refKey), function () use ($app, $refUrl) {
                        return \SR\Request\RequestHandler::returnRedirect($refUrl, $app);
                    });
                }
            }
        }
    };


    function buildReferencingAliasGroups(array $aliases, string $urlKey): array
    {
        $direct = array_filter($aliases, function (string $refKey) use ($urlKey): bool {
            return $refKey === $urlKey;
        });

        $others = [];

        foreach ($direct as $dKey => $dRef) {
            if (null !== ($found = findSelfReferencingAliases($aliases, $dKey, $dRef)) && true !== empty($found)) {
               $all = array_merge($all ?? $direct, $found);
            }
        }

        return $all ?? $direct;
    }

    function findSelfReferencingAliases(array $aliases, string $dKey, string $dRef): array
    {
        $refs = [];

        foreach ($aliases as $aKey => $aRef) {
            if ($dKey !== $aRef) {
                continue;
            }

            $refs[$aKey] = $aRef;

            if (null !== ($found = findSelfReferencingAliases($aliases, $aKey, $aRef)) && true !== empty($found)) {
                $refs = array_merge($refs, $found);
            }
        }

        return $refs;
    }

}

/* EOF */
