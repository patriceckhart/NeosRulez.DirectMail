<?php
namespace NeosRulez\DirectMail\Domain\Model;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

/**
 * @Flow\Entity
 */
class Queue
{

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
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */
    protected $nodeuri;

    /**
     * @return string
     */
    public function getNodeuri()
    {
        return $this->nodeuri;
    }

    /**
     * @param string $nodeuri
     * @return void
     */
    public function setNodeuri($nodeuri)
    {
        $this->nodeuri = $nodeuri;
    }

    /**
     * @var \DateTime
     */
    protected $send;

    /**
     * @return \DateTime
     */
    public function getSend()
    {
        return $this->send;
    }

    /**
     * @param \DateTime $send
     * @return void
     */
    public function setSend($send)
    {
        $this->send = $send;
    }

    /**
     * @var boolean
     */
    protected $done = false;

    /**
     * @return boolean
     */
    public function getDone()
    {
        return $this->done;
    }

    /**
     * @param boolean $done
     * @return void
     */
    public function setDone($done)
    {
        $this->done = $done;
    }

    /**
     * @var Collection<\NeosRulez\DirectMail\Domain\Model\RecipientList>
     * @ORM\ManyToMany
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
     * @param Collection $recipientlist
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
