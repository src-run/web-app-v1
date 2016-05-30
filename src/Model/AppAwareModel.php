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

/**
 * Class AppAwareModel
 */
trait AppAwareModel
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     *
     * @return $this
     */
    public function setApp(Application $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @param string $param
     *
     * @return mixed
     */
    public function getAppParam($param)
    {
        return $this->app[$param];
    }
}

/* EOF */
