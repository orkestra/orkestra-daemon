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

use Orkestra\Daemon\Tests\RunkitEnabledTestCase;
use Orkestra\Daemon\Worker\PcntlWorker;

/**
 * Tests the functionality provided by the PcntlWorker
 *
 * This unit tests requires a bit of magic to work. Both ext/runkit and
 * ext/test_helpers must be installed for this test to work.
 *
 * @group orkestra
 * @group daemon
 * @group worker
 */
class PcntlWorkerTest extends RunkitEnabledTestCase
{
    public static $lastWorker;

    public static function __pcntl_exec_overload($worker, $args)
    {
        static::$lastWorker = array($worker, $args);
    }

    public function testExecute()
    {
        $resetPcntlExec = $this->redefineFunction('pcntl_exec', '$worker,$args', __CLASS__ . '::__pcntl_exec_overload($worker, $args);');

        $worker = new PcntlWorker('test.sh', array('--help'));
        $worker->execute();

        $this->assertEquals(array('test.sh', array('--help')), static::$lastWorker);

        $resetPcntlExec();
        static::$lastWorker = null;
    }
}
