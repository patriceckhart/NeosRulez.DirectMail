<?php
namespace NeosRulez\DirectMail\Domain\Model;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Import
{

    /**
     * @ORM\Column(nullable=true)
     * @ORM\OneToOne(cascade={"persist"})
     * @var \Neos\Flow\ResourceManagement\PersistentResource
     */
    protected $file;

    /**
     * @return string
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * @param string $file
     * @return void
     */
    public function setFile($file) {
        $this->file = $file;
    }

    /**
     * @var \NeosRulez\DirectMail\Domain\Model\RecipientList
     * @ORM\ManyToOne(cascade={"persist"})
     * @ORM\Column(unique=false)
     */
    protected $recipientlist;

    /**
     * @return \NeosRulez\DirectMail\Domain\Model\RecipientList
     */
    public function getRecipientlist()
    {
        return $this->recipientlist;
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $recipientlist
     */
    public function setRecipientlist($recipientlist)
    {
        $this->recipientlist = $recipientlist;
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

}
