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
class RecipientRepository extends Repository {

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList
     * @return integer
     */
    public function countByRecipientList(\NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList): int
    {
        $class = '\NeosRulez\DirectMail\Domain\Model\Recipient';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->contains('recipientlist', $recipientList)
            )
        )->execute();
        $count = $result ? count($result) : 0;
        return $count;
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList
     * @return integer
     */
    public function countActiveByRecipientList(\NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList): int
    {
        $class = '\NeosRulez\DirectMail\Domain\Model\Recipient';
        $query = $this->persistenceManager->createQueryForType($class);
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
     * @return \Neos\Flow\Persistence\QueryResultInterface
     */
    public function findByActiveAndImportedExcept($exceptEmailList = array())
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
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList
     * @return void
     */
    public function findByRecipientList(\NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList)
    {
        $class = '\NeosRulez\DirectMail\Domain\Model\Recipient';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching(
            $query->logicalAnd(
                $query->contains('recipientlist', $recipientList)
            )
        )->execute();
        return $result;
    }

    /**
     * @param string $identifier
     * @return void
     */
    public function findRecipientByIdentifier(string $identifier) {
        $class = '\NeosRulez\DirectMail\Domain\Model\Recipient';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching($query->equals('Persistence_Object_Identifier', $identifier))->execute()->getFirst();
        return $result;
    }

    /**
     * @param string $email
     * @return void
     */
    public function findOneRecipientByMail(string $email) {
        $class = '\NeosRulez\DirectMail\Domain\Model\Recipient';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching($query->equals('email', $email))->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute()->getFirst();
        return $result;
    }

    /**
     * @param string $email
     * @return void
     */
    public function findRecipientsByMail(string $email) {
        $class = '\NeosRulez\DirectMail\Domain\Model\Recipient';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching($query->equals('email', $email))->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute();
        return $result;
    }

    /**
     * @param string $searchstring
     * @return void
     */
    public function findBySearchstring(string $searchstring) {
        $class = '\NeosRulez\DirectMail\Domain\Model\Recipient';
        $query = $this->persistenceManager->createQueryForType($class);
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
     * @return void
     */
    public function findInactiveRecipients() {
        $class = '\NeosRulez\DirectMail\Domain\Model\Recipient';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching($query->equals('active', 0))->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute();
        return $result;
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList
     * @param string $searchstring
     * @return void
     */
    public function findByRecipientListAndSearchstring(\NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList, string $searchstring)
    {
        $class = '\NeosRulez\DirectMail\Domain\Model\Recipient';
        $query = $this->persistenceManager->createQueryForType($class);
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
