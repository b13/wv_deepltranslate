<?php
defined('TYPO3_MODE') || die('Access denied.');

//Temporary variables
$extensionKey = 'wv_deepltranslate';

//icons to icon registry
$iconRegistry         = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$deeplIconIdentifier = 'actions-localize-deepl';
$googleIconIdentifier = 'actions-localize-google';
$iconRegistry->registerIcon(
    $deeplIconIdentifier,
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:wv_deepltranslate/Resources/Public/Icons/' . $deeplIconIdentifier . '.svg']
);

$iconRegistry->registerIcon(
    $googleIconIdentifier,
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:wv_deepltranslate/Resources/Public/Icons/' . $googleIconIdentifier . '.svg']
);

//register backend module
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'WebVision.WvDeepltranslate',
    'Deepl',
    '',
    '',
    [],
    [
        'icon'   => 'EXT:wv_deepltranslate/Resources/Public/Icons/deepl.svg',
        'access' => 'user,group',
        'labels' => 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang.xlf',
    ]
);

$typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
);

if ($typo3VersionArray['version_main'] < 10) {
    $actionsControllerArray = [
        'Settings' => 'index,saveSettings',
    ];
} else {
    $actionsControllerArray = [
        \WebVision\WvDeepltranslate\Controller\SettingsController::class => 'index,saveSettings',
    ];
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'WebVision.WvDeepltranslate',
    'Deepl',
    'Settings',
    '',
    $actionsControllerArray,
    [
        'icon'   => 'EXT:install/Resources/Public/Icons/module-install-settings.svg',
        'access' => 'user,group',
        'labels' => 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_module_settings.xlf',
    ]
);


$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['/typo3/sysext/backend/Resources/Private/Language/locallang_layout.xlf'] = 'EXT:wv_deepltranslate/Resources/Private/Language/locallang.xlf';
