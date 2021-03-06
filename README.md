orkestra-daemon
===============

[![Build Status](https://travis-ci.org/orkestra/orkestra-daemon.png?branch=master)](https://travis-ci.org/orkestra/orkestra-daemon)

Daemonize your PHP scripts to accomplish work in the background.

Note: This library requires that you install the Process Control and POSIX extensions.


Installation
------------

The easiest way to add orkestra-common to your project is using composer.

Add orkestra-common to your `composer.json` file:

``` json
{
    "require": {
        "orkestra/daemon": "dev-master"
    }
}
```

Then run `composer install` or `composer update`.


Usage
-----

Spawning a new worker process using `pcntl_exec`:

``` php
<?php

require __DIR__ . '/vendor/autoload.php';

use Orkestra\Daemon\Daemon;
use Orkestra\Daemon\Worker\PcntlWorker;

$daemon = new Daemon();
$daemon->addWorker(new PcntlWorker('/path/to/executable', array('--arg=value')));

$daemon->execute();
```


Spawning a worker processing using a Symfony Process:

``` php
<?php

require __DIR__ . '/vendor/autoload.php';

use Orkestra\Daemon\Daemon;
use Orkestra\Daemon\Worker\ProcessWorker;
use Symfony\Component\Process\Process;

$process = new Process('/path/to/executable --arg=value');

$daemon = new Daemon();
$daemon->addWorker(new ProcessWorker($process));

$daemon->execute();
```
