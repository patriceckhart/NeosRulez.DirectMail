<?php
namespace NeosRulez\DirectMail\Domain\Repository;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class RecipientListRepository extends Repository {

    protected $defaultOrderings = [
        'name' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * @param string $identifier
     * @return void
     */
    public function findRecipientListByIdentifier(string $identifier) {
        $class = '\NeosRulez\DirectMail\Domain\Model\RecipientList';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching($query->equals('Persistence_Object_Identifier', $identifier))->execute()->getFirst();
        return $result;
    }

}
