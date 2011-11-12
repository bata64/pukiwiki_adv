<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: classic.ini.php,v 2.2.7 2011/09/11 22:58:30 Logue Exp $
// 
// PukiWiki Classic Skin
// Copyright (C)
//   2010-2011 PukiWiki Advance Developer Team
//   2005-2010 Logue
//   2002-2006 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji

// ------------------------------------------------------------
// Settings (define before here, if you want)
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
	'ui_theme'		=> 'redmond',

	// Navibar�n�v���O�C���ł��A�C�R����\������
	'showicon'		=> false,

	// �A�h���X�̑���Ƀp�X��\��
	'topicpath'		=> true,
	
	// ���S�ݒ�
	'logo'=>array(
		'src'		=> IMAGE_URI.'pukiwiki_adv.logo.png',
		'alt'		=> '[PukiWiki Adv.]',
		'width'		=> '96',
		'height'	=> '96'
	),

	// �L���\���̈�
	'adarea'	=> array(
		// �y�[�W�̉E��̍L���\���̈�
		'header'	=> <<<EOD
EOD
	)
);

// �ǂݍ��ރX�^�C���V�[�g
$link_tags[] = array('rel'=>'stylesheet',	'type'=>'text/css',	'href'=>SKIN_URI.'scripts.css.php');
$link_tags[] = array('rel'=>'stylesheet',	'type'=>'text/css',	'href'=>SKIN_URI.THEME_PLUS_NAME.PLUS_THEME.'/classic.css.php');

// �ǂݍ��ރX�N���v�g
$js_tags[] = array('type'=>'text/javascript', 'src'=>SKIN_URI.THEME_PLUS_NAME.PLUS_THEME.'/classic.js');
?>