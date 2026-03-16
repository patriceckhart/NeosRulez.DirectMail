<?php

namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Pelago\Emogrifier\CssInliner;

/**
 *
 * @Flow\Scope("singleton")
 */
class CssToInlineService
{
    /**
     * @Flow\InjectConfiguration(package="NeosRulez.DirectMail", path="css")
     * @var array
     */
    protected $cssSettings;

    /**
     * @param string $html
     * @return string
     */
    public function execute(string $html): string
    {
        $css = file_get_contents(constant('FLOW_PATH_ROOT') . 'Web/_Resources/Static/Packages/NeosRulez.DirectMail/Styles/email.css');
        foreach ($this->cssSettings['additionalFiles'] as $additionalFile) {
            $css .= file_get_contents($additionalFile) ?: '';
        }
        $visualHtml = CssInliner::fromHtml($html)->inlineCss($css)->renderBodyContent();
        return $visualHtml;
    }
}
