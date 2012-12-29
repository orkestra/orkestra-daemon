<?php

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
     * Constructor
     *
     * @throws \RuntimeException if the PCNTL or POSIX extensions are not loaded
     */
    public function __construct()
    {
        if (!function_exists('pcntl_fork')) {
            throw new \RuntimeException('The Daemon class relies on the PCNTL extension, which is not available on your PHP installation.');
        } elseif (!function_exists('posix_kill')) {
            throw new \RuntimeException('The Daemon class relies on the POSIX extension, which is not available on your PHP installation.');
        }
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
        pcntl_signal(SIGHUP, array($this, 'handleSignal'));
        pcntl_signal(SIGINT, array($this, 'handleSignal'));
        pcntl_signal(SIGUSR1, array($this, 'handleSignal'));

        do {
            // Spawn a new worker if necessary
            if ($this->hasMoreWork() && count($this->pids) < $this->maxChildren) {
                $pid = pcntl_fork();
                list($worker, $arguments) = $this->getNextWorker();

                if (!$pid) {
                    // New worker process
                    pcntl_exec($worker, $arguments);
                } else {
                    $this->pids[] = $pid;
                }
            }

            // Clean up any workers that have exited on their own
            do {
                $pid = pcntl_waitpid(-1, $status, WNOHANG);

                if ($pid <= 0)
                    break;

                unset($this->pids[array_search($pid, $this->pids)]);
            } while (true);

            // Exit the daemon if no more work is needed
            if (count($this->workers) <= 0 && count($this->pids) <= 0) {
                $this->handleSignal(SIGTERM);
            }

            sleep(1);
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
                foreach ($this->pids as $pid) {
                    posix_kill($pid, $signal);
                }

                foreach ($this->pids as $pid) {
                    pcntl_waitpid($pid, $status);
                }

                exit;
            case SIGUSR1:
                // echo some kind of status
                printf('I have %s child processes', count($this->pids));
        }
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
     * Gets the parent's PID
     *
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
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
     * Sets the maximum number of children the daemon can spawn at any given time
     *
     * @param int $max
     */
    public function setMaxChildren($max)
    {
        $this->maxChildren = (int) $max;
    }
}
