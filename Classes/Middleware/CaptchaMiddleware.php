<?php
declare(strict_types = 1);
namespace SJBR\SrFreecap\Middleware;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Lightweight alternative to regular frontend requests; used when $_GET[eID] is set.
 * In the future, logic from the EidUtility will be moved to this class, however in most cases
 * a custom PSR-15 middleware will be better suited for whatever job the eID functionality does currently.
 *
 * @internal
 */
class CaptchaMiddleware implements MiddlewareInterface
{

    /**
     * @var StandaloneView
     */
    protected $standaloneView;

    /**
     * Extbase Object Manager
     * @var ObjectManager
     * @inject
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $vendorName = 'SJBR';

    /**
     * @var string
     */
    protected $extensionName = 'SrFreecap';

    /**
     * @var string
     */
    protected $pluginName;

    /**
     * @var string
     */
    protected $controllerName;

    /**
     * @var string
     */
    protected $actionName;

    /**
     * @var string
     */
    protected $formatName;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var int
     */
    protected $pageUid;

    /**
     * CaptchaMiddleware constructor.
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    public function __construct()
    {
        $this->standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $this->initializeStandaloneView();
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    private function initializeStandaloneView(): void
    {
        $this->standaloneView->getRenderingContext()->setControllerName('ImageGenerator');
        $this->standaloneView->getRenderingContext()->setControllerAction('show');
        $this->standaloneView->getRequest()->setControllerExtensionName('SrFreecap');
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws BadRequestException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pluginName = $this->getParamFromRequest($request, 'pluginName'); //show

        // this can be replaced by a switch case or so if you wan´t more than one action
        if ($pluginName !== 'ImageGenerator' && $pluginName !== 'AudioPlayer') {
            return $handler->handle($request);
        }

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var Bootstrap $bootstrap */
        $bootstrap = $this->objectManager->get(Bootstrap::class);
        $this->pluginName = $this->getParamFromRequest($request, 'pluginName');
        $this->controllerName = $this->getParamFromRequest($request, 'controllerName');
        $this->actionName = $this->getParamFromRequest($request, 'actionName');
        $this->formatName = $this->getParamFromRequest($request, 'formatName');
        $this->arguments = [];

        $configuration['vendorName'] = $this->vendorName;
        $configuration['extensionName'] = $this->extensionName;
        $configuration['pluginName'] = $this->pluginName;
        $bootstrap->initialize($configuration);
        $request = $this->buildRequest();
        $response = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Response::class);
        $dispatcher = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Dispatcher::class);
        try {
            $dispatcher->dispatch($request, $response);
        } catch (\Exception $e) {
            throw new BadRequestException('An argument is missing or invalid', 1394587024);
        }
        if (isset($GLOBALS['TSFE']->fe_user)) {
            $GLOBALS['TSFE']->fe_user->storeSessionData();
        }
        // Output was already sent
        return new NullResponse();
    }

    /**
     * Build a request object
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Request $request
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    protected function buildRequest()
    {
        $request = $this->objectManager->get(Request::class);
        $request->setControllerVendorName($this->vendorName);
        $request->setControllerExtensionName($this->extensionName);
        $request->setPluginName($this->pluginName);
        $request->setControllerName($this->controllerName);
        $request->setControllerActionName($this->actionName);
        $request->setFormat($this->formatName);
        $request->setArguments($this->arguments);
        return $request;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $name
     * @return mixed
     */
    protected function getParamFromRequest(ServerRequestInterface $request, string $name)
    {
        return $request->getParsedBody()[$name] ?? $request->getQueryParams()[$name] ?? null;
    }

    /**
     * @return mixed
     */
    protected function processSettings()
    {
        $settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_srfreecap.']['settings.'];

        // Image type:
        // possible values: "jpg", "png", "gif"
        // jpg doesn't support transparency (transparent bg option ends up white)
        // png isn't supported by old browsers (see http://www.libpng.org/pub/png/pngstatus.html)
        // gif may not be supported by your GD Lib.
        $settings['imageFormat'] = $settings['imageFormat'] ? $settings['imageFormat'] : 'png';

        // true = generate pseudo-random string, false = use dictionary
        // dictionary is easier to recognise
        // - both for humans and computers, so use random string if you're paranoid.
        $settings['useWordsList'] = $settings['useWordsList'] ? true : false;

        // if your server is NOT set up to deny web access to files beginning ".ht"
        // then you should ensure the dictionary file is kept outside the web directory
        // eg: if www.foo.com/index.html points to c:\website\www\index.html
        // then the dictionary should be placed in c:\website\dict.txt
        // test your server's config by trying to access the dictionary through a web browser
        // you should NOT be able to view the contents.
        // can leave this blank if not using dictionary
        $settings['wordsListLocation'] = \SJBR\SrFreecap\Utility\LocalizationUtility::getWordsListLocation($settings['defaultWordsList']);

        // Used for non-dictionary word generation and to calculate image width
        $settings['maxWordLength'] = $settings['maxWordLength'] ? $settings['maxWordLength'] : 6;

        // Maximum times a user can refresh the image
        // on a 6500 word dictionary, I think 15-50 is enough to not annoy users and make BF unfeasble.
        // further notes re: BF attacks in "avoid brute force attacks" section, below
        // on the other hand, those attempting OCR will find the ability to request new images
        // very useful; if they can't crack one, just grab an easier target...
        // for the ultra-paranoid, setting it to <5 will still work for most users
        $settings['maxAttempts'] = $settings['maxAttempts'] ? $settings['maxAttempts'] : 50;

        // List of fonts to use
        // font size should be around 35 pixels wide for each character.
        // you can use my GD fontmaker script at www.puremango.co.uk to create your own fonts
        // There are other programs to can create GD fonts, but my script allows a greater
        // degree of control over exactly how wide each character is, and is therefore
        // recommended for 'special' uses. For normal use of GD fonts,
        // the GDFontGenerator @ http://www.philiplb.de is excellent for convering ttf to GD
        // the fonts included with freeCap *only* include lowercase alphabetic characters
        // so are not suitable for most other uses
        // to increase security, you really should add other fonts
        if ($settings['generateNumbers']) {
            $settings['fontLocations'] = ['EXT:sr_freecap/Resources/Private/Captcha/Fonts/anonymous.gdf'];
        } else {
            $settings['fontLocations'] = [
                'EXT:sr_freecap/Resources/Private/Captcha/Fonts/freecap_font1.gdf',
                'EXT:sr_freecap/Resources/Private/Captcha/Fonts/freecap_font2.gdf',
                'EXT:sr_freecap/Resources/Private/Captcha/Fonts/freecap_font3.gdf',
                'EXT:sr_freecap/Resources/Private/Captcha/Fonts/freecap_font4.gdf',
                'EXT:sr_freecap/Resources/Private/Captcha/Fonts/freecap_font5.gdf'
            ];
        }
        if ($settings['fontFiles']) {
            $settings['fontLocations'] = GeneralUtility::trimExplode(',', $settings['fontFiles'], 1);
        }
        for ($i = 0; $i < count($settings['fontLocations']); $i++) {
            if (substr($settings['fontLocations'][$i], 0, 4) == 'EXT:') {
                $settings['fontLocations'][$i] = GeneralUtility::getFileAbsFileName($settings['fontLocations'][$i]);
            } else {
                $settings['fontLocations'][$i] = Environment::getPublicPath() . '/uploads/tx_srfreecap/' . $settings['fontLocations'][$i];
            }
        }

        // Text color
        // 0 = one random color for all letters
        // 1 = different random color for each letter
        if ($settings['textColor']) {
            $settings['textColor'] = 1;
        } else {
            $settings['textColor'] = 0;
        }

        // Text position
        $settings['textPosition'] = [];
        $settings['textPosition']['horizontal'] = $settings['textHorizontalPosition'] ? intval($settings['textHorizontalPosition']) : 32;
        $settings['textPosition']['vertical'] = $settings['textVerticalPosition'] ? intval($settings['textVerticalPosition']) : 15;
        // Text morphing factor
        $settings['morphFactor'] = $settings['morphFactor'] ? $settings['morphFactor'] : 0;
        // Limits for text color
        $settings['colorMaximum'] = [];
        if (isset($settings['colorMaximumDarkness'])) {
            $settings['colorMaximum']['darkness'] = intval($settings['colorMaximumDarkness']);
        }
        if (isset($settings['colorMaximumLightness'])) {
            $settings['colorMaximum']['lightness'] = intval($settings['colorMaximumLightness']);
        }

        // Background
        // Many thanks to http://ocr-research.org.ua and http://sam.zoy.org/pwntcha/ for testing
        // for jpgs, 'transparent' is white
        if (!in_array($settings['backgroundType'], ['Transparent', 'White with grid', 'White with squiggles', 'Morphed image blocks'])) {
            $settings['backgroundType'] = 'White with grid';
        }
        // Should we blur the background? (looks nicer, makes text easier to read, takes longer)
        $settings['backgroundBlur'] = ($settings['backgroundBlur'] || !isset($settings['backgroundBlur'])) ? true : false;
        // For background type 'Morphed image blocks', which images should we use?
        // If you add your own, make sure they're fairly 'busy' images (ie a lot of shapes in them)
        $settings['backgroundImages'] = [
            'EXT:sr_freecap/Resources/Private/Captcha/Images/freecap_im1.jpg',
            'EXT:sr_freecap/Resources/Private/Captcha/Images/freecap_im2.jpg',
            'EXT:sr_freecap/Resources/Private/Captcha/Images/freecap_im3.jpg',
            'EXT:sr_freecap/Resources/Private/Captcha/Images/freecap_im4.jpg',
            'EXT:sr_freecap/Resources/Private/Captcha/Images/freecap_im5.jpg'
        ];
        // For non-transparent backgrounds only:
        // if 0, merges CAPTCHA with background
        // if 1, write CAPTCHA over background
        $settings['mergeWithBackground'] = $settings['mergeWithBackground'] ? 0 : 1;
        // Should we morph the background? (recommend yes, but takes a little longer to compute)
        $settings['backgroundMorph'] = $settings['backgroundMorph'] ? true : false;

        // Read each font and get font character widths
        $settings['fontWidths'] = [];
        for ($i=0; $i < count($settings['fontLocations']); $i++) {
            $handle = fopen($settings['fontLocations'][$i], 'r');
            // Read header of GD font, up to char width
            $c_wid = fread($handle, 12);
            $settings['fontWidths'][$i] = ord($c_wid{8})+ord($c_wid{9})+ord($c_wid{10})+ord($c_wid{11});
            fclose($handle);
        }
        // Modify image width depending on maximum possible length of word
        // you shouldn't need to use words > 6 chars in length really.
        $settings['imageWidth'] = ($settings['maxWordLength'] * (array_sum($settings['fontWidths'])/count($settings['fontWidths']))) + (isset($settings['imageAdditionalWidth']) ? intval($settings['imageAdditionalWidth']) : 40);
        $settings['imageHeight'] = $settings['imageHeight'] ? $settings['imageHeight'] : 90;

        // Try to avoid the 'free p*rn' method of CAPTCHA circumvention
        // see www.wikipedia.com/captcha for more info
        // "To avoid spam, please do NOT enter the text if this site is not example.org";
        // or more simply:
        // "for use only on example.org";
        // reword or add lines as you please
        $settings['siteTag'] = $settings['siteTag'] ? explode('|', LocalizationUtility::translate('site_tag', 'tx_srfreecap', [isset($settings['siteTagDomain']) ? $settings['siteTagDomain'] : 'example.org'])) : [];

        // where to write the above:
        // 0=top
        // 1=bottom
        // 2=both
        $settings['siteTagPosition'] = isset($settings['siteTagPosition']) ? $settings['siteTagPosition'] : 1;

        return $settings;
    }
}
