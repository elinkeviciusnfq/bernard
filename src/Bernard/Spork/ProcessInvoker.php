<?php

namespace Bernard\Spork;

use Bernard\ServiceResolver\Invoker;
use Bernard\Spork\Exception\ProcessException;
use Spork\Fork;
use Spork\ProcessManager;

/**
 * Wraps a Invoker object and forks out when executing the service. This
 * will help on memory. If memory is exceeded it will kill the fork and not
 * the master process.
 *
 * @package Bernard
 */
class ProcessInvoker extends Invoker
{
    protected $invoker;
    protected $spork;

    /**
     * @param ProcessManager $manager
     * @param Invoker        $invoker
     */
    public function __construct(ProcessManager $manager, Invoker $invoker)
    {
        $this->invoker = $invoker;
        $this->spork = $manager;
    }

    /**
     * {@inheritDoc}
     */
    public function invoke()
    {
        $invoker = $this->invoker;

        $fork = $this->spork->fork(function () use ($invoker) {
            $invoker->invoke();
        });

        $fork->wait();
        $fork->fail(array($this, 'fail'));
    }

    /**
     * Throws an Exception based on the $error array given by Spork\ProcessManager.
     * It hides some information but the trade of are still in the positive.
     *
     * @param  Fork                  $fork
     * @throws ForkingLogicException
     */
    public function fail(Fork $fork)
    {
        list($class, $message, $file, $line, $code) = $fork->getError();

        throw new ProcessException($class, $message, $file, $line, $code);
    }
}
