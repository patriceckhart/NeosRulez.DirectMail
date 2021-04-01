<?php
namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Pelago\Emogrifier\CssInliner;

/**
 *
 * @Flow\Scope("singleton")
 */
class CssToInlineService {

    /**
     * @param string $html
     * @return string
     */
    public function execute(string $html):string
    {
        $css = file_get_contents(constant('FLOW_PATH_ROOT') . 'Web/_Resources/Static/Packages/NeosRulez.DirectMail/Styles/email.css');
        $visualHtml = CssInliner::fromHtml($html)->inlineCss($css)->renderBodyContent();
        return $visualHtml;
    }


}
