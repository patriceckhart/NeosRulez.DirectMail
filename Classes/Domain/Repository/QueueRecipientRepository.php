<?php

namespace NeosRulez\DirectMail\Domain\Repository;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use NeosRulez\DirectMail\Domain\Model\Queue;
use NeosRulez\DirectMail\Domain\Model\QueueRecipient;

/**
 * @Flow\Scope("singleton")
 */
class QueueRecipientRepository extends Repository
{

    /**
     * @return QueryResultInterface
     */
    public function findOpenQueueRecipients($queue): QueryResultInterface
    {
        $query = $this->persistenceManager->createQueryForType(QueueRecipient::class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->equals('queue', $queue),
                $query->equals('sent', true)
            )
        )->execute();
        return $result;
    }

    /**
     * @return QueryResultInterface
     */
    public function findByQueueAndNotSent(Queue $queue): QueryResultInterface
    {
        $query = $this->createQuery();
        return $query->matching(
            $query->logicalAnd(
                $query->equals('queue', $queue),
                $query->equals('sent', false)
            )
        )->execute();
    }

    /**
     * @return QueryResultInterface
     */
    public function findByQueueAndSent(Queue $queue): QueryResultInterface
    {
        $query = $this->createQuery();
        return $query->matching(
            $query->logicalAnd(
                $query->equals('queue', $queue),
                $query->equals('sent', true)
            )
        )->execute();
    }
}
