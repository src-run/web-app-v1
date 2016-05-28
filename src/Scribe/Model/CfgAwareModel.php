<?php

/*
 * This file is part of the Scr.be Application
 *
 * (c) Scribe Inc. <source@scribe.software>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Scribe\Model;

use Silex\Application;
use Scribe\Config\ConfigHandler;
use Scribe\Config\ConfigHandlerDevServices;

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
