<?php

/*
 * This file is part of the Orkestra Daemon package.
 *
 * Copyright (c) Orkestra Community
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Orkestra\Daemon\Tests;

use Orkestra\Daemon\Daemon;

/**
 * Tests the functionality provided by the Daemon
 *
 * This unit tests requires a bit of magic to work. Both ext/runkit and
 * ext/test_helpers must be installed for this test to work.
 *
 * @group orkestra
 * @group daemon
 */
class DaemonTest extends RunkitEnabledTestCase
{
    /**
     * Contains call counts
     *
     * @var array
     */
    public static $calls;

    /**
     * Contains callable that will reset the usleep function back to normal
     *
     * @var callable
     */
    private $resetUsleep;

    protected function setUp()
    {
        parent::setUp();

        set_exit_overload(array(__CLASS__, '__exit_overload'));

        $this->resetUsleep = $this->redefineFunction('usleep', '', __CLASS__ . '::__usleep_overload();');
        $this->resetCallCounts();
    }

    protected function tearDown()
    {
        // These checks are required because tearDown is called even when the test is skipped
        if (function_exists('unset_exit_overload')) {
            unset_exit_overload();
        }

        if ($this->resetUsleep) {
            call_user_func($this->resetUsleep);
        }
    }

    public function testDaemon()
    {
        $resetPcntlFork    = $this->redefineFunction('pcntl_fork',    '',              __CLASS__ . '::__pcntl_fork_overload();');
        $resetPcntlWaitpid = $this->redefineFunction('pcntl_waitpid', '',              __CLASS__ . '::__pcntl_waitpid_overload();');

        $firstWorker = $this->getMockForAbstractClass('Orkestra\Daemon\Worker\WorkerInterface');
        $firstWorker->expects($this->once())
            ->method('execute')
            ->will($this->returnCallback(__CLASS__ . '::__workerExecute'));

        $secondWorker = $this->getMockForAbstractClass('Orkestra\Daemon\Worker\WorkerInterface');
        $secondWorker->expects($this->once())
            ->method('execute')
            ->will($this->returnCallback(__CLASS__ . '::__workerExecute'));

        $daemon = new Daemon();
        $daemon->addWorker($firstWorker);
        $daemon->addWorker($secondWorker);

        $daemon->init();

        $this->assertNotEmpty($daemon->getPid());

        $daemon->execute();

        $this->assertEquals(1, static::$calls['exit']);
        $this->assertEquals(1, static::$calls['usleep']);
        $this->assertEquals(3, static::$calls['pcntl_fork']);
        $this->assertEquals(2, static::$calls['pcntl_waitpid']);
        $this->assertEquals(2, static::$calls['workers']);

        $resetPcntlFork();
        $resetPcntlWaitpid();
    }

    public static function __exit_overload()
    {
        static::$calls['exit']++;
        return;
    }

    public static function __usleep_overload()
    {
        static::$calls['usleep']++;
        return;
    }

    public static function __pcntl_fork_overload()
    {
        static::$calls['pcntl_fork']++;
        return 1;
    }

    public static function __pcntl_waitpid_overload()
    {
        static::$calls['pcntl_waitpid']++;
        return 0;
    }

    public static function __workerExecute()
    {
        static::$calls['workers']++;
    }

    protected function resetCallCounts()
    {
        static::$calls = array(
            'usleep' => 0,
            'pcntl_fork' => 0,
            'pcntl_waitpid' => 0,
            'exit' => 0,
            'workers' => 0
        );
    }
}
