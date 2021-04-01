<?php
namespace NeosRulez\DirectMail\Command;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

/**
 * @Flow\Scope("singleton")
 */
class QueueCommandController extends CommandController {

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\DispatchService
     */
    protected $dispatchService;


    /**
     * Process the queue
     *
     * @return void
     */
    public function processCommand() {
        $this->outputLine("\n" .'Start processing the queue ...' . "\n");
        $result = $this->dispatchService->execute();
        $this->outputLine($result);
        $this->outputLine("\n" . 'The queue has been processed.'. "\n");
    }

}
