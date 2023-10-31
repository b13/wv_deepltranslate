<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Utility;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use WebVision\WvDeepltranslate\Exception\LanguageIsoCodeNotFoundException;
use WebVision\WvDeepltranslate\Exception\LanguageRecordNotFoundException;
use WebVision\WvDeepltranslate\Service\LanguageService;

class DeeplBackendUtility
{
    private static string $apiKey = '';

    /**
     * @deprecated
     */
    private static string $apiUrl = '';

    /**
     * @deprecated
     */
    private static string $googleApiKey = '';

    /**
     * @deprecated
     */
    private static string $googleApiUrl = '';

    private static string $deeplFormality = 'default';

    private static bool $configurationLoaded = false;

    /**
     * @var array{uid: int, title: string}|array<empty>
     */
    protected static array $currentPage;

    /**
     * @return string
     */
    public static function getApiKey(): string
    {
        if (!self::$configurationLoaded) {
            self::loadConfiguration();
        }
        return self::$apiKey;
    }

    /**
     * @return string
     */
    public static function getApiUrl(): string
    {
        if (!self::$configurationLoaded) {
            self::loadConfiguration();
        }
        return self::$apiUrl;
    }

    /**
     * @return string
     */
    public static function getGoogleApiKey(): string
    {
        if (!self::$configurationLoaded) {
            self::loadConfiguration();
        }
        return self::$googleApiKey;
    }

    /**
     * @return string
     */
    public static function getGoogleApiUrl(): string
    {
        if (!self::$configurationLoaded) {
            self::loadConfiguration();
        }
        return self::$googleApiUrl;
    }

    /**
     * @return string
     */
    public static function getDeeplFormality(): string
    {
        if (!self::$configurationLoaded) {
            self::loadConfiguration();
        }
        return self::$deeplFormality;
    }

    public static function isDeeplApiKeySet(): bool
    {
        if (!self::$configurationLoaded) {
            self::loadConfiguration();
        }

        return (bool)self::$apiKey;
    }

    public static function loadConfiguration(): void
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('wv_deepltranslate');
        self::$apiKey = $extensionConfiguration['apiKey'];
        self::$deeplFormality = $extensionConfiguration['deeplFormality'];
        self::$apiUrl = $extensionConfiguration['apiUrl'];
        self::$googleApiUrl = $extensionConfiguration['googleapiUrl'];
        self::$googleApiKey = $extensionConfiguration['googleapiKey'];

        self::$configurationLoaded = true;
    }

    public static function buildBackendRoute(string $route, array $parameters): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($route, $parameters);
    }

    public static function buildTranslateDropdown(
        $siteLanguages,
        $id,
        $requestUri
    ): string {
        $availableTranslations = [];
        foreach ($siteLanguages as $siteLanguage) {
            if (
                $siteLanguage->getLanguageId() === 0
                || $siteLanguage->getLanguageId() === -1
            ) {
                continue;
            }
            $availableTranslations[$siteLanguage->getLanguageId()] = $siteLanguage->getTitle();
        }
        // Then, subtract the languages which are already on the page:
        $localizationParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];
        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(
                GeneralUtility::makeInstance(
                    WorkspaceRestriction::class,
                    (int)self::getBackendUserAuthentication()->workspace
                )
            );
        $statement = $queryBuilder->select('uid', $languageField)
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $localizationParentField,
                    $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
                )
            )
            ->execute();
        while ($pageTranslation = $statement->fetch()) {
            unset($availableTranslations[(int)$pageTranslation[$languageField]]);
        }
        // If any languages are left, make selector:
        if (!empty($availableTranslations)) {
            $output = '';
            foreach ($availableTranslations as $languageUid => $languageTitle) {
                // check if language can be translated with DeepL
                // otherwise continue to next
                if (!DeeplBackendUtility::checkCanBeTranslated($id, $languageUid)) {
                    continue;
                }
                // Build localize command URL to DataHandler (tce_db)
                // which redirects to FormEngine (record_edit)
                // which, when finished editing should return back to the current page (returnUrl)
                $parameters = [
                    'justLocalized' => 'pages:' . $id . ':' . $languageUid,
                    'returnUrl' => $requestUri,
                ];
                $redirectUrl = self::buildBackendRoute('record_edit', $parameters);
                $params = [];
                $params['redirect'] = $redirectUrl;
                $params['cmd']['pages'][$id]['localize'] = $languageUid;
                $params['cmd']['localization']['custom']['mode'] = 'deepl';
                $targetUrl = self::buildBackendRoute('tce_db', $params);
                $output .= '<option value="' . htmlspecialchars($targetUrl) . '">' . htmlspecialchars($languageTitle) . '</option>';
            }
            if ($output !== '') {
                $output = sprintf(
                    '<option value="">%s</option>%s',
                    htmlspecialchars((string)LocalizationUtility::translate('backend.label', 'wv_deepltranslate')),
                    $output
                );
            }

            return $output;
        }
        return '';
    }

    public static function checkCanBeTranslated(int $pageId, int $languageId): bool
    {
        $languageService = GeneralUtility::makeInstance(LanguageService::class);
        $site = $languageService->getCurrentSite('pages', $pageId);
        if ($site === null) {
            return false;
        }
        try {
            $languageService->getSourceLanguage($site['site']);
        } catch (LanguageIsoCodeNotFoundException $e) {
            return false;
        }
        try {
            $languageService->getTargetLanguage($site['site'], $languageId);
        } catch (LanguageIsoCodeNotFoundException|LanguageRecordNotFoundException $e) {
            return false;
        }
        return true;
    }


    private static function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
