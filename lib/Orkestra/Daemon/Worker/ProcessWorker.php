<?php

/*
 * This file is part of the Orkestra Daemon package.
 *
 * Copyright (c) Orkestra Community
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Orkestra\Daemon\Worker;

use Symfony\Component\Process\Process;

/**
 * Spawns a process using a Process instance
 */
class ProcessWorker implements WorkerInterface
{
    /**
     * @var \Symfony\Component\Process\Process
     */
    private $process;

    /**
     * @var callable|null
     */
    private $callback;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Process\Process $process   A Process instance
     * @param callable|null                      $callback  Optional callback, passed to Process->run()
     */
    public function __construct(Process $process, $callback = null)
    {
        $this->process  = $process;
        $this->callback = $callback;
    }

    /**
     * Sets the callback
     *
     * This callback is passed to Process->run() when the Worker is executed
     *
     * @param callable $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    /**
     * Execute the unit of work
     *
     * @return void
     */
    public function execute()
    {
        $this->process->run($this->callback);
    }
}
