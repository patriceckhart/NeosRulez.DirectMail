<?php

namespace NeosRulez\DirectMail\Domain\Repository;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use NeosRulez\DirectMail\Domain\Model\Queue;
use NeosRulez\DirectMail\Domain\Model\RecipientList;

/**
 * @Flow\Scope("singleton")
 */
class QueueRepository extends Repository
{

    /**
     * @return QueryResultInterface
     */
    public function findOpenQueues(): QueryResultInterface
    {
        $query = $this->persistenceManager->createQueryForType(Queue::class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->lessThanOrEqual('send', new \DateTime()),
                $query->equals('done', false)
            )
        )->execute();
        return $result;
    }

    /**
     * @param string $identifier
     * @return Queue|null
     */
    public function findQueueByIdentifier(string $identifier): ?Queue
    {
        $query = $this->persistenceManager->createQueryForType(Queue::class);
        $result = $query->matching($query->equals('Persistence_Object_Identifier', $identifier))->execute()->getFirst();
        return $result;
    }

    /**
     * @return QueryResultInterface
     */
    public function findQueuesInProgress(): QueryResultInterface
    {
        $query = $this->persistenceManager->createQueryForType(Queue::class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->greaterThan('sent', 0)
            )
        )->execute();
        return $result;
    }

    /**
     * @param RecipientList $recipientList
     * @return QueryResultInterface
     */
    public function findByRecipientList(RecipientList $recipientList): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->contains('recipientlist', $recipientList),
        );
        return $query->execute();
    }
}
