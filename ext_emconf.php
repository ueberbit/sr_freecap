<?php
/*
 * Extension Manager configuration file for ext "sr_freecap".
 *
 */
$EM_CONF[$_EXTKEY] = [
    'title' => 'freeCap CAPTCHA',
    'description' => 'A TYPO3 integration of freeCap CAPTCHA.',
    'category' => 'plugin',
    'version' => '2.5.3',
    'state' => 'stable',
    'uploadfolder' => 1,
    'createDirs' => '',
    'clearcacheonload' => 0,
    'author' => 'Stanislas Rolland',
    'author_email' => 'typo3(arobas)sjbr.ca',
    'author_company' => 'SJBR',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-9.5.99'
        ],
        'conflicts' => [],
        'suggests' => []
    ]
];
