<?php
defined('TYPO3_MODE') or die();

call_user_func(
    function($extKey)
    {
		if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
			/**
			 * Registers a Backend Module
			 */
			// GDlib is a requirement for the Font Maker module
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
				// SJBR\SrFreecap\Domain\Model\Font uses declare(encoding='ISO-8859-2') which, since PHP 5.4, requires zend.multibyte to be set to On'.
				// However, this has to be set in php.ini, .htaccess, httpd.conf or .user.ini, because the setting zend.multibyte is of type PHP_INI_PERDIR
				// See http://php.net/manual/en/configuration.changes.modes.php
				if (ini_get('zend.multibyte')) {
					\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
						'SJBR.' . $extKey,
						// Make module a submodule of 'tools'
						'tools',
						// Submodule key
						'FontMaker',
						// Position
						'',
						// An array holding the controller-action combinations that are accessible
						[
							'FontMaker' => 'new,create'
						],
						[
							'access' => 'user,group',
							'icon' => 'EXT:sr_freecap/Resources/Public/Icons/Extension.svg',
							'labels' => 'LLL:EXT:sr_freecap/Resources/Private/Language/locallang_mod.xlf'
						]
					);
					// Add module configuration setup
					\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($extKey, 'setup', '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $extKey . '/Configuration/TypoScript/FontMaker/setup.typoscript">');
				}
			}
		}
	},
	'sr_freecap'
);