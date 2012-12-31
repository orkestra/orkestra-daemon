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
 * Creates a daemon for accomplishing tasks in the background
 */
class Daemon
{
    /**
     * @var int The pid of the parent process
     */
    protected $pid;

    /**
     * @var array An array of child pids
     */
    protected $pids = array();

    /**
     * @var array An array of executable worker scripts
     */
    protected $workers = array();

    /**
     * @var int The maximum number of child workers to spawn
     */
    protected $maxChildren = 1;

    /**
     * @var int Number of milliseconds to sleep
     */
    protected $sleepTime = 1000;

    /**
     * Constructor
     *
     * @throws \RuntimeException if the PCNTL or POSIX extensions are not loaded
     */
    public function __construct()
    {
        if (!extension_loaded('pcntl')) {
            throw new \RuntimeException('The Daemon class relies on the PCNTL extension, which is not available on your PHP installation.');
        } elseif (!extension_loaded('posix')) {
            throw new \RuntimeException('The Daemon class relies on the POSIX extension, which is not available on your PHP installation.');
        }
    }

    /**
     * Adds a worker
     *
     * A worker must be a valid executable
     *
     * @param string $worker    Path to 'work'
     * @param array  $arguments
     */
    public function addWorker($worker, $arguments = array())
    {
        $this->workers[] = array($worker, (array) $arguments);
    }

    /**
     * Initializes the daemon, spawning a "child" process and exiting the parent process
     *
     * The new "child" process is now considered the parent process, running the Daemon as
     * a background task
     */
    public function init()
    {
        $pid = pcntl_fork();
        if ($pid) {
            // The parent process must exit
            exit;
        }

        $this->pid = getmypid();
    }

    /**
     * Executes the configured workers
     *
     * New child processes will be spawned until the limit is reached. As child processes
     * finish on their work, the daemon will spawn new processes until no more work is available
     */
    public function execute()
    {
        if (!$this->pid) {
            $this->init();
        }

        declare(ticks = 1);
        pcntl_signal(SIGTERM, array($this, 'handleSignal'));
        pcntl_signal(SIGHUP,  array($this, 'handleSignal'));
        pcntl_signal(SIGINT,  array($this, 'handleSignal'));
        pcntl_signal(SIGUSR1, array($this, 'handleSignal'));
        pcntl_signal(SIGUSR2, array($this, 'handleSignal'));

        do {
            // Spawn a new worker if necessary
            if ($this->hasMoreWork() && count($this->pids) < $this->maxChildren) {
                $this->spawnWorker();
            }

            $this->cleanUpFinishedWorkers();

            // Exit the daemon if no more work is needed
            if (count($this->workers) <= 0 && count($this->pids) <= 0) {
                $this->terminate(SIGTERM);

                // Necessary because the unit test overrides exit
                return;
            }

            usleep($this->sleepTime);
        } while (true);
    }

    /**
     * Handles a control signal sent by the system
     *
     * @param int $signal
     */
    public function handleSignal($signal)
    {
        switch ($signal) {
            case SIGTERM:
            case SIGHUP:
            case SIGINT:
                $this->terminate($signal);

                break;
            case SIGUSR1:
            case SIGUSR2:
                $this->handleUserDefinedSignal($signal);

                break;
        }
    }

    /**
     * Terminates the Daemon
     *
     * This method should perform any cleanup, killing all child processes
     *
     * @param int $signal
     */
    protected function terminate($signal)
    {
        foreach ($this->pids as $pid) {
            posix_kill($pid, $signal);
        }

        foreach ($this->pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        exit;
    }

    /**
     * Handles user defined signals
     *
     * This method is called when SIGUSR1 or SIGUSR2 are sent to the Daemon
     *
     * @param int $signal
     */
    protected function handleUserDefinedSignal($signal)
    {
    }

    /**
     * Returns true if there is work to be done
     *
     * @return bool
     */
    protected function hasMoreWork()
    {
        return count($this->workers) > 0;
    }

    /**
     * Gets the next available worker
     *
     * @return array|null Array of command, arguments or null if no more workers
     */
    protected function getNextWorker()
    {
        return $this->hasMoreWork() ? array_shift($this->workers) : null;
    }

    /**
     * Spawns a new worker process
     *
     * @return void
     */
    protected function spawnWorker()
    {
        $pid = pcntl_fork();
        list($worker, $arguments) = $this->getNextWorker();

        if (!$pid) {
            // New worker process
            pcntl_exec($worker, $arguments);
        } else {
            $this->pids[] = $pid;
        }
    }

    /**
     * Cleans up any workers that have exited on their own
     *
     * @return void
     */
    protected function cleanUpFinishedWorkers()
    {
        do {
            $status = 0;
            $pid = pcntl_waitpid(-1, $status, WNOHANG);

            if ($pid <= 0) {
                break;
            }

            unset($this->pids[array_search($pid, $this->pids)]);
        } while (true);
    }

    /**
     * Gets the parent's PID
     *
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Sets the maximum number of children the daemon can spawn at any given time
     *
     * @param int $max
     */
    public function setMaxChildren($max)
    {
        $this->maxChildren = (int) $max;
    }

    /**
     * Sets the number of milliseconds the daemon will sleep between loops
     *
     * @param int $sleepTime
     */
    public function setSleepTime($sleepTime)
    {
        $this->sleepTime = (int) $sleepTime;
    }

    /**
     * Gets the maximum number of children the daemon can spawn at any given time
     *
     * @return int
     */
    public function getMaxChildren()
    {
        return $this->maxChildren;
    }

    /**
     * Gets the number of milliseconds the daemon will sleep between loops
     *
     * @return int
     */
    public function getSleepTime()
    {
        return $this->sleepTime;
    }
}
