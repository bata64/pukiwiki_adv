<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: mobile.ini.php,v 0.0.2 2012/02/19 21:45:30 Logue Exp $
// 
// PukiWiki Adv. Mobile Theme
// Copyright (C)
//   2012 PukiWiki Advance Developer Team

// ------------------------------------------------------------
// Settings (define before here, if you want)
global $_SKIN, $link_tags, $js_tags;

//define('IS_MOBILE',true);

// �ǂݍ��ރX�^�C���V�[�g
//$link_tags[] = array('rel'=>'stylesheet',	'type'=>'text/css',	'href'=>'http://code.jquery.com/mobile/1.1.0-rc.1/jquery.mobile-1.1.0-rc.1.min.css');
$link_tags[] = array('rel'=>'stylesheet',	'type'=>'text/css',	'href'=>SKIN_URI.THEME_PLUS_NAME.'mobile/mobile.css.php');

// �ǂݍ��ރX�N���v�g
//$js_tags[] = array('type'=>'text/javascript', 'src'=>SKIN_URI.THEME_PLUS_NAME.PLUS_THEME.'/classic.js');
?>