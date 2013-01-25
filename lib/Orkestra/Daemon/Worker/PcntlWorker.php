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

/**
 * Spawns a process using pcntl_exec
 */
class PcntlWorker implements WorkerInterface
{
    /**
     * Constructor
     *
     * @param string  $path       The path to the executable
     * @param array   $arguments  Arguments to be passed to the executable
     */
    public function __construct($path, array $arguments = array())
    {
        $this->path      = $path;
        $this->arguments = $arguments;
    }

    /**
     * Execute the unit of work
     *
     * @return void
     */
    public function execute()
    {
        pcntl_exec($this->path, $this->arguments);
    }
}
