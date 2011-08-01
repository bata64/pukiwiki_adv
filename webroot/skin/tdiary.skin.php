<?php
// PukiPlus - Yet another WikiWikiWeb clone.
// $Id: tdiary.skin.php,v 1.34.7 2010/01/18 23:39:00 upk Exp $
// Copyright (C)
//  2010 PukiPlus Team
//  2005-2007,2009 PukiWiki Plus! Team
//  2002-2007 PukiWiki Developers Team
//  2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// tDiary-wrapper skin (Updated for tdiary-theme 2.1.2)

// ------------------------------------------------------------
// Settings (define before here, if you want)

// Set site identities
$_IMAGE['skin']['favicon']  = 'favicon.ico'; // Sample: 'image/favicon.ico';

// Select theme
defined('TDIARY_THEME') or define('TDIARY_THEME', 'loose-leaf'); // Default

// Show link(s) at your choice, with <div class="calendar"> design
// NOTE: Some theme become looking worse with this!
//   NULL = Show nothing
//   0    = Show topicpath
//   1    = Show reload URL
defined('TDIARY_CALENDAR_DESIGN') or define('TDIARY_CALENDAR_DESIGN', null); // null, 0, 1

// Show / Hide navigation bar UI at your choice
// NOTE: This is not stop their functionalities!
defined('PKWK_SKIN_SHOW_NAVBAR') or define('PKWK_SKIN_SHOW_NAVBAR', 1); // 1, 0

// Show toolbar at your choice, with <div class="footer"> design
// NOTE: Some theme become looking worse with this!
defined('PKWK_SKIN_SHOW_TOOLBAR') or define('PKWK_SKIN_SHOW_TOOLBAR', 0); // 0, 1

// TDIARY_SIDEBAR_POSITION: See below

// ------------------------------------------------------------
// Code start

// Prohibit direct access
if (! defined('UI_LANG')) die('UI_LANG is not set');
if (! isset($_LANG)) die('$_LANG is not set');
if (! defined('PKWK_READONLY')) die('PKWK_READONLY is not set');

// ------------------------------------------------------------
// Check tDiary theme

if (! defined('TDIARY_THEME') || TDIARY_THEME == '') {
	die('Theme is not specified. Set "TDIARY_THEME" correctly');
} else {
	$theme = rawurlencode(TDIARY_THEME); // Supress all nasty letters
	$theme_css = SKIN_DIR . THEME_TDIARY_NAME . $theme . '/' . $theme . '.css';
	if (! file_exists($theme_css)) {
		echo 'tDiary theme wrapper: ';
		echo 'Theme not found: ' . htmlspecialchars($theme_css) . '<br />';
		echo 'You can get tdiary-theme from: ';
		echo 'http://sourceforge.net/projects/tdiary/';
		exit;
	 }
}

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
$ui_theme = 'base';

$files = array(
	/* libraly */
	'swfupload','shadowbox',
	
	/* Use plugins */ 
	'jquery.cookie','jquery.lazyload', 'jquery.query','jquery.scrollTo','jquery.colorbox',
	'jquery.superfish','jquery.swfupload','jquery.tablesorter','jquery.textarearesizer', 'jquery.jplayer.min',
	'jquery.markitup','jQselectable',
	
	/* MUST BE LOAD LAST */
	'skin.original'
);

$debug = false;

// ------------------------------------------------------------
// tDiary theme: Exception

// Adjust DTD (bug between these theme(=CSS) and MSIE)
// NOTE:
//    PukiWiki default: PKWK_DTD_XHTML_1_1
//    tDiary's default: PKWK_DTD_HTML_4_01_STRICT
switch(TDIARY_THEME){
case 'christmas':
	$pkwk_dtd = PKWK_DTD_HTML_4_01_STRICT; // or centering will be ignored via MSIE
	break;
}

// Adjust reverse-link default design manually
$disable_backlink = FALSE;
switch(TDIARY_THEME){
case 'hatena':		/* FALLTHROUGH */
case 'hatena-black':
case 'hatena-brown':
case 'hatena-darkgray':
case 'hatena-green':
case 'hatena-lightblue':
case 'hatena-lightgray':
case 'hatena-purple':
case 'hatena-red':
case 'hatena-white':
case 'hatena_cinnamon':
case 'hatena_japanese':
case 'hatena_leaf':
case 'hatena_water':
	$disable_backlink = TRUE; // or very viewable title color
	break;
}

// ------------------------------------------------------------
// tDiary theme: Select CSS color theme (Now testing:black only)

if (defined('TDIARY_COLOR_THEME')) {
	$css_theme = rawurlencode(TDIARY_COLOR_THEME);
} else {
	$css_theme = '';

	switch(TDIARY_THEME){
	case 'alfa':
	case 'bill':
	case 'black-lingerie':
	case 'blackboard':
	case 'bubble':
	case 'cosmos':
	case 'darkness-pop':
	case 'digital_gadgets':
	case 'fine':
	case 'fri':
	case 'giza':
	case 'hatena-black':
	case 'hatena_savanna-blue':
	case 'hatena_savanna-green':
	case 'hatena_savanna-red':
	case 'kaizou':
	case 'lightning':
	case 'lime':
	case 'line':
	case 'midnight':
	case 'moo':
	case 'nachtmusik':
	case 'nebula':
	case 'nippon':
	case 'noel':
	case 'petith-b':
	case 'quiet_black':
	case 'redgrid':
	case 'starlight':
	case 'tinybox_green':
	case 'white-lingerie':
	case 'white_flower':
	case 'whiteout':
	case 'wine':
	case 'wood':
	case 'xmastree':
	case 'yukon':
		$css_theme = 'black';

	// Another theme needed?
	case 'bluely':
	case 'brown':
	case 'deepblue':
	case 'scarlet':
	case 'smoking_black':
		;
	}
}

// ------------------------------------------------------------
// tDiary theme: Page title design (which is fancy, date and text?)

if (defined('TDIARY_TITLE_DESIGN_DATE') &&
    (TDIARY_TITLE_DESIGN_DATE  == 0 ||
     TDIARY_TITLE_DESIGN_DATE  == 1 ||
     TDIARY_TITLE_DESIGN_DATE  == 2)) {
	$title_design_date = TDIARY_TITLE_DESIGN_DATE;
} else {
	$title_design_date = 1; // Default: Select the date desin, or 'the same design'
	switch(TDIARY_THEME){
	case '3minutes':	/* FALLTHROUGH */
	case '90':
	case 'aoikuruma':
	case 'black-lingerie':
	case 'blog':
	case 'book':
	case 'book2-feminine':
	case 'book3-sky':
	case 'candy':
	case 'cards':
	case 'desert':
	case 'dot':
	case 'himawari':
	case 'kitchen-classic':
	case 'kitchen-french':
	case 'kitchen-natural':
	case 'light-blue':
	case 'lovely':
	case 'lovely_pink':
	case 'lr':
	case 'magic':
	case 'maroon':
	case 'midnight':
	case 'momonga':
	case 'nande-ya-nen':
	case 'narrow':
	case 'natrium':
	case 'nebula':
	case 'orange':
	case 'parabola':
	case 'plum':
	case 'pool_side':
	case 'rainy-season':
	case 'right':
	case 's-blue':
	case 's-pink':
	case 'sky':
	case 'sleepy_kitten':
	case 'snow_man':
	case 'spring':
	case 'tag':
	case 'tdiarynet':
	case 'treetop':
	case 'white-lingerie':
	case 'white_flower':
	case 'whiteout':
	case 'wood':
		$title_design_date = 0; // Select text design	
		break;

	case 'aqua':
	case 'arrow':
	case 'fluxbox':
	case 'fluxbox2':
	case 'fluxbox3':
	case 'ymck':
		$title_design_date = 2; // Show both :)
		break;
	}
}

// ------------------------------------------------------------
// tDiary 'Sidebar' position

// Default position
if (defined('TDIARY_SIDEBAR_POSITION')) {
	$sidebar = TDIARY_SIDEBAR_POSITION;
} else {
	$sidebar = 'another'; // Default: Show as an another page below

	// List of themes having sidebar CSS < (AllTheme / 2)
	// $ grep div.sidebar */*.css | cut -d: -f1 | cut -d/ -f1 | sort | uniq
	// $ wc -l *.txt
	//    142 list-sidebar.txt
	//    286 list-all.txt
	switch(TDIARY_THEME){
	case '3minutes':	/*FALLTHROUGH*/
	case '3pink':
	case 'aoikuruma':
	case 'aqua':
	case 'arrow':
	case 'artnouveau-blue':
	case 'artnouveau-green':
	case 'artnouveau-red':
	case 'asterisk-blue':
	case 'asterisk-lightgray':
	case 'asterisk-maroon':
	case 'asterisk-orange':
	case 'asterisk-pink':
	case 'autumn':
	case 'babypink':
	case 'be_r5':
	case 'bill':
	case 'bistro_menu':
	case 'bluely':
	case 'book':
	case 'book2-feminine':
	case 'book3-sky':
	case 'bright-green':
	case 'britannian':
	case 'bubble':
	case 'candy':
	case 'cat':
	case 'cherry':
	case 'cherry_blossom':
	case 'chiffon_leafgreen':
	case 'chiffon_pink':
	case 'chiffon_skyblue':
	case 'citrus':
	case 'clover':
	case 'colorlabel':
	case 'cool_ice':
	case 'cosmos':
	case 'curtain':
	case 'darkness-pop':
	case 'delta':
	case 'diamond_dust':
	case 'dice':
	case 'digital_gadgets':
	case 'dot-lime':
	case 'dot-orange':
	case 'dot-pink':
	case 'dot-sky':
	case 'dotted_line-blue':
	case 'dotted_line-green':
	case 'dotted_line-red':
	case 'emboss':
	case 'flower':
	case 'gear':
	case 'germany':
	case 'gray2':
	case 'green_leaves':
	case 'happa':
	case 'hatena':
	case 'hatena-black':
	case 'hatena-brown':
	case 'hatena-darkgray':
	case 'hatena-green':
	case 'hatena-lightblue':
	case 'hatena-lightgray':
	case 'hatena-lime':
	case 'hatena-orange':
	case 'hatena-pink':
	case 'hatena-purple':
	case 'hatena-red':
	case 'hatena-sepia':
	case 'hatena-tea':
	case 'hatena-white':
	case 'hatena_cinnamon':
	case 'hatena_japanese':
	case 'hatena_leaf':
	case 'hatena_rainyseason':
	case 'hatena_savanna-blue':
	case 'hatena_savanna-green':
	case 'hatena_savanna-red':
	case 'hatena_savanna-white':
	case 'hatena_water':
	case 'himawari':
	case 'jungler':
	case 'kaeru':
	case 'kitchen-classic':
	case 'kitchen-french':
	case 'kitchen-natural':
	case 'kotatsu':
	case 'light-blue':
	case 'loose-leaf':
	case 'marguerite':
	case 'matcha':
	case 'mizu':
	case 'momonga':
	case 'mono':
	case 'moo':
	case 'natrium':
	case 'nippon':
	case 'note':
	case 'old-pavement':
	case 'orange_flower':
	case 'pain':
	case 'pale':
	case 'paper':
	case 'parabola':
	case 'pettan':
	case 'pink-border':
	case 'plum':
	case 'puppy':
	case 'purple_sun':
	case 'rainy-season':
	case 'rectangle':
	case 'repro':
	case 'rim-daidaiiro':
	case 'rim-fujiiro':
	case 'rim-mizuiro':
	case 'rim-sakurairo':
	case 'rim-tanpopoiro':
	case 'rim-wakabairo':
	case 'russet':
	case 's-blue':
	case 'sagegreen':
	case 'savanna':
	case 'scarlet':
	case 'sepia':
	case 'simple':
	case 'sleepy_kitten':
	case 'smoking_black':
	case 'smoking_white':
	case 'spring':
	case 'sunset':
	case 'tdiarynet':
	case 'teacup':
	case 'thin':
	case 'tile':
	case 'tinybox':
	case 'tinybox_green':
	case 'treetop':
	case 'white_flower':
	case 'wine':
	case 'yukon':
	case 'zef':
		$sidebar = 'bottom'; // This is the default position of tDiary's.
		break;
	}

	// Manually adjust sidebar's default position
	switch(TDIARY_THEME){

	// 'bottom'
	case '90': // But upper navigatin UI will be hidden by sidebar
	case 'blackboard':
	case 'quirky':
	case 'quirky2':
		$sidebar = 'bottom';
		break;

	// 'top': Assuming sidebar is above of the body
	case 'autumn':	/*FALLTHROUGH*/
	case 'cosmos':
	case 'dice':	// Sidebar text (white) seems unreadable
	case 'happa':
	case 'kaeru':
	case 'note':
	case 'paper':	// Sidebar text (white) seems unreadable
	case 'sunset':
	case 'tinybox':	// For MSIE with narrow window width, seems meanless
	case 'tinybox_green':	// The same
	case 'ymck':
		$sidebar = 'top';
		break;

	// 'strict': Strict separation between sidebar and main contents needed
	case '3minutes':	/*FALLTHROUGH*/
	case '3pink':
	case 'aoikuruma':
	case 'aqua':
	case 'artnouveau-blue':
	case 'artnouveau-green':
	case 'artnouveau-red':
	case 'asterisk-blue':
	case 'asterisk-lightgray':
	case 'asterisk-maroon':
	case 'asterisk-orange':
	case 'asterisk-pink':
	case 'bill':
	case 'candy':
	case 'cat':
	case 'chiffon_leafgreen':
	case 'chiffon_pink':
	case 'chiffon_skyblue':
	case 'city':
	case 'clover':
	case 'colorlabel':
	case 'cool_ice':
	case 'dot-lime':
	case 'dot-orange':
	case 'dot-pink':
	case 'dot-sky':
	case 'dotted_line-blue':
	case 'dotted_line-green':
	case 'dotted_line-red':
	case 'flower':
	case 'germany':
	case 'green-tea':
	case 'hatena':
	case 'hatena-black':
	case 'hatena-brown':
	case 'hatena-darkgray':
	case 'hatena-green':
	case 'hatena-lightblue':
	case 'hatena-lightgray':
	case 'hatena-lime':
	case 'hatena-orange':
	case 'hatena-pink':
	case 'hatena-purple':
	case 'hatena-red':
	case 'hatena-sepia':
	case 'hatena-tea':
	case 'hatena-white':
	case 'hiki':
	case 'himawari':
	case 'kasumi':
	case 'kitchen-classic':
	case 'kitchen-french':
	case 'kitchen-natural':
	case 'kotatsu':
	case 'kurenai':
	case 'light-blue':
	case 'loose-leaf':
	case 'marguerite':
	case 'matcha':
	case 'memo':
	case 'memo2':
	case 'memo3':
	case 'mirage':
	case 'mizu':
	case 'mono':
	case 'moo':	// For MSIE, strict seems meanless
	case 'navy':
	case 'pict':
	case 'pokke-blue':
	case 'pokke-orange':
	case 'query000':
	case 'query011':
	case 'query101':
	case 'query110':
	case 'query111or':
	case 'puppy':
	case 'rainy-season':
	case 's-blue':	// For MSIE, strict seems meanless
	case 'sagegreen':
	case 'savanna':
	case 'scarlet':
	case 'sepia':
	case 'simple':
	case 'smoking_gray':
	case 'spring':
	case 'teacup':
	case 'wine':
		$sidebar = 'strict';
		break;

	// 'another': They have sidebar-design, but can not show it
	//  at the 'side' of the contents
	case 'babypink':	/*FALLTHROUGH*/
	case 'bubble':
	case 'cherry':
	case 'darkness-pop':
	case 'diamond_dust':
	case 'gear':
	case 'necktie':
	case 'pale':
	case 'pink-border':
	case 'rectangle':
	case 'russet':
	case 'smoking_black':
	case 'zef':
		$sidebar = 'another'; // Show as an another page below
		break;
	}

	// 'none': Show no sidebar
}
// Check menu (sidebar) is ready and $menubar is there
if ($sidebar == 'none') {
	$menu = FALSE;
} else {
	$menu = (arg_check('read') && is_page($GLOBALS['menubar']) &&
		exist_plugin_convert('menu'));
	if ($menu) {
		global $body_menu;
		$menu_body = & $body_menu;
	}
}

// ------------------------------------------------------------
// Code continuing ...

$lang  = & $_LANG['skin'];
$link  = & $_LINK;
$image = & $_IMAGE['skin'];
$rw    = ! PKWK_READONLY;

// Decide charset for CSS
$css_charset = 'iso-8859-1';
switch(UI_LANG){
	case 'ja': $css_charset = 'Shift_JIS'; break;
}

// ------------------------------------------------------------
// Output

// HTTP headers
pkwk_common_headers();
header('Cache-Control: no-cache');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=' . CONTENT_CHARSET);
header('ETag: ' . md5(MUTIME));

// HTML DTD, <html>, and receive content-type
$meta_content_type = (isset($pkwk_dtd)) ? pkwk_output_dtd($pkwk_dtd) : pkwk_output_dtd();

global $notify_from, $glossarypage, $menubar;
?>
	<head profile="http://purl.org/net/ns/metaprof">
		<?php echo $meta_content_type; ?>
		<meta http-equiv="content-style-type" content="text/css" />
		<meta http-equiv="content-script-type" content="text/javascript" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<meta name="author" content="<?php echo $modifier ?>" />
		<meta name="generator" content="PukiPlus <?php echo strip_tags(S_VERSION)?>" />
		<meta name="reply-to" content="mailto:<?php echo $notify_from ?>" />
<?php
if ($nofollow || ! $is_read) echo "\t\t".'<meta name="robots" content="NOINDEX,NOFOLLOW" />'."\n";
if ($title == $defaultpage) {
	echo "\t\t".'<title>'.$page_title.'</title>'."\n";
} elseif ($newtitle != '' && $is_read) {
	echo "\t\t".'<title>'.$newtitle.' - '.$page_title.'</title>'."\n";
} else {
	echo "\t\t".'<title>'.$title.' - '.$page_title.'</title>'."\n";
} ?>
		<link rel="alternate" href="<?php echo $_LINK['mixirss'] ?>" type="application/rss+xml" title="RSS" />
		<link rel="author" href="<?php echo $modifierlink ?>" title="<?php echo $modifier ?>" />
		<link rel="canonical" href="<?php echo $_LINK['reload'] ?>" />
		<link rel="contents" href="<?php echo $script.'?'.rawurlencode($menubar); ?>" title="<?php echo $menubar; ?>" />
		<link rel="glossary" href="<?php echo $script.'?'.rawurlencode($glossarypage); ?>" title="<?php echo $glossarypage; ?>" />
		<link rel="help" href="<?php echo $_LINK['help'] ?>" title="<?php echo $_LANG['skin']['help'] ?>" />
		<link rel="home" href="<?php echo $_LINK['top'] ?>" title="<?php echo $title ?>" />
		<link rel="index" href="<?php echo $_LINK['list']?>" title="<?php echo $_LANG['skin']['list'] ?>" />
		<link rel="search" href="<?php echo $_LINK['search'] ?>" title="<?php echo $_LANG['skin']['search'] ?>" />
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
		<link rel="stylesheet" href="<?php echo SKIN_URI ?>scripts.css.php" type="text/css" media="screen" charset="UTF-8" />
		<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/<?php echo $ui_theme; ?>/jquery-ui.css" type="text/css" />
		<link rev="made" href="mailto:<?php echo $notify_from ?>" title="Contact to <?php echo $modifier ?>" />
		<script type="text/javascript" src="http://www.google.com/jsapi<?php if ($google_api_key) echo '?key='.$google_api_key; ?>"></script>
		<script type="text/javascript">
//<![CDATA[
google.load("jquery", "1.4.2");
google.load("jqueryui", "1.8.2");
google.load("swfobject", "2.2");
<?php
echo 'var MODIFIED = "'.get_filetime($r_page).'";'."\n";
if ($r_page) echo 'var PAGE = "'.$r_page.'";'."\n";
if (exist_plugin_convert('js_init')) echo do_plugin_convert('js_init');
?>
var SCRIPT = "<?php echo $script ?>";
//]]></script>
		<script type="text/javascript" src="<?php echo SKIN_URI; ?>js/locale.js"></script>
<?php 
if ($use_local_time && exist_plugin_convert('tz')) echo "\t\t".'<script type="text/javascript" src="'.SKIN_URI.'tzCalculation_LocalTimeZone.js"></script>'."\n";
echo $head_tag;
if ($debug == true){
	foreach ($files as $script_file) {
		echo "\t\t".'<script type="text/javascript" src="'.SKIN_URI.'js/src/'.$script_file.'.js"></script>'."\n";
	}
}else{
	echo "\t\t".'<script type="text/javascript" src="'.SKIN_URI.'js/skin.js.php"></script>'."\n";
}
?>
		<link rel="stylesheet" type="text/css" media="all" href="<?php echo SKIN_URI.THEME_TDIARY_NAME ?>base.css" />
		<link rel="stylesheet" type="text/css" media="all" href="<?php echo SKIN_URI.THEME_TDIARY_NAME.$theme.'/'.$theme.'.css'; ?>" />
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo SKIN_URI ?>tdiary.css.php?charset=<?php echo $css_charset ?>&amp;color=<?php echo $css_theme ?>" charset="<?php echo $css_charset ?>" />
		<link rel="stylesheet" type="text/css" media="print"  href="<?php echo SKIN_URI ?>tdiary.css.php?charset=<?php echo $css_charset ?>&amp;color=<?php echo $css_theme ?>&amp;media=print" charset="<?php echo $css_charset ?>" />
		<?php if ($js_block) echo '<script type="text/javascript">'."\n".'//<![CDATA['."\n".$js_block.'//]]></script>'."\n"; ?>
	</head>
<?php flush(); ?>
	<body><!-- Theme:<?php echo htmlspecialchars($theme) . ' Sidebar:' . $sidebar ?> -->

<?php if ($menu && $sidebar == 'strict') { ?>
<!-- Sidebar top -->
		<div class="sidebar">
			<div id="menubar">
				<?php echo $menu_body ?>
			</div>
		</div><!-- class="sidebar" -->

		<div class="pkwk_body">
			<div class="main">
<?php } // if ($menu && $sidebar == 'strict') ?>

<!-- Navigation buttuns -->
<?php if (PKWK_SKIN_SHOW_NAVBAR) { ?>
				<div class="adminmenu"><div id="navigator">
<?php
function _navigator($key, $value = '', $javascript = '') {
	$lang = $GLOBALS['_LANG']['skin'];
	$link = $GLOBALS['_LINK'];
	if (! isset($lang[$key])) { echo 'LANG NOT FOUND'; return FALSE; }
	if (! isset($link[$key])) { echo 'LINK NOT FOUND'; return FALSE; }
	if (! PKWK_ALLOW_JAVASCRIPT) $javascript = '';

	echo '<span class="adminmenu"><a href="' . $link[$key] . '" ' . $javascript . '>' .
		(($value === '') ? $lang[$key] : $value) .
		'</a></span>';

	return TRUE;
}
_navigator('top');
echo '&nbsp;';

if ($is_page) {
	if ($rw) {
		_navigator('edit');
		if ($is_read && $function_freeze) {
			(! $is_freeze) ? _navigator('freeze') : _navigator('unfreeze');
		}
	}
	_navigator('diff');
	if ($do_backup) _navigator('backup');
	if ($rw && (bool)ini_get('file_uploads')) _navigator('upload');
	_navigator('reload');
	echo '&nbsp;';
}

if ($rw) _navigator('new');
_navigator('list');
if (arg_check('list')) _navigator('filelist');
_navigator('search');
_navigator('recent');
_navigator('help');

?>
			</div>
		</div>
<?php } else { ?>
		<div id="navigator"></div>
<?php } // PKWK_SKIN_SHOW_NAVBAR ?>

		<h1><?php echo $page_title ?></h1>

		<div class="calendar">
<?php if ($is_page && TDIARY_CALENDAR_DESIGN !== NULL) { ?>
<?php 	if(TDIARY_CALENDAR_DESIGN) { ?>
			<a href="<?php echo $link['reload'] ?>"><span class="small"><?php echo $link['reload'] ?></span></a>
<?php 	} else { ?>
			<?php require_once(PLUGIN_DIR . 'topicpath.inc.php'); echo plugin_topicpath_inline(); ?>
<?php	 } ?>
<?php } ?>
		</div>


<?php if ($menu && $sidebar == 'top') { ?>
<!-- Sidebar compat top -->
		<div class="sidebar">
			<div id="menubar">
				<?php echo $menu_body ?>
			</div>
		</div><!-- class="sidebar" -->
<?php } // if ($menu && $sidebar == 'top') ?>


<?php if ($menu && ($sidebar == 'top' || $sidebar == 'bottom')) { ?>
		<div class="pkwk_body">
			<div class="main">
<?php } ?>

				<hr class="sep" />

				<div class="day">

<?php
// Page title (page name)
$title = '&nbsp;';
if ($disable_backlink) {
	$title = ($_page != '') ? htmlspecialchars($_page) : $page; // Search, or something message
} else {
	$title = ($page != '')  ? $page : htmlspecialchars($_page);
}
$title_date = $title_text = '&nbsp;';
switch($title_design_date){
case 1: $title_date = & $title; break;
case 0: $title_text = & $title; break;
default:
	// Show both (for debug or someting)
	$title_date = & $title;
	$title_text = & $title;
	break;
}
?>
				<h2><span class="date"><?php  echo $title_date ?></span>
				    <span class="title"><?php echo $title_text ?></span></h2>

					<div class="body">
						<div class="section">
<?php
	// For read and preview: tDiary have no <h2> inside body
	$body = preg_replace('#<h2 ([^>]*)>(.*?)<a class="anchor_super" ([^>]*)>.*?</a></h2>#',
		'<h3 $1><a $3><span class="sanchor">_</span></a> $2</h3>', $body);
	$body = preg_replace('#<h([34]) ([^>]*)>(.*?)<a class="anchor_super" ([^>]*)>.*?</a></h\1>#',
		'<h$1 $2><a $4>_</a> $3</h$1>', $body);
	$body = preg_replace('#<h2 ([^>]*)>(.*?)</h2>#',
		'<h3 $1><span class="sanchor">_</span> $2</h3>', $body);
	if ($is_read) {
		// Read
		echo $body;
	} else {
		// Edit, preview, search, etc
		echo preg_replace('/(<form) (action="' . preg_quote($script, '/') .
			')/', '$1 class="update" $2', $body);
	}
?>
						</div>
					</div><!-- class="body" -->


<?php if ($notes != '') { ?>
					<div class="comment"><!-- Design for tDiary "Comments" -->
						<div class="caption">&nbsp;</div>
						<div class="commentbody"><br />
<?php
$notes = preg_replace('#<span class="small">(.*?)</span>#', '<p>$1</p>', $notes);
echo preg_replace('#<a (id="notefoot_[^>]*)>(.*?)</a>#',
	'<div class="commentator"><a $1><span class="canchor"></span> ' .
	'<span class="commentator">$2</span></a>' .
	'<span class="commenttime"></span></div>', $notes);
?>
						</div>
					</div>
<?php } ?>

<?php if ($attaches != '') { ?>
					<div class="comment">
						<div class="caption">&nbsp;</div>
						<div class="commentshort">
							<?php echo $attaches ?>
						</div>
					</div>
<?php } ?>

<?php if ($related != '') { ?>
					<div class="comment">
						<div class="caption">&nbsp;</div>
						<div class="commentshort">
							Link: <?php echo $related ?>
						</div>
					</div>
<?php } ?>

<!-- Design for tDiary "Today's referrer" -->
					<div class="referer"><?php if ($lastmodified != '') echo 'Last-modified: ' . $lastmodified; ?></div>

				</div><!-- class="day" -->

				<hr class="sep" />

<?php if ($menu && ($sidebar == 'top' || $sidebar == 'bottom')) { ?>
			</div><!-- class="main" -->
		</div><!-- class="pkwk_body" -->
<?php } ?>

<?php if ($menu && $sidebar == 'another') { ?>
<!-- Sidebar another -->
			<div class="pkwk_body">
				<h1>&nbsp;</h1>
				<div class="calendar"></div>
				<hr class="sep" />
				<div class="day">
					<h2><span class="date"></span><span class="title">&nbsp;</span></h2>
					<div class="body">
						<div class="section">
							<?php echo $menu_body ?>
						</div>
					</div>
					<div class="referer"></div>
				</div>
				<hr class="sep" />
			</div><!-- class="pkwk_body" -->

			<div class="pkwk_body">
				<div class="main">
<?php } // if ($menu && $sidebar == 'another') ?>


<?php if ($menu && ($sidebar == 'top' || $sidebar == 'bottom')) { ?>
			</div><!-- class="main" -->
		</div><!-- class="pkwk_body" -->
<?php } ?>


<?php if ($menu && $sidebar == 'bottom') { ?>
<!-- Sidebar compat bottom -->
					<div class="sidebar">
						<div id="menubar">
							<?php echo $menu_body ?>
						</div>
					</div><!-- class="sidebar" -->
<?php } // if ($menu && $sidebar == 'bottom') ?>


				<div class="footer">
<!-- Toolbar -->
					<?php if (PKWK_SKIN_SHOW_TOOLBAR && exist_plugin('toolbar')) echo do_plugin_convert('toolbar','top,|,edit,freeze,diff,backup,upload,copy,rename,reload,|,new,list,search,recent,|,help,|,mixirss'); ?>

<!-- Copyright etc -->
					<address>Founded by <a href="<?php echo $modifierlink ?>"><?php echo $modifier ?></a></address>
					<?php echo S_COPYRIGHT ?>.<br />
					HTML convert time: <?php echo $taketime ?> sec.

				</div><!-- class="footer" -->

<?php if ($menu && ($sidebar != 'top' && $sidebar != 'bottom')) { ?>
			</div><!-- class="main" -->
		</div><!-- class="pkwk_body" -->
<?php } ?>

<?php if (exist_plugin_convert('tz')) echo do_plugin_convert('tz'); ?>
<?php echo $foot_tag ?>
	</body>
</html>
