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
class DaemonTest extends \PHPUnit_Framework_TestCase
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
        if (!extension_loaded('test_helpers')) {
            $this->markTestSkipped('These unit tests require ext/test_helpers. See https://github.com/sebastianbergmann/php-test-helpers for more info.');
        } elseif (!extension_loaded('runkit')) {
            $this->markTestSkipped('These unit tests require ext/runkit. See https://github.com/zenovich/runkit/ for more info.');
        }

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
        $resetPcntlExec    = $this->redefineFunction('pcntl_exec',    '$worker,$args', __CLASS__ . '::__pcntl_exec_overload($worker, $args);');

        $daemon = new Daemon();
        $daemon->addWorker('first', array('arg' => 'test'));
        $daemon->addWorker('second');

        $daemon->init();

        $this->assertNotEmpty($daemon->getPid());

        $daemon->execute();

        $this->assertEquals(1, static::$calls['exit']);
        $this->assertEquals(1, static::$calls['usleep']);
        $this->assertEquals(3, static::$calls['pcntl_fork']);
        $this->assertEquals(2, static::$calls['pcntl_waitpid']);
        $this->assertEquals(2, static::$calls['pcntl_exec']);

        $this->assertCount(2, static::$calls['workers']);
        $this->assertArrayHasKey('first', static::$calls['workers']);
        $this->assertArrayHasKey('second', static::$calls['workers']);
        $this->assertEquals(array('arg' => 'test'),  static::$calls['workers']['first']);
        $this->assertEquals(array(), static::$calls['workers']['second']);

        $resetPcntlFork();
        $resetPcntlWaitpid();
        $resetPcntlExec();
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

    public static function __pcntl_exec_overload($worker, $args)
    {
        static::$calls['pcntl_exec']++;
        static::$calls['workers'][$worker] = $args;
    }

    protected function resetCallCounts()
    {
        static::$calls = array(
            'usleep' => 0,
            'pcntl_fork' => 0,
            'pcntl_waitpid' => 0,
            'pcntl_exec' => 0,
            'exit' => 0,
            'workers' => array()
        );
    }

    /**
     * Overrides a function, returning a callable that will reset the environment when called
     *
     * @see runkit_function_redefine
     *
     * @param string $funcname
     * @param string $arglist
     * @param string $code
     *
     * @return callable A callable to reset everything back to normal
     */
    protected function redefineFunction($funcname, $arglist, $code)
    {
        runkit_function_copy($funcname, '__backup_' . $funcname);
        runkit_function_redefine($funcname, $arglist, $code);

        return function() use($funcname) {
            runkit_function_remove($funcname);
            runkit_function_copy('__backup_' . $funcname, $funcname);
            runkit_function_remove('__backup_' . $funcname);
        };
    }
}
