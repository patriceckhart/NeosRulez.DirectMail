<?php
namespace NeosRulez\DirectMail\Domain\Repository;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class QueueRecipientRepository extends Repository {

    /**
     * @return void
     */
    public function findOpenQueueRecipients($queue)
    {
        $class = '\NeosRulez\DirectMail\Domain\Model\QueueRecipient';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->equals('queue', $queue),
                $query->equals('sent', true)
            )
        )->execute();
        return $result;
    }

}
