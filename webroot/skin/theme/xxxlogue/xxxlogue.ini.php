<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: xxxlogue.ini.php,v2.3.1 2010/09/11 22:59:30 Logue Exp $
// Copyright (C) 2010-2011 PukiWiki Advance Developers Team
//               2007-2010 Logue
// PukiWiki Advance xxxLogue skin
//
// Based on
//   Xu Yiyang's (http://xuyiyang.com/) Unnamed (http://xuyiyang.com/wordpress-themes/unnamed/)
//
// License: GPL v3 or (at your option) any later version
// http://www.opensource.org/licenses/gpl-3.0.html
//
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
	
	// �摜�ݒu�f�B���N�g���i���̃f�B���N�g������̑��΃p�X�j
	'image_dir'		=> './image/',

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
$link_tags[] = array('rel'=>'stylesheet','href'=>SKIN_URI.'scripts.css.php');
$link_tags[] = array('rel'=>'stylesheet','href'=>SKIN_URI.THEME_PLUS_NAME.PLUS_THEME.'/xxxlogue.css.php');

// �ǂݍ��ރX�N���v�g
$js_tags[] = array('type'=>'text/javascript', 'src'=>SKIN_URI.THEME_PLUS_NAME.PLUS_THEME.'/xxxlogue.js');