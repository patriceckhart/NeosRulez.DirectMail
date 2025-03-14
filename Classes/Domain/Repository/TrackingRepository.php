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

/**
 * @Flow\Scope("singleton")
 */
class TrackingRepository extends Repository
{

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $queue
     * @return integer
     */
    public function countByQueue(\NeosRulez\DirectMail\Domain\Model\Queue $queue): int
    {
        $class = '\NeosRulez\DirectMail\Domain\Model\Tracking';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->equals('queue', $queue)
            )
        )->execute();
        if($result) {
            $uniqueResult = [];
            foreach ($result as $tracking) {
                if($tracking->getRecipient() !== null) {
                    $uniqueResult[$tracking->getRecipient()->getEmail()] = $tracking;
                }
            }
        }
        $count = $uniqueResult ? count($uniqueResult) : 0;
        return $count;
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $queue
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $recipient
     * @return integer
     */
    public function countByQueueAndRecipient(\NeosRulez\DirectMail\Domain\Model\Queue $queue, \NeosRulez\DirectMail\Domain\Model\Recipient $recipient): int
    {
        $class = '\NeosRulez\DirectMail\Domain\Model\Tracking';
        $query = $this->persistenceManager->createQueryForType($class);
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
        return $query->setOrderings(array('created' => QueryInterface::ORDER_ASCENDING))->execute();
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $queue
     * @return object
     */
    public function findByQueue(\NeosRulez\DirectMail\Domain\Model\Queue $queue): object
    {
        $class = '\NeosRulez\DirectMail\Domain\Model\Tracking';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->equals('queue', $queue)
            )
        )->execute();
        return $result;
    }

}
