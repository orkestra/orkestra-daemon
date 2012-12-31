<?php

/*
 * Copyright (c) 2012 Orkestra Community
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
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
