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
 * Defines the contract any worker must follow
 *
 * A worker is a unit of work to be performed by a daemonized PHP process
 */
interface WorkerInterface
{
    /**
     * Execute the unit of work
     *
     * @return void
     */
    public function execute();
}
