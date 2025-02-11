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

    const temporaryPathAndFileName = FLOW_PATH_TEMPORARY_BASE . '/directmail-queue';

    /**
     * @Flow\InjectConfiguration(package="NeosRulez.DirectMail", path="queue.preventMultipleJobExecution")
     * @var bool
     */
    protected $preventMultipleJobExecution;

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
        if($this->preventMultipleJobExecution) {
            if(!$this->temporaryJobFileExist()) {
                $this->handleTemporaryJobFile();
                $this->outputLine("\n" .'Start processing the queue ...' . "\n");
                $result = $this->dispatchService->execute();
                $this->handleTemporaryJobFile(true);
                $this->outputLine($result);
                $this->outputLine("\n" . 'The queue has been processed.'. "\n");
            } else {
                $this->outputLine("\n" .'Queue is being processed ...' . "\n");
            }
        } else {
            $this->outputLine("\n" .'Start processing the queue ...' . "\n");
            $result = $this->dispatchService->execute();
            $this->outputLine($result);
            $this->outputLine("\n" . 'The queue has been processed.'. "\n");
        }
    }

    /**
     * @param bool $remove
     * @return void
     */
    private function handleTemporaryJobFile(bool $remove = false): void
    {
        if(!$remove) {
            file_put_contents(self::temporaryPathAndFileName, self::temporaryPathAndFileName);
        } else {
            if($this->temporaryJobFileExist()) {
                unlink(self::temporaryPathAndFileName);
            }
        }
    }

    /**
     * @return bool
     */
    private function temporaryJobFileExist(): bool
    {
        if(file_exists(self::temporaryPathAndFileName)) {
            return true;
        }
        return false;
    }

}
