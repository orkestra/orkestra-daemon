<?php

/*
 * This file is part of the Orkestra Daemon package.
 *
 * Copyright (c) Orkestra Community
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Orkestra\Daemon\Tests\Worker;

use Orkestra\Daemon\Worker\ProcessWorker;

/**
 * Tests the functionality provided by the ProcessWorker
 *
 * @group orkestra
 * @group daemon
 * @group worker
 */
class ProcessWorkerTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $that = $this;

        $process = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();
        $process->expects($this->once())
            ->method('run');

        $worker = new ProcessWorker($process);
        $worker->execute();
    }
}
