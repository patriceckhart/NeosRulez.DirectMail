<?php
namespace NeosRulez\DirectMail\Command;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use NeosRulez\DirectMail\Domain\Service\DispatchService;

/**
 * @Flow\Scope("singleton")
 */
class QueueCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var DispatchService
     */
    protected $dispatchService;

    /**
     * Process the queue
     *
     * @return void
     */
    public function processCommand(): void
    {
        $this->outputLine("\n" .'Start processing the queue ...' . "\n");
        $result = $this->dispatchService->execute();
        $this->outputLine($result);
        $this->outputLine("\n" . 'The queue has been processed.'. "\n");
    }

}
