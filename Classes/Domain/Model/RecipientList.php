<?php
namespace NeosRulez\DirectMail\Domain\Model;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Persistence\PersistenceManagerInterface;

/**
 * @Flow\Entity
 */
class RecipientList
{

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */
    protected $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @var \DateTime
     */
    protected $created;

    public function __construct() {
        $this->created = new \DateTime();
    }

    /**
     * @return string
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->persistenceManager->getIdentifierByObject($this);
    }

}
