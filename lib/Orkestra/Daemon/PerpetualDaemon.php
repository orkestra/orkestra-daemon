<?php

/*
 * This file is part of the Orkestra Daemon package.
 *
 * Copyright (c) Orkestra Community
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Orkestra\Daemon;

/**
 * A Daemon that only wants one worker and will always run that worker
 */
class PerpetualDaemon extends Daemon
{
    /**
     * Executes the configured workers
     *
     * @see Orkestra\Common\Daemon\Daemon::execute
     *
     * @throws \RuntimeException if no workers are assigned
     */
    public function execute()
    {
        if (empty($this->workers)) {
            throw new \RuntimeException('The PerpetualDaemon must be assigned work before it can be executed');
        }

        parent::execute();
    }

    /**
     * Adds a worker
     *
     * The PerpetualDaemon may only be assigned a single worker
     *
     * @param $worker
     * @param array $arguments
     *
     * @see Orkestra\Common\Daemon\Daemon::addWorker
     *
     * @throws \RuntimeException if a worker is already assigned
     */
    public function addWorker($worker, $arguments = array())
    {
        if (!empty($this->workers)) {
            throw new \RuntimeException('The PerpetualDaemon may only be assigned one worker');
        }

        parent::addWorker($worker, $arguments);
    }

    /**
     * @return bool
     */
    protected function hasMoreWork()
    {
        return true;
    }

    /**
     * @return array|null
     */
    protected function getNextWorker()
    {
        return $this->workers[0];
    }
}
