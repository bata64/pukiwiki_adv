<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// White Flow skin for PukiWiki Advance.
// Override to skin config.
//
// $Id: whiteflow.ini.php,v 1.0.0 2011/12/10 11:39:00 Logue Exp $
//
global $_SKIN, $link_tags, $js_tags;

$_SKIN = array(
/*
UI Themes
jQuery(jQuery UI): 
	base, black-tie, blitzer, cupertino, dark-hive, dot-luv, eggplant, excite-bike, flick, hot-sneaks
	humanity, le-frog, mint-choc, overcast, pepper-grinder, redmond, smoothness, south-street,
	start, sunny, swanky-purse, trontastic, ui-darkness, ui-lightness, vader

see also
http://www.devcurry.com/2010/05/latest-jquery-and-jquery-ui-theme-links.html
http://jqueryui.com/themeroller/
*/
	'ui_theme'		=> 'smoothness',
	
	'image_dir'		=> './image/',

	// Navibar�n�v���O�C���ł��A�C�R����\������
	'showicon'		=> true,

	// �A�h���X�̑���Ƀp�X��\��
	'topicpath'		=> true,
	
	// ���S�ݒ�
	'logo'=>array(
		'src'		=> IMAGE_URI.'pukiwiki_adv.logo.png',
		'alt'		=> '[PukiWiki Adv.]',
		'width'		=> '80',
		'height'	=> '80'
	),

	// �L���\���̈�
	'adarea'	=> array(
		// �y�[�W�̉E��̍L���\���̈�
		'header'	=> <<<EOD
EOD
,		// �y�[�W�����̍L���\���̈�
		'footer'	=> <<<EOD
EOD
	)
);

// �ǂݍ��ރX�^�C���V�[�g
$link_tags[] = array('rel'=>'stylesheet','href'=>SKIN_URI.'scripts.css.php?base=' . urlencode(IMAGE_URI) );
$link_tags[] = array('rel'=>'stylesheet','href'=>SKIN_URI.THEME_PLUS_NAME.PLUS_THEME.'/whiteflow.css.php');
?>