<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Listener;

use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Core\Page\PageRenderer;

class ModifyPageLayoutContent
{
    public function __construct(protected PageRenderer $pageRenderer)
    {
    }

    public function __invoke(ModifyPageLayoutContentEvent $e): void
    {
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:wv_deepltranslate/Resources/Private/Language/locallang.xlf');
    }
}
