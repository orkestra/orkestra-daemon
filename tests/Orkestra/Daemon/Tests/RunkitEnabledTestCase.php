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
abstract class RunkitEnabledTestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!extension_loaded('test_helpers')) {
            $this->markTestSkipped('These unit tests require ext/test_helpers. See https://github.com/sebastianbergmann/php-test-helpers for more info.');
        } elseif (!extension_loaded('runkit')) {
            $this->markTestSkipped('These unit tests require ext/runkit. See https://github.com/zenovich/runkit/ for more info.');
        }
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
