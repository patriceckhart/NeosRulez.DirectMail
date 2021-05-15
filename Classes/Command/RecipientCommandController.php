<?php
namespace NeosRulez\DirectMail\Command;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

/**
 * @Flow\Scope("singleton")
 */
class RecipientCommandController extends CommandController {

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\MergeService
     */
    protected $mergeService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\RecipientRepository
     */
    protected $recipientRepository;


    /**
     * Merging recipients
     *
     * @return void
     */
    public function mergeCommand() {
        $this->outputLine("\n" .'Start merging duplicate recipients ...' . "\n");
        $this->mergeService->execute();
        $this->outputLine('The merging process is complete.'. "\n");
    }

    /**
     * Validate recipient email address
     *
     * @return void
     */
    public function validateCommand() {
        $this->outputLine("\n" .'Start validating email addresses ...' . "\n");
        $recipients = $this->recipientRepository->findAll();
        if($recipients) {
            foreach ($recipients as $recipient) {
                $email = $recipient->getEmail();
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo $email . " is invalid. I'm trying to fix it ...\n";
                    $fixedEmail = str_replace(' ', '', $email);
                    if (filter_var($fixedEmail, FILTER_VALIDATE_EMAIL)) {
                        $recipient->setEmail($fixedEmail);
                        $this->recipientRepository->update($recipient);
                    } else {
                        $this->recipientRepository->remove($recipient);
                    }
                }
            }
        }
        $this->outputLine('The validating process is complete.'. "\n");
    }

}
