<?php
// PukiPlus
// $Id: counter.inc.php,v 1.19.9 2014/04/26 17:38:00 Logue Exp $
// Copyright (C)
//   2010-2014 PukiWiki Advance Developers Team
//   2004-2007 PukiWiki Plus! Team
//   2002-2005, 2007 PukiWiki Developers Team
//   2002 Y.MASUI GPL2 http://masui.net/pukiwiki/ masui@masui.net
// License: GPL2
//
// Counter plugin

// Counter file's suffix
define('PLUGIN_COUNTER_SUFFIX', '.count');

// Report one
function plugin_counter_inline()
{
	global $vars;

	if (!isset($vars['page'])) return null;
	// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
	$args = func_get_args(); // with array_shift()
	$arg = strtolower(array_shift($args));
	switch ($arg) {
	case ''     : $arg = 'total'; /*FALLTHROUGH*/
	case 'total': /*FALLTHROUGH*/
	case 'today': /*FALLTHROUGH*/
	case 'yesterday':
		$counter = plugin_counter_get_count($vars['page']);
		return $counter[$arg];
	case 'none':
		$counter = plugin_counter_get_count($vars['page']);
		return '';
	default:
		return '<span class="text-warning">&amp;counter([total|today|yesterday|none]);</span>';
	}
}

// Report all
function plugin_counter_convert()
{
	global $vars;
	
	if ($vars['page'] === '') return null;
	$counter = plugin_counter_get_count($vars['page']);
	return <<<EOD
<div class="plugin-counter">
Counter:	<var>{$counter['total']}</var>,
Today:		<var>{$counter['today']}</var>,
Yesterday:	<var>{$counter['yesterday']}</var>
</div>
EOD;
}

// Return a summary
function plugin_counter_get_count($page)
{
	global $vars;
	static $counters = array();
	static $default;
	static $localtime;

	if (! isset($localtime)) {
		list($zone, $zonetime) = set_timezone(DEFAULT_LANG);
		$localtime = UTIME + $zonetime;
	}

	if (! isset($default)) {
		$default = array(
			'total'     => 0,
			'date'      => gmdate('Y/m/d', $localtime),
			'today'     => 0,
			'yesterday' => 0,
			'ip'        => '');
	}

	if (! is_page($page)) return $default;
	if (isset($counters[$page])) return $counters[$page];

	// Set default
	$counters[$page] = $default;
	$modify = FALSE;

	// Open
	$file = COUNTER_DIR . encode($page) . PLUGIN_COUNTER_SUFFIX;
	touch($file);
	$fp = fopen($file, 'r+')
		or die('counter.inc.php: Cannot open COUTER_DIR/' . basename($file));
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	rewind($fp);

	// Read
	foreach ($default as $key=>$val) {
		// Update
		$counters[$page][$key] = rtrim(fgets($fp, 256));
		if (feof($fp)) break;
	}

	// Anothoer day?
	$remoteip = get_remoteip();
	if ($counters[$page]['date'] != $default['date']) {
		$modify = TRUE;
		$yesterday = gmmktime(0,0,0, gmdate('m',$localtime), gmdate('d',$localtime)-1, gmdate('Y',$localtime));
		$is_yesterday = ($counters[$page]['date'] == gmdate('Y/m/d', $yesterday));
		$counters[$page]['ip']        = $remoteip;
		$counters[$page]['date']      = $default['date'];
		$counters[$page]['yesterday'] = $is_yesterday ? $counters[$page]['today'] : 0;
		$counters[$page]['today']     = 1;
		$counters[$page]['total']++;
	} else if ($counters[$page]['ip'] != $remoteip) {
		// Not the same host
		$modify = TRUE;
		$counters[$page]['ip']        = $remoteip;
		$counters[$page]['today']++;
		$counters[$page]['total']++;
	}

	// Modify
	if ($modify && $vars['cmd'] == 'read') {
		rewind($fp);
		ftruncate($fp, 0);
		foreach (array_keys($default) as $key)
			fputs($fp, $counters[$page][$key] . "\n");
	}

	// Close
	@flock($fp, LOCK_UN);
	fclose($fp);

	return $counters[$page];
}
/* End of file counter.inc.php */
/* Location: ./wiki-common/plugin/counter.inc.php */
