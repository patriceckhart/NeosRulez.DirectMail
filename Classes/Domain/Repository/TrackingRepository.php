<?php

namespace NeosRulez\DirectMail\Domain\Repository;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use NeosRulez\DirectMail\Domain\Model\Queue;
use NeosRulez\DirectMail\Domain\Model\Recipient;
use NeosRulez\DirectMail\Domain\Model\Tracking;

/**
 * @Flow\Scope("singleton")
 */
class TrackingRepository extends Repository
{

    /**
     * @param Queue $queue
     * @return integer
     */
    public function countByQueue(Queue $queue): int
    {
        $query = $this->persistenceManager->createQueryForType(Tracking::class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->equals('queue', $queue)
            )
        )->execute();

        $uniqueResult = [];
        foreach ($result as $tracking) {
            if ($tracking->getRecipient() !== null) {
                $uniqueResult[$tracking->getRecipient()->getEmail()] = $tracking;
            }
        }

        $count = $uniqueResult ? count($uniqueResult) : 0;
        return $count;
    }

    /**
     * @param Queue $queue
     * @param Recipient $recipient
     * @return integer
     */
    public function countByQueueAndRecipient(Queue $queue, Recipient $recipient): int
    {
        $query = $this->persistenceManager->createQueryForType(Tracking::class);
        return $query->matching(
            $query->logicalAnd(
                $query->equals('queue', $queue),
                $query->equals('recipient', $recipient),
                $query->equals('action', 'opened')
            )
        )->count();
    }

    /**
     * @param Queue $queue
     * @return QueryResultInterface
     */
    public function findOpenedByQueue(Queue $queue): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('queue', $queue),
                $query->equals('action', 'opened')
            )
        );
        return $query->setOrderings([
            'created' => QueryInterface::ORDER_ASCENDING,
        ])->execute();
    }

    /**
     * @param Queue $queue
     * @return QueryResultInterface
     */
    public function findByQueue(Queue $queue): QueryResultInterface
    {
        $query = $this->persistenceManager->createQueryForType(Tracking::class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->equals('queue', $queue)
            )
        )->execute();
        return $result;
    }
}
