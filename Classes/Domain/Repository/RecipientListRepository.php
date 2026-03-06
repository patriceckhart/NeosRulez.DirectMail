<?php

namespace NeosRulez\DirectMail\Domain\Repository;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\Repository;
use NeosRulez\DirectMail\Domain\Model\RecipientList;

/**
 * @Flow\Scope("singleton")
 */
class RecipientListRepository extends Repository
{

    protected $defaultOrderings = [
        'name' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * @param string $identifier
     * @return RecipientList|null
     */
    public function findRecipientListByIdentifier(string $identifier): ?RecipientList
    {
        $query = $this->persistenceManager->createQueryForType(RecipientList::class);
        $result = $query->matching($query->equals('Persistence_Object_Identifier', $identifier))->execute()->getFirst();
        return $result;
    }
}
