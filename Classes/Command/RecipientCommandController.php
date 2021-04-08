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
class RecipientCommandController extends CommandController {

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\MergeService
     */
    protected $mergeService;


    /**
     * Merging recipients
     *
     * @return void
     */
    public function mergeCommand() {
        $this->outputLine("\n" .'Start merging duplicate recipients ...' . "\n");
        $this->mergeService->execute();
        $this->outputLine('The merging process is complete.'. "\n");
    }

}
