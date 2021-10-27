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
class QueueRecipient
{

    /**
     * @var \NeosRulez\DirectMail\Domain\Model\Recipient
     * @ORM\ManyToOne(cascade={"persist"})
     * @ORM\Column(unique=false)
     */
    protected $recipient;

    /**
     * @return \NeosRulez\DirectMail\Domain\Model\Recipient
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $recipient
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * @var \NeosRulez\DirectMail\Domain\Model\Queue
     * @ORM\ManyToOne(cascade={"persist"})
     * @ORM\Column(unique=false)
     */
    protected $queue;

    /**
     * @return \NeosRulez\DirectMail\Domain\Model\Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $queue
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    /**
     * @var \NeosRulez\DirectMail\Domain\Model\RecipientList
     * @ORM\ManyToOne(cascade={"persist"})
     * @ORM\Column(unique=false)
     */
    protected $recipientList;

    /**
     * @return \NeosRulez\DirectMail\Domain\Model\RecipientList
     */
    public function getRecipientList()
    {
        return $this->recipientList;
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList
     */
    public function setRecipientList($recipientList)
    {
        $this->recipientList = $recipientList;
    }

    /**
     * @var boolean
     */
    protected $sent = false;

    /**
     * @return boolean
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * @param boolean $sent
     * @return void
     */
    public function setSent($sent)
    {
        $this->sent = $sent;
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
