<?php
namespace SJBR\SrFreecap;

/*
 *  Copyright notice
 *
 *  (c) 2005-2018 Stanislas Rolland <typo3(arobas)sjbr.ca>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
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

use SJBR\SrFreecap\Validation\Validator\CaptchaValidator;
use SJBR\SrFreecap\ViewHelpers\AudioViewHelper;
use SJBR\SrFreecap\ViewHelpers\ImageViewHelper;
use SJBR\SrFreecap\ViewHelpers\TranslateViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class PiBaseApi
{
    /**
     * @var string The extension key
     */
    public $extKey = 'sr_freecap';

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * This function generates an array of markers used to render the captcha element
     *
     * @return array marker array containing the captcha markers to be sustituted in the html template
     */
    public function makeCaptcha()
    {
        // Get the object manager
        if ($this->objectManager === null) {
            $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        }

        // Get the configuration manager
        $configurationManager = $this->objectManager->get(ConfigurationManagerInterface::class);

        // Get translation view helper
        $translator = $this->objectManager->get(TranslateViewHelper::class);
        $translator->injectConfigurationManager($configurationManager);

        $markerArray = [];
        $markerArray['###' . strtoupper($this->extKey) . '_NOTICE###'] = $translator->render('notice') . ' ' . $translator->render('explain');

        // Get the captcha image view helper
        $imageViewHelper = $this->objectManager->get(ImageViewHelper::class);
        $imageViewHelper->injectConfigurationManager($configurationManager);
        $markerArray['###' . strtoupper($this->extKey) . '_IMAGE###'] = $imageViewHelper->render('pi1');
        $markerArray['###' . strtoupper($this->extKey) . '_CANT_READ###'] = '';

        // Get the audio icon view helper
        $audioViewHelper = $this->objectManager->get(AudioViewHelper::class);
        $audioViewHelper->injectConfigurationManager($configurationManager);
        $markerArray['###' . strtoupper($this->extKey) . '_ACCESSIBLE###'] = $audioViewHelper->render('pi1');

        return $markerArray;
    }

    /**
     * Check the word that was entered against the hashed value
     *
     * @param string $word: hte word that was entered
     * @return bool true, if the word entered matches the hashes value
     */
    public function checkWord($word)
    {
        // Get the object manager
        if ($this->objectManager === null) {
            $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        }
        // Get the validator
        $validator = $this->objectManager->get(CaptchaValidator::class);
        // Check word
        return !$validator->validate($word)->hasErrors();
    }
}
class_alias('SJBR\\SrFreecap\\PiBaseApi', 'tx_srfreecap_pi2');
