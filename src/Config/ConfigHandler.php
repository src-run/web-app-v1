<?php

/*
 * This file is part of the `src-run/web-app-v1` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Config;

use Eloquent\Lcs\LcsSolver;
use SR\Model\AppAwareModel;

/**
 * Class ConfigHandler.
 */
class ConfigHandler
{
    use AppAwareModel;

    /**
     * @param string $search
     *
     * @return null|string
     */
    public function getClosestCollectionMatch($search, $collection)
    {
        if (false === (count($collection) > 0)) {
            return null;
        }

        $collectionSortedByLcs = $this->getCollectionSortedByLcs($search, $collection);
        $collectionTopByLcs = $this->getCollectionTopByLcs($collectionSortedByLcs);

        if (false === (count($collectionTopByLcs) > 0)) {
            return null;
        }

        $collectionSelectedByLev = $this->getCollectionSortedByLev($search, $collectionTopByLcs);

        if (null === $collectionSelectedByLev) {
            return null;
        }

        if (array_key_exists($collectionSelectedByLev['key'], $collection)) {
            return $collection[$collectionSelectedByLev['key']];
        }

        return null;
    }

    /**
     * @param string $search
     * @param array  $collection
     *
     * @return array
     */
    public function getCollectionSortedByLcs($search, array $collection = [])
    {
        $solver = new LcsSolver();

        array_walk($collection, function (&$value, $key) use ($search, $solver) {
            $value = [
                'lcs' => $solver->longestCommonSubsequence(str_split($value), str_split($search)),
                'val' => $value,
                'key' => $key,
            ];
        });

        uasort($collection, function (&$a, $b) {
            $aChars = str_split($a['val'], 1);
            $bChars = str_split($b['val'], 1);

            if ($aChars === $bChars) {
                return 0;
            }

            return (count($a['lcs']) < count($b['lcs'])) ? -1 : 1;
        });

        return (array) $collection;
    }

    /**
     * @param array $collection
     *
     * @return array
     */
    public function getCollectionTopByLcs(array $collection = [])
    {
        if (false === (count($collection) > 0)) {
            return [];
        }

        $collection = array_values($collection);
        $highest = 0;
        $total = count($collection) - 1;
        $last = $total;

        for ($i = $total; $i > 0; --$i) {
            if (count($collection[$i]['lcs']) < $highest) {
                break;
            }

            $highest = count($collection[$i]['lcs']);
            $last = $i;
        }

        return (array) @array_splice($collection, $last);
    }

    /**
     * @param string $search
     * @param array  $collection
     */
    public function getCollectionSortedByLev($search, array $collection = [])
    {
        $shortest = -1;
        $closest = null;
        $collection = array_values(array_map(function ($value) {
            $value['lwr'] = strtolower($value['val']);

            return $value;
        }, $collection));

        foreach ($collection as $item) {
            $lev = levenshtein($search, $item['lwr']);

            if ($lev == 0) {
                $closest = $item;
                $shortest = 0;

                break;
            }

            if ($lev <= $shortest || $shortest < 0) {
                $closest = $item;
                $shortest = $lev;
            }
        }

        if ($shortest > 20) {
            return null;
        }

        return $closest;
    }
}

/* EOF */
