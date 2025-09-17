<?php
namespace NeosRulez\DirectMail\Command;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use NeosRulez\DirectMail\Domain\Model\Job;
use NeosRulez\DirectMail\Domain\Repository\JobRepository;
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
     * @Flow\Inject
     * @var JobRepository
     */
    protected $jobRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Process the queue
     *
     * @return void
     */
    public function processCommand(): void
    {
        if ($this->preventMultipleJobExecution) {
            if (!$this->temporaryJobExist()) {
                $this->handleTemporaryJob();
                $this->outputLine("\n" .'Start processing the queue ...' . "\n");
                $result = $this->dispatchService->execute();
                $this->handleTemporaryJob(true);
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
    private function handleTemporaryJob(bool $remove = false): void
    {
        if (!$remove) {
            $this->jobRepository->add((new Job()));
            $this->persistenceManager->persistAll();
        } else {
            if ($this->temporaryJobExist()) {
                $this->jobRepository->removeAll();
                $this->persistenceManager->persistAll();
            }
        }
    }

    /**
     * @return bool
     */
    private function temporaryJobExist(): bool
    {
        if ($this->jobRepository->findAll()->count() > 0) {
            return true;
        }
        return false;
    }

}
