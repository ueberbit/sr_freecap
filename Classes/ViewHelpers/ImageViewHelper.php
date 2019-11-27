<?php

namespace SJBR\SrFreecap\ViewHelpers;

/*
 *  Copyright notice
 *
 *  (c) 2013-2018 Stanislas Rolland <typo3(arobas)sjbr.ca>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class ImageViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string Name of the extension this view helper belongs to
     */
    protected $extensionName = 'SrFreecap';

    /**
     * @var string Name of the extension this view helper belongs to
     */
    protected $extensionKey = 'sr_freecap';

    /**
     * @var string Name of the plugin this view helper belongs to
     */
    protected $pluginName = 'tx_srfreecap';

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument(
            'suffix',
            'string',
            'Suffix to be appended to the extenstion key when forming css class names',
            false,
            ''
        );
    }

    /**
     * Render the captcha image html
     *
     * @param string suffix to be appended to the extenstion key when forming css class names
     * @return string The html used to render the captcha image
     * @throws SessionNotFoundException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function render($suffix = '')
    {
        // This viewhelper needs a frontend user session
        if (!is_object($this->getTypoScriptFrontendController()) || !isset($this->getTypoScriptFrontendController()->fe_user)) {
            throw new SessionNotFoundException('No frontend user found in session!');
        }

        $value = '';

        // Include the required JavaScript
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFooterFile(PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($this->extensionKey)) . 'Resources/Public/JavaScript/freeCap.js');

        // Disable caching
        $this->getTypoScriptFrontendController()->no_cache = 1;

        // Get the translation view helper
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $translator = $objectManager->get(TranslateViewHelper::class);

        // Generate the image url
        $fakeId = GeneralUtility::shortMD5(uniqid(rand()), 5);
        $siteURL = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $context = GeneralUtility::makeInstance(Context::class);
        $languageAspect = $context->getAspect('language');
        $urlParams = [
            //'eID' => 'sr_freecap_EidDispatcher',
            'id' => $this->getTypoScriptFrontendController()->id,
            //'vendorName' => 'SJBR',
            //'extensionName' => 'SrFreecap',
            'pluginName' => 'ImageGenerator',
            'controllerName' => 'ImageGenerator',
            'actionName' => 'show',
            'formatName' => 'png',
            'L' => $languageAspect->getId()
        ];
        if ($this->getTypoScriptFrontendController()->MP) {
            $urlParams['MP'] = $this->getTypoScriptFrontendController()->MP;
        }
        $urlParams['set'] = $fakeId;
        $imageUrl = $siteURL . 'index.php?' . ltrim(GeneralUtility::implodeArrayForUrl('', $urlParams), '&');

        // Generate the html text
        $value = '<img' . $this->getClassAttribute(
            'image',
            $suffix
        ) . ' id="tx_srfreecap_captcha_image_' . $fakeId . '"'
            . ' src="' . htmlspecialchars($imageUrl) . '"'
            . ' alt="' . $translator->render('altText') . ' "/>'
            . '<span' . $this->getClassAttribute('cant-read', $suffix) . '>' . $translator->render('cant_read1')
            . ' <a href="#" onclick="this.blur();' . $this->extensionName . '.newImage(\'' . $fakeId . '\', \'' . $translator->render('noImageMessage') . '\');return false;">'
            . $translator->render('click_here') . '</a>'
            . $translator->render('cant_read2') . '</span>';
        return $value;
    }

    /**
     * Returns a class attribute with a class-name prefixed with $this->pluginName and with all underscores substituted to dashes (-)
     *
     * @param string $class The class name (or the END of it since it will be prefixed by $this->pluginName.'-')
     * @param string suffix to be appended to the extenstion key when forming css class names
     * @return string the class attribute with the combined class name (with the correct prefix)
     */
    protected function getClassAttribute($class, $suffix = '')
    {
        return ' class="' . trim(str_replace(
            '_',
            '-',
            $this->pluginName
        ) . ($suffix ? '-' . $suffix . '-' : '-') . $class) . '"';
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
