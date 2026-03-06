<?php

namespace NeosRulez\DirectMail\Domain\Repository;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use NeosRulez\DirectMail\Domain\Model\Recipient;
use NeosRulez\DirectMail\Domain\Model\RecipientList;

/**
 * @Flow\Scope("singleton")
 */
class RecipientRepository extends Repository
{

    /**
     * @param RecipientList $recipientList
     * @return int
     */
    public function countByRecipientList(RecipientList $recipientList): int
    {
        $query = $this->persistenceManager->createQueryForType(Recipient::class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->contains('recipientlist', $recipientList)
            )
        )->execute();
        $count = $result ? count($result) : 0;
        return $count;
    }

    /**
     * @param RecipientList $recipientList
     * @return int
     */
    public function countActiveByRecipientList(\NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList): int
    {
        $query = $this->persistenceManager->createQueryForType(Recipient::class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->contains('recipientlist', $recipientList),
                $query->equals('active', 1)
            )
        )->execute();
        $count = $result ? count($result) : 0;
        return $count;
    }

    /**
     * @param array $exceptEmailList
     * @return QueryResultInterface
     */
    public function findByActiveAndImportedExcept(array $exceptEmailList = []): QueryResultInterface
    {
        $query = $this->createQuery();

        $constraints = array();

        $constraints[] = $query->equals('active', true);
        $constraints[] = $query->equals('importedViaApi', true);
        $constraints[] = $query->logicalNot($query->in('email', $exceptEmailList));

        $query = $query->matching($query->logicalAnd($constraints));

        return $query->execute();
    }

    /**
     * @param RecipientList $recipientList
     * @return QueryResultInterface
     */
    public function findByRecipientList(RecipientList $recipientList): QueryResultInterface
    {
        $query = $this->persistenceManager->createQueryForType(Recipient::class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->contains('recipientlist', $recipientList)
            )
        )->execute();
        return $result;
    }

    /**
     * @param RecipientList $recipientList
     * @return QueryResultInterface
     */
    public function findActiveByRecipientList(RecipientList $recipientList): QueryResultInterface
    {
        $query = $this->persistenceManager->createQueryForType(Recipient::class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->contains('recipientlist', $recipientList),
                $query->equals('active', true)
            )
        )->execute();
        return $result;
    }

    /**
     * @param string $identifier
     * @return Recipient|null
     */
    public function findRecipientByIdentifier(string $identifier): ?Recipient
    {
        $query = $this->persistenceManager->createQueryForType(Recipient::class);
        $result = $query->matching($query->equals('Persistence_Object_Identifier', $identifier))->execute()->getFirst();
        return $result;
    }

    /**
     * @param string $email
     * @return Recipient|null
     */
    public function findOneRecipientByMail(string $email): ?Recipient
    {
        $query = $this->persistenceManager->createQueryForType(Recipient::class);
        $result = $query->matching($query->equals('email', $email))->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute()->getFirst();
        return $result;
    }

    /**
     * @param string $email
     * @return QueryResultInterface
     */
    public function findRecipientsByMail(string $email): QueryResultInterface
    {
        $query = $this->persistenceManager->createQueryForType(Recipient::class);
        $result = $query->matching($query->equals('email', $email))->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute();
        return $result;
    }

    /**
     * @param string $searchstring
     * @return QueryResultInterface
     */
    public function findBySearchstring(string $searchstring): QueryResultInterface
    {
        $query = $this->persistenceManager->createQueryForType(Recipient::class);
        $result = $query->matching(
            $query->logicalOr(
                $query->like('firstname', '%' . $searchstring . '%'),
                $query->like('lastname', '%' . $searchstring . '%'),
                $query->like('email', '%' . $searchstring . '%'),
            )
        )->execute();
        return $result;
    }

    /**
     * @return QueryResultInterface
     */
    public function findInactiveRecipients(): QueryResultInterface
    {
        $query = $this->persistenceManager->createQueryForType(Recipient::class);
        $result = $query->matching($query->equals('active', 0))->setOrderings([
            'created' => QueryInterface::ORDER_ASCENDING,
        ])->execute();
        return $result;
    }

    /**
     * @param RecipientList $recipientList
     * @param string $searchstring
     * @return QueryResultInterface
     */
    public function findByRecipientListAndSearchstring(RecipientList $recipientList, string $searchstring): QueryResultInterface
    {
        $query = $this->persistenceManager->createQueryForType(Recipient::class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->contains('recipientlist', $recipientList),
                $query->logicalOr(
                    $query->like('firstname', '%' . $searchstring . '%'),
                    $query->like('lastname', '%' . $searchstring . '%'),
                    $query->like('email', '%' . $searchstring . '%'),
                ),
            )
        )->execute();
        return $result;
    }
}
