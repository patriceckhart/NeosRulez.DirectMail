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
class QueueRepository extends Repository {

    /**
     * @return void
     */
    public function findOpenQueues()
    {
        $class = '\NeosRulez\DirectMail\Domain\Model\Queue';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->lessThanOrEqual('send', new \DateTime()),
                $query->equals('sent', 0)
            )
        )->execute();
        return $result;
    }

}
