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
     * @ORM\Column(options={"default" : 0}, nullable=true)
     * @var bool
     */
    protected $importedViaApi = false;

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
     * @return bool
     */
    public function getImportedViaApi()
    {
        return $this->importedViaApi;
    }

    /**
     * @param bool $importedViaApi
     * @return void
     */
    public function setImportedViaApi($importedViaApi)
    {
        $this->importedViaApi = $importedViaApi;
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
     * @Flow\Validate(type="EmailAddress")
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
     * @Flow\Validate(type="NotEmpty")
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
     * @param RecipientList $recipientlist
     * @return bool
     */
    public function hasRecipientlist($recipientlist)
    {
        return $this->recipientlist->contains($recipientlist);
    }

    /**
     * @param RecipientList $recipientlist
     * @return void
     */
    public function removeRecipientlist($recipientlist)
    {
        $this->recipientlist->removeElement($recipientlist);
    }

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $language;

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return void
     */
    public function setLanguage($language)
    {
        $this->language = $language;
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
