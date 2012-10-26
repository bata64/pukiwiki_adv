<?php
// PukiWiki Advance.
// $Id: main.php,v 1.23.28 2012/01/29 23:15:00 Logue Exp $
//
// PukiWiki Advance
//  Copyright (C) 2010-2012 by PukiWiki Advance Team
//  http://pukiwiki.logue.be/
//
// PukiWiki Plus! 1.4.*
//  Copyright (C) 2002-2009 by PukiWiki Plus! Team
//  http://pukiwiki.cafelounge.net/plus/
//
// PukiWiki 1.4.*
//  Copyright (C) 2002-2011 by PukiWiki Developers Team
//  http://pukiwiki.sourceforge.jp/
//
// PukiWiki 1.3.*
//  Copyright (C) 2002-2004 by PukiWiki Developers Team
//  http://pukiwiki.sourceforge.jp/
//
// PukiWiki 1.3 (Base)
//  Copyright (C) 2001-2002 by yu-ji <sng@factage.com>
//  http://pukiwiki.sourceforge.jp/
//
// Special thanks
//  YukiWiki by Hiroshi Yuki <hyuki@hyuki.com>
//  http://www.hyuki.com/yukiwiki/
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

set_time_limit(0);
ignore_user_abort(true);
ini_set('memory_limit', '128M');
ini_set('zlib.output_compression', 'Off');
ini_set('zlib.output_handler','mb_output_handler');
$info = array();
foreach (array('mbstring','json') as $ext){
	if (! extension_loaded($ext)){
		$info[] = 'PukiWiki Adv. needs the <a href="http://www.php.net/manual/book.'.$ext.'.php">'.$ext.' extension</a>.';
	}
}
if (count($info) !== 0){
	throw new Exception(join("<br />\n",$info));
}

/////////////////////////////////////////////////
// Include subroutines

defined('LIB_DIR') or define('LIB_DIR', realpath('./').'/');

// Load *.ini.php files and init PukiWiki
require(LIB_DIR . 'func.php');
require(LIB_DIR . 'file.php');
require(LIB_DIR . 'funcplus.php');
require(LIB_DIR . 'fileplus.php');
require(LIB_DIR . 'plugin.php');
require(LIB_DIR . 'html.php');
require(LIB_DIR . 'backup.php');

require(LIB_DIR . 'convert_html.php');
require(LIB_DIR . 'make_link.php');
require(LIB_DIR . 'diff.php');
require(LIB_DIR . 'config.php');
require(LIB_DIR . 'link.php');
require(LIB_DIR . 'auth.php');
require(LIB_DIR . 'proxy.php');
require(LIB_DIR . 'lang.php');
require(LIB_DIR . 'timezone.php');
require(LIB_DIR . 'log.php');
require(LIB_DIR . 'spamplus.php');
require(LIB_DIR . 'proxy.cls.php');
require(LIB_DIR . 'auth.cls.php');
require(LIB_DIR . 'netbios.cls.php');
require(LIB_DIR . 'ua/user_agent.cls.php');

require(LIB_DIR . 'simple_html_dom.php');
require(LIB_DIR . 'gettext/gettext.inc');

// Defaults
$notify = $trackback = $referer = 0;
require(LIB_DIR . 'init.php');

// Load optional libraries
if (isset($notify)){ require(LIB_DIR . 'mail.php'); }	// Mail notification
if (isset($trackback)){ require(LIB_DIR . 'trackback.php'); }	// TrackBack
if (isset($referer)){ require(LIB_DIR . 'referer.php'); }
/////////////////////////////////////////////////
// Main
$info[] = '<var>PHP '.PHP_VERSION.'</var> as <var>'.php_sapi_name().'</var> mode.';
$info[] = 'Powerd by <var>'.getenv('SERVER_SOFTWARE').'</var>.';

$retvars = array();
$page  = isset($vars['page'])  ? $vars['page']  : '';
$refer = isset($vars['refer']) ? $vars['refer'] : '';
$plugin = isset($vars['cmd']) ? $vars['cmd'] : '';

// SPAM
$remote_addr = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];
if (SpamCheckBAN($remote_addr)) die('Sorry, your access is prohibited.');

// Block SPAM countory
$country_code = '';
if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])){
	// CloudFlareを使用している場合、そちらのGeolocationを読み込む
	// https://www.cloudflare.com/wiki/IP_Geolocation
	$country_code = $_SERVER['HTTP_CF_IPCOUNTRY'];
}else if (isset($_SERVER['GEOIP_COUNTRY_CODE'])){
	// サーバーが$_SERVER['GEOIP_COUNTRY_CODE']を出力している場合
	$country_code = $_SERVER['GEOIP_COUNTRY_CODE'];
}else if (function_exists('geoip_db_avail') && geoip_db_avail(GEOIP_COUNTRY_EDITION) && function_exists('geoip_region_by_name')) {
	// それでもダメな場合は、phpのgeoip_region_by_name()からGeolocationを取得
	$geoip = geoip_region_by_name($remote_addr);
	$country_code = $geoip['country_code'];
	$info[] = (!empty($geoip['country_code']) ) ?
		'GeoIP is usable. Your country code from IP is inferred <var>'.$geoip['country_code'].'</var>.' :
		'GeoIP is NOT usable. Maybe database is not installed. Please check <a href="http://www.maxmind.com/app/installation?city=1" rel="external">GeoIP Database Installation Instructions</a>';
}else if (function_exists('apache_note')) {
	// Apacheの場合
	$country_code = apache_note('GEOIP_COUNTRY_CODE');
}

// 使用可能かをチェック
if ( isset($country_code) && !empty($country_code)) {
	$info[] = 'Your country code from IP is inferred <var>'.$country_code.'</var>.';
} else {
	$info[] = '<var>$deny_countory</var> value and <var>$allow_countory</var> value is ignoled.';
}

// Spam filtering
if ($spam && $method !== 'GET') {
	if (isset($country_code) && $country_code !== false){
		if (isset($deny_countory) && !empty($deny_countory)) {
			if (in_array($country_code, $deny_countory)) {
				die('Sorry, access from your country('.$geoip['country_code'].') is prohibited.');
				exit;
			}
		}
		if (isset($allow_countory) && !empty($allow_countory)) {
			if (!in_array($country_code, $allow_countory)) {
				die('Sorry, access from your country('.$geoip['country_code'].') is prohibited.');
				exit;
			}
		}
	}

	// Adjustment
	$_spam   = ! empty($spam);
	$_plugin = strtolower($plugin);
	$_ignore = array();
	switch ($_plugin) {
		case 'search': $_spam = FALSE; break;
		case 'edit':
			$_page = & $page;
			if (isset($vars['add']) && $vars['add']) {
				$_plugin = 'add';
			} else {
				$_ignore[] = 'original';
			}
			break;
		case 'bugtrack': $_page = & $vars['base'];  break;
		case 'tracker':  $_page = & $vars['_base']; break;
		case 'read':     $_page = & $page;  break;
		default: $_page = & $refer; break;
	}

	if ($_spam) {
		require(LIB_DIR . 'spam.php');

		if (isset($spam['method'][$_plugin])) {
			$_method = & $spam['method'][$_plugin];
		} else if (isset($spam['method']['_default'])) {
			$_method = & $spam['method']['_default'];
		} else {
			$_method = array();
		}
		$exitmode = isset($spam['exitmode']) ? $spam['exitmode'] : '';

		// Hack: ignorance several keys
		if ($_ignore) {
			$_vars = array();
			foreach($vars as $key => $value) {
				$_vars[$key] = & $vars[$key];
			}
			foreach($_ignore as $key) {
				unset($_vars[$key]);
			}
		} else {
			$_vars = & $vars;
		}
		pkwk_spamfilter($method . ' to #' . $_plugin, $_page, $_vars, $_method, $exitmode);
	}
}

// If page output, enable session.
// NOTE: if action plugin(command) use session, call pkwk_session_start()
//       in plugin action-API function.
pkwk_session_start();

// auth remoteip
if (isset($auth_api['remoteip']['use']) && $auth_api['remoteip']['use']) {
	if (exist_plugin_inline('remoteip')) do_plugin_inline('remoteip');
}

// WebDAV
if (is_webdav() && exist_plugin('dav')) {
	do_plugin_action('dav');
	exit;
}
$is_protect = auth::is_protect();

if (DEBUG) {
	$exclude_plugin = array();
	if (file_exists($mecab_path)){
		$info[] = 'Mecab is enabled. (It will not work in XAMPP,but not a malfunction....)';
		if (extension_loaded('mecab')){
			$info[] = 'Mecab is module mode.';
		}else{
			$info[] = 'Mecab is stdio mode. Please concider to install <a href="https://github.com/rsky/php-mecab">php-mecab</a> in your server.';
		}
	}else{
		$info[] = 'Mecab is disabled. If you installed, please check mecab path.';
	}
}

$base = '';
// Plugin execution
if (!empty($plugin)) {
	if ($is_protect) {
		$plugin_arg = '';
		if (auth::is_protect_plugin_action($plugin)) {
			if (exist_plugin_action($plugin)) do_plugin_action($plugin);
			// Location で飛ばないプラグインの場合
			$plugin_arg = $plugin;
		}
		if (exist_plugin_convert('protect')) do_plugin_convert('protect',$plugin_arg);
	}

	if (exist_plugin_action($plugin)) {
		$retvars = do_plugin_action($plugin);
		if ($retvars === FALSE) exit; // Done
		$base = (!empty($page)) ? $page : $refer;
	} else {
		header('HTTP/1.1 501 Not Implemented');
		die_message(sprintf($_string['plugin_not_implemented'],htmlsc($plugin)));
	}
}

// Location で飛ぶようなプラグインの対応のため
// 上のアクションプラグインの実行後に処理を実施
if ($is_protect) {
 	if (exist_plugin_convert('protect')) do_plugin_convert('protect');
	die('<var>PLUS_PROTECT_MODE</var> is set.');
}

// Set Home
$auth_key = auth::get_user_info();
if (!empty($auth_key['home'])) {
	if ($base == $defaultpage || $base == $auth_key['home'])
		$base = $defaultpage = $auth_key['home'];
}

// Page output
$title = htmlsc(strip_bracket($base));
$page  = make_search($base);
if (isset($retvars['msg']) && !empty($retvars['msg']) ) {
	$title = str_replace('$1', $title, $retvars['msg']);
	$page  = str_replace('$1', $page,  $retvars['msg']);
}

if (isset($retvars['body']) && !empty($retvars['body'])) {
	$body = $retvars['body'];
} else {
	if (empty($base) || ! is_page($base)) {
		$base  = $defaultpage;
		$title = htmlsc(strip_bracket($base));
		$page  = make_search($base);
	}

	$vars['cmd']  = 'read';
	$vars['page'] = $base;

	global $fixed_heading_edited;
	$source = get_source($base);

	// Virtual action plugin(partedit).
	// NOTE: Check wiki source only.(*NOT* call convert_html() function)
	$lines = $source;
	while (! empty($lines)) {
		$line = array_shift($lines);
		if (preg_match("/^\#(partedit)(?:\((.*)\))?/", $line, $matches)) {
			if ( !isset($matches[2]) || $matches[2] == '') {
				$fixed_heading_edited = ($fixed_heading_edited ? 0:1);
			} else if ( $matches[2] == 'on') {
				$fixed_heading_edited = 1;
			} else if ( $matches[2] == 'off') {
				$fixed_heading_edited = 0;
			}
		}
	}

	$body = convert_html($source);
	$body .= ($trackback && $tb_auto_discovery) ? tb_get_rdf($base) : ''; // Add TrackBack-Ping URI
	if ($referer) ref_save($base);
	log_write('check',$vars['page']);
	log_write('browse',$vars['page']);
}

// global $always_menu_displayed;
if (arg_check('read')) $always_menu_displayed = 1;
$body_menu = $body_side = '';
if ($always_menu_displayed) {
	if (exist_plugin_convert('menu')) $body_menu = do_plugin_convert('menu');
	if (exist_plugin_convert('side')) $body_side = do_plugin_convert('side');
}

// Output
catbody($title, $page, $body);
exit;

/* End of file main.php */
/* Location: ./wiki-common/lib/main.php */