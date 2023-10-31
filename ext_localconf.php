<?php

if (!defined('TYPO3')) {
    die();
}

(static function (): void {

    //allowLanguageSynchronizationHook manipulates l10n_state
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \WebVision\WvDeepltranslate\Hooks\AllowLanguageSynchronizationHook::class;

    //hook for translate content
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processTranslateToClass']['deepl']
        = \WebVision\WvDeepltranslate\Hooks\TranslateHook::class;

    //xclass localizationcontroller for localizeRecords() and process() action
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\Page\LocalizationController::class] = [
        'className' => \WebVision\WvDeepltranslate\Override\LocalizationController::class,
    ];

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('container')) {
        //xclass CommandMapPostProcessingHook for translating contents within containers
        if (class_exists(\B13\Container\Hooks\Datahandler\CommandMapPostProcessingHook::class)) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\B13\Container\Hooks\Datahandler\CommandMapPostProcessingHook::class] = [
                'className' => \WebVision\WvDeepltranslate\Override\CommandMapPostProcessingHook::class,
            ];
        }
    }


    //add caching for DeepL API supported Languages
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['wvdeepltranslate']
        ??= [];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['wvdeepltranslate']['backend']
        ??= \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class;
})();
