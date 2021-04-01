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
class Recipient
{

    /**
     * @var integer
     */
    protected $gender;

    /**
     * @return integer
     */
    public function getGender()
    {
        return $this->gender;
    }
    
    /**
     * @param integer $gender
     * @return void
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $customsalutation;

    /**
     * @return string
     */
    public function getCustomsalutation()
    {
        return $this->customsalutation;
    }

    /**
     * @param string $customsalutation
     * @return void
     */
    public function setCustomsalutation($customsalutation)
    {
        $this->customsalutation = $customsalutation;
    }

    /**
     * @var string
     */
    protected $firstname;

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     * @return void
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @var string
     */
    protected $lastname;

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     * @return void
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */
    protected $email;

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @var boolean
     */
    protected $active = false;

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     * @return void
     */
    public function setActive($active)
    {
        $this->active = $active;
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
