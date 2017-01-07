<?php
/*
 * Extension Manager configuration file for ext "sr_freecap".
 *
 */
$EM_CONF[$_EXTKEY] = array(
	'title' => 'freeCap CAPTCHA',
	'description' => 'A TYPO3 integration of freeCap CAPTCHA.',
	'category' => 'plugin',
	'version' => '2.3.1',
	'state' => 'stable',
	'uploadfolder' => 1,
	'createDirs' => '',
	'clearcacheonload' => 0,
	'author' => 'Stanislas Rolland',
	'author_email' => 'typo3(arobas)sjbr.ca',
	'author_company' => 'SJBR',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.6.0-8.99.99'
		),
		'conflicts' => array(),
		'suggests' => array()
	)
);