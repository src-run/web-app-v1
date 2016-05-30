<?php

/*
 * This file is part of the `src-run/web-app` project
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Model;

use Silex\Application;
use SR\Config\ConfigHandler;
use SR\Config\ConfigHandlerDevServices;

/**
 * Class CfgAwareModel
 */
trait CfgAwareModel
{
    /**
     * @return Application
     */
    abstract public function getApp();

    /**
     * Get config handler
     *
     * @return ConfigHandler
     */
    public function getCfg($handler)
    {
        return $this->getApp()['s.'.$handler];
    }

    /**
     * @return ConfigHandlerDevServices
     */
    public function getCsv()
    {
        return $this->getCfg('csv');
    }
}

/* EOF */
