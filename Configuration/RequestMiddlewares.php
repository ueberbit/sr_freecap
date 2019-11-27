<?php
return [
    'frontend' => [
        'sjbr/srfreecap/captchamiddleware' => [
            'target' => \SJBR\SrFreecap\Middleware\CaptchaMiddleware::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ],
    ],
];
