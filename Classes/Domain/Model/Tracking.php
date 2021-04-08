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
class Tracking
{

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
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */
    protected $action;

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return void
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */
    protected $description;

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
