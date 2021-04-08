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
                $uniqueResult[$tracking->getRecipient()->getEmail()] = $tracking;
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
        $result = $query->matching(
            $query->logicalAnd(
                $query->equals('queue', $queue),
                $query->equals('recipient', $recipient)
            )
        )->execute();
        $count = $result ? count($result) : 0;
        return $count;
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
