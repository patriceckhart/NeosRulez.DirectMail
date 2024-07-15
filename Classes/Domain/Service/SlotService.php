<?php
namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
class SlotService
{

    /**
     * @Flow\InjectConfiguration(package="NeosRulez.DirectMail", path="slots.addRecipientToQueue")
     * @var array
     */
    protected $addRecipientToQueueSlots;

    /**
     * @Flow\InjectConfiguration(package="NeosRulez.DirectMail", path="slots.processQueueRecipients")
     * @var array
     */
    protected $processQueueRecipientsSlots;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct(ObjectManagerInterface $objectManager) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param mixed $arguments
     * @return bool
     */
    public function addRecipientToQueue(mixed $arguments): bool
    {
        foreach ($this->addRecipientToQueueSlots as $addRecipientToQueueSlot) {
            $slotClass = $this->objectManager->get($addRecipientToQueueSlot['class']);
            if($slotClass->execute($arguments) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param mixed $arguments
     * @return bool
     */
    public function processQueueRecipients(mixed $arguments): bool
    {
        foreach ($this->processQueueRecipientsSlots as $processQueueRecipientsSlot) {
            $slotClass = $this->objectManager->get($processQueueRecipientsSlot['class']);
            if($slotClass->execute($arguments) === false) {
                return false;
            }
        }
        return true;
    }

}
