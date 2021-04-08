<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;

class NewsletterController extends ActionController
{

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\CssToInlineService
     */
    protected $cssToInlineService;

    /**
     * @param string $base64Uri
     * @return string
     */
    public function indexAction($base64Uri)
    {
        $uri = base64_decode($base64Uri);
        $html = file_get_contents($uri);
        $cssToInlineView = $this->cssToInlineService->execute($html);
        $view = '<meta name="viewport" content="width=device-width, initial-scale=1.0" /><body style="margin:0;padding:0;"></body>' . $cssToInlineView . '<img src="http://dev.newsletter.dockyard.local/tracking/9b167d97-1943-4f6d-8f91-92df9b06ed14/8264a0d8-2e83-4fa4-8990-822a5516d4f5/opened/0" style="width:1px;height:1px" />';
        return $view;
    }

}
