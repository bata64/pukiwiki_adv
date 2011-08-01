<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: calendar_viewer.inc.php,v 1.37 2011/01/25 15:01:01 henoheno Exp $
// Copyright (C) 2002-2005, 2007, 2011 PukiWiki Developers Team
//           
// License: GPL v2 or (at your option) any later version
// Calendar viewer plugin - List pages that calendar/calnedar2 plugin created
// (Based on calendar and recent plugin)

// Notice: This plugin based on minicalendar_viewer.inc.php from PukiWiki Plus!.
//         Adv. is rejected to minicalendar.inc.php and minicalendar_viewer.inc.php.

// Page title's date format
//  * See PHP date() manual for detail
//  * '$\w' = weeklabel defined in $_msg_week
define('PLUGIN_CALENDAR_VIEWER_DATE_FORMAT',
	//	FALSE         // 'pagename/2004-02-09' -- As is
	//	'D, d M, Y'   // 'Mon, 09 Feb, 2004'
	//	'F d, Y'      // 'February 09, 2004'
	//	'[Y-m-d]'     // '[2004-02-09]'
		'Y/n/j ($\w)' // '2004/2/9 (Mon)'
	);

// ----

define('PLUGIN_CALENDAR_VIEWER_USAGE',
	'#calendar_viewer(pagename,this|yyyy-mm|n|x*y[,mode[,separater]])');
/*
 ** pagename
 * - A working root of calendar or calendar2 plugin
 *   pagename/2004-12-30
 *   pagename/2004-12-31
 *   ...
 *
 ** (yyyy-mm|n|this)
 * this    - Show 'this month'
 * yyyy-mm - Show pages at year:yyyy and month:mm
 * n       - Show first n pages
 * x*n     - Show first n pages from x-th page (0 = first)
 *
 ** [mode]
 * past   - Show today, and the past below. Recommended for ChangeLogs and diaries (default)
 * future - Show today, and the future below. Recommended for event planning and scheduling
 * view   - Show all, from the past to the future
 *
 ** [separater]
 * - Specify separator of yyyy/mm/dd
 * - Default: '-' (yyyy-mm-dd)
 *
 * TODO
 *  Stop showing links 'next month' and 'previous month' with past/future mode for 'this month'
 *    #calendar_viewer(pagename,this,past)
 */
// ����ɽ�����
defined('PLUGIN_CALENDAR_MAX_VIEWS')			or define('PLUGIN_CALENDAR_MAX_VIEWS', 3);
// ������ȿ�Ǥ�����
defined('PLUGIN_CALENDAR_VIEWER_HOLIDAYVIEW')	or define('PLUGIN_CALENDAR_VIEWER_HOLIDAYVIEW',	TRUE);
// ���������ɽ��
defined('PLUGIN_CALENDAR_VIEWER_COMMENT')		or define('PLUGIN_CALENDAR_VIEWER_COMMENT',		FALSE);
// TrackBack��󥯤�ɽ��
defined('PLUGIN_CALENDAR_VIEWER_TRACKBACK')		or define('PLUGIN_CALENDAR_VIEWER_TRACKBACK',	TRUE);

function plugin_calendar_viewer_convert()
{
	global $vars, $get, $post, $script, $_labels;
//	global $_msg_calendar_viewer_right, $_msg_calendar_viewer_left;
//	global $_msg_calendar_viewer_restrict, $_err_calendar_viewer_param2;
	global $_symbol_paraedit, $trackback, $pkwk_dtd;

	$_calendar_viewer_msg = array(
		'_err_param2'	=> T_('Wrong second parameter.'),
		'_msg_right'	=> T_('Next %d &gt;&gt;'),
		'_msg_left'		=> T_('&lt;&lt; Prev %d'),
		'_msg_restrict'	=> T_('Due to the blocking, the calendar_viewer cannot refer to $1.'),
		'_title_format'	=> T_('%1s, %2s %3s %4s')	// Sat, 12 Mar 2011
	);
	
	static $viewed = array();

	if (func_num_args() < 2)
		return PLUGIN_CALENDAR_VIEWER_USAGE . '<br />' . "\n";

	$func_args = func_get_args();

	// Default values
	$pagename    = $func_args[0];	// ���Ȥʤ�ڡ���̾
	$page_YM     = '';	// ����ɽ������ǯ��
	$limit_base  = 0;	// ��Ƭ��������Ʋ��ڡ����ܤ���ɽ�����뤫 (��Ƭ)
	$limit_pitch = 0;	// ����Ť�ɽ�����뤫
	$limit_page  = 0;	// ����������ڡ�����
	$mode        = 'past';	// ư��⡼��
	$date_sep    = '-';	// ���դΥ��ѥ졼�� calendar2�ʤ� '-', calendar�ʤ� ''

	// Check $func_args[1]
	$matches = array();
	if (preg_match('/[0-9]{4}' . $date_sep . '[0-9]{2}/', $func_args[1])) {
		// ����ǯ��ΰ���ɽ��
		$page_YM     = $func_args[1];
		$limit_page  = 31;
	} else if (preg_match('/this/si', $func_args[1])) {
		// ����ΰ���ɽ��
		$page_YM     = get_date('Y' . $date_sep . 'm');
		$limit_page  = 31;
	} else if (preg_match('/^[0-9]+$/', $func_args[1])) {
		// n��ʬɽ��
		$limit_pitch = $func_args[1];
		$limit_page  = $func_args[1];
	} else if (preg_match('/(-?[0-9]+)\*([0-9]+)/', $func_args[1], $matches)) {
		// ��Ƭ�������� x �ڡ����ܤ��顢y��Ť�ɽ��
		$limit_base  = $matches[1];
		$limit_pitch = $matches[2];
		$limit_page  = $matches[1] + $matches[2]; // �ɤ����Ф� + ɽ������
	} else {
		return '#calendar_viewer(): ' . $_calendar_viewer_msg['_err_param2'] . '<br />' . "\n";
	}

	// $func_args[2]: Mode setting
	if (isset($func_args[2]) && preg_match('/^(past|pastex|view|viewex|future|futureex)$/si', $func_args[2]))
		$mode = $func_args[2];

	// $func_args[3]: Change default delimiter
	if (isset($func_args[3])) $date_sep = $func_args[3];

	// Avoid Loop etc.
	if (isset($viewed[$pagename])) {
		if ($viewed[$pagename] > PLUGIN_CALENDAR_MAX_VIEWS) {
			$s_page = htmlsc($pagename);
			return '#calendar_viewer(): You already view: '.$s_page.'<br />';
		}
		$viewed[$pagename]++; // Valid
	} else {
		$viewed[$pagename]=1; // Valid
	}

	// ����ɽ������ڡ���̾�ȥե�����̾�Υѥ����󡡥ե�����̾�ˤ�ǯ���ޤ�
	if ($pagename == '') {
		// pagename̵����yyyy-mm-dd���б����뤿��ν���
		$pagepattern     = '';
		$pagepattern_len = 0;
		$filepattern     = encode($page_YM);
		$filepattern_len = strlen($filepattern);
	} else {
		$pagepattern     = strip_bracket($pagename) . '/';
		$pagepattern_len = strlen($pagepattern);
		$filepattern     = encode($pagepattern . $page_YM);
		$filepattern_len = strlen($filepattern);
	}

	// �ڡ����ꥹ�Ȥμ���
	$pagelist = array();
	if ($dir = @opendir(DATA_DIR)) {
		$_date = get_date('Y' . $date_sep . 'm' . $date_sep . 'd');
		$page_date  = '';
		while($file = readdir($dir)) {
			if ($file == '..' || $file == '.') continue;
			if (substr($file, 0, $filepattern_len) != $filepattern) continue;

			$page      = decode(trim(preg_replace('/\.txt$/', ' ', $file)));
			$page_date = substr($page, $pagepattern_len);

			// Verify the $page_date pattern (Default: yyyy-mm-dd).
			// Past-mode hates the future, and
			// Future-mode hates the past.
			if ((plugin_calendar_viewer_isValidDate($page_date, $date_sep) == FALSE) || 
				($page_date > $_date && ($mode == 'past')) ||
				($page_date < $_date && ($mode == 'future')) ||
				// from Plus!
				($page_date >= $_date) && ($mode=='pastex') ||
				($page_date <= $_date) && ($mode=='futureex')
			)
					continue;

			$pagelist[] = $page;
		}
	}
	closedir($dir);

	if ($mode == 'past' || $mode == 'pastex' || $mode =='viewex') {
		rsort($pagelist, SORT_STRING);	// New => Old
	} else {
		sort($pagelist, SORT_STRING);	// Old => New
	}

	// Include start
	$tmppage     = $vars['page'];
	
	$return_body = '';

	// $limit_page �η���ޤǥ��󥯥롼��
	$tmp = max($limit_base, 0); // Skip minus
	while ($tmp < $limit_page) {
		if (! isset($pagelist[$tmp])) break;

		$page = $pagelist[$tmp];
		$get['page'] = $post['page'] = $vars['page'] = $page;

		// �����Ǳ������Ĥ����������ɽ������
		if (check_readable($page, FALSE, FALSE)) {
			if (function_exists('convert_filter')) {
				$body = convert_html(convert_filter(get_source($page)));
			} else {
				$body = convert_html(get_source($page));
			}
		} else {
			$body = str_replace('$1', $page, $_calendar_viewer_msg['_msg_restrict']);
		}

		if (PLUGIN_CALENDAR_VIEWER_DATE_FORMAT !== FALSE) {
			$time = strtotime(basename($page)); // $date_sep must be assumed '-' or ''!
			if ($time == -1) {
				$s_page = htmlsc($page); // Failed. Why?
			} else {
				$week   = $_labels['week'][date('w', $time)][0];
				$month  = $_labels['month'][preg_replace('/^0/','',date('m', $time))][0];
				$s_page = htmlsc(str_replace(
						array('$w','$m' ),
						array($week, $month),
						date(PLUGIN_CALENDAR_VIEWER_DATE_FORMAT, $time)
					));
			}
		} else {
			$s_page = htmlsc($page);
		}

		// if (PKWK_READONLY) {
		if (auth::check_role('readonly')) {
			$link = get_page_uri($page);
		} else {
			$link = get_cmd_uri('edit',$page,'',array('page'=>$page));
		}
		$link = '<a class="anchor_super" href="' . $link . '">' . $_symbol_paraedit . '</a>';

		$head   = '<h1>' . $s_page . $link .'</h1>' . "\n";
		$page_title = basepagename($page);
		$tail = '';
		if (PLUGIN_CALENDAR_VIEWER_HOLIDAYVIEW === TRUE) {
			$time = strtotime($page_title);
			if ($time != -1) {
				$yy = intval(date('Y', $time));
				$mm = intval(date('n', $time));
				$dd = intval(date('d', $time));

				$h_today = public_holiday($yy, $mm, $dd); 
				
				
				if ($h_today['rc'] != 0) {
					$classname = 'date_holiday';
					$weekclass = 'week_sun';
				}else{
					switch($h_today['w']){
						case 0:
							$classname = 'date_holiday';
							$weekclass = 'week_sun';
						break;
						case 6:
							$classname = 'date_weekend';
							$weekclass = 'week_sat';
						default:
							$classname = 'date_weekday';
							$weekclass = 'week_day';
						break;
					}
				}
			}
		}
		if (PLUGIN_CALENDAR_VIEWER_COMMENT === TRUE) {
			if (is_page(':config/plugin/addline/comment') && exist_plugin_inline('addline')) {
				$comm = convert_html(array('&addline(comment,above){comment};'));
				$comm = preg_replace(array("'<p>'si","'</p>'si"), array("",""), $comm );
				$tail .= str_replace('>comment','><img src="'.IMAGE_URI.'plus/comment.png" width="15" height="15" alt="Comment" title="Comment" />Comment',$comm);
			}
		}
		if (PLUGIN_CALENDAR_VIEWER_TRACKBACK === TRUE && $trackback) {
			$tb_link = get_cmd_uri('tb','','',array(
				'__mode'=>'view',
				'tb_id'=>tb_get_id($page)
			));
			$tail .= '<a class="pkwk-icon_linktext cmd-trackback" href="'.$tb_link.'">'.'Trackback(' . tb_count($page) . ')'.'</a>'."\n";
		}
		$page_id= str_replace('/','_',$page);
		$return_body .= (($pkwk_dtd === PKWK_DTD_HTML_5) ? '<article id="'.$page_id.'">' : '<div id="'.$page_id.'">')."\n";
		$return_body .= $head . $body;
		$return_body .= (($pkwk_dtd === PKWK_DTD_HTML_5) ? '</article>' : '</div>')."\n";

		++$tmp;
	}

	// �����ǡ�����Υ�󥯤�ɽ��
	// ?plugin=calendar_viewer&file=�ڡ���̾&date=yyyy-mm
	$page = substr($pagepattern, 0, $pagepattern_len - 1);
	$r_page = rawurlencode($page);

	if ($page_YM != '') {
		// ǯ��ɽ����
		$date_sep_len = strlen($date_sep);
		$this_year    = substr($page_YM, 0, 4);
		$this_month   = substr($page_YM, 4 + $date_sep_len, 2);

		// ����
		$next_year  = $this_year;
		$next_month = $this_month + 1;
		if ($next_month > 12) {
			++$next_year;
			$next_month = 1;
		}
		$next_YM = sprintf('%04d%s%02d', $next_year, $date_sep, $next_month);

		// ����
		$prev_year  = $this_year;
		$prev_month = $this_month - 1;
		if ($prev_month < 1) {
			--$prev_year;
			$prev_month = 12;
		}
		$prev_YM = sprintf('%04d%s%02d', $prev_year, $date_sep, $prev_month);
		if ($mode == 'past') {
			$right_YM   = $prev_YM;
			$right_text = $prev_YM . '&gt;&gt;'; // >>
			$left_YM    = $next_YM;
			$left_text  = '&lt;&lt;' . $next_YM; // <<
		} else {
			$left_YM    = $prev_YM;
			$left_text  = '&lt;&lt;' . $prev_YM; // <<
			$right_YM   = $next_YM;
			$right_text = $next_YM . '&gt;&gt;'; // >>
		}
	} else {
		// n��ɽ����
		if ($limit_base <= 0) {
			$left_YM = ''; // ɽ�����ʤ� (���������ι��ܤϤʤ�)
		} else {
			$left_YM   = $limit_base - $limit_pitch . '*' . $limit_pitch;
			$left_text = sprintf($_calendar_viewer_msg['_msg_left'], $limit_pitch);

		}
		if ($limit_base + $limit_pitch >= count($pagelist)) {
			$right_YM = ''; // ɽ�����ʤ� (�������ι��ܤϤʤ�)
		} else {
			$right_YM   = $limit_base + $limit_pitch . '*' . $limit_pitch;
			$right_text = sprintf($_calendar_viewer_msg['_msg_right'], $limit_pitch);
		}
	}

	// �ʥӥ������ѤΥ�󥯤��������ɲ�
	if ($left_YM != '' || $right_YM != '') {
		$s_date_sep = htmlsc($date_sep);
		$left_link = $right_link = '';
		$link = $script . '?plugin=calendar_viewer&amp;mode=' . $mode .
			'&amp;file=' . $r_page . '&amp;date_sep=' . $s_date_sep . '&amp;';
		if ($left_YM != '')
			$left_link = '<a href="'.get_cmd_uri('calendar_viewer', '', '', array('mode'=>$mode,'file'=>$page,'date_sep'=>$date_sep,'date'=>$left_YM)).'">'.$left_text.'</a>';
		if ($right_YM != '')
			$right_link = '<a href="'.get_cmd_uri('calendar_viewer', '', '', array('mode'=>$mode,'file'=>$page,'date_sep'=>$date_sep,'date'=>$right_YM)).'">'.$right_text.'</a>';
		
		$center_link = '<a href="'.get_page_uri($page).'">'.$page.'</a>';

		// past mode��<<�� ��>> ¾��<<�� ��>>
		$nav = (($pkwk_dtd === PKWK_DTD_HTML_5) ? '<nav class="calendar_viewer_navi">' : '<div class="calendar_viewer_navi">')."\n";
		$nav .=  <<<EOD
<ul class="navi">
	<li class="navi_left">{$left_link}</li>
	<li class="navi_none">{$center_link}</li>
	<li class="navi_right">{$right_link}</li>
</ul>
<hr />
EOD;
		$nav .= (($pkwk_dtd === PKWK_DTD_HTML_5) ? '</nav>' : '</div>')."\n";
	}

	$get['page'] = $post['page'] = $vars['page'] = $tmppage;

	return $nav.$return_body;
}

function plugin_calendar_viewer_action()
{
	global $vars, $get, $post, $script;

	$date_sep = '-';

	$return_vars_array = array();

	$page = strip_bracket($vars['page']);
	$vars['page'] = '*';
	if (isset($vars['file'])) $vars['page'] = $vars['file'];

	$date_sep = $vars['date_sep'];

	$page_YM = $vars['date'];
	if ($page_YM == '') $page_YM = get_date('Y' . $date_sep . 'm');
	$mode = $vars['mode'];

	$args_array = array($vars['page'], $page_YM, $mode, $date_sep);
	$return_vars_array['body'] = call_user_func_array('plugin_calendar_viewer_convert', $args_array);

	//$return_vars_array['msg'] = 'calendar_viewer ' . $vars['page'] . '/' . $page_YM;
	$return_vars_array['msg'] = 'calendar_viewer ' . htmlsc($vars['page']);
	if ($vars['page'] != '') $return_vars_array['msg'] .= '/';
	if (preg_match('/\*/', $page_YM)) {
		// ������n��ɽ���λ��Ϥʤ�ƥڡ���̾�ˤ����餤����
	} else {
		$return_vars_array['msg'] .= htmlsc($page_YM);
	}

	$vars['page'] = $page;
	return $return_vars_array;
}

function plugin_calendar_viewer_isValidDate($aStr, $aSepList = '-/ .')
{
	$matches = array();
	if ($aSepList == '') {
		// yyymmdd�Ȥ��ƥ����å��ʼ�ȴ��(^^;��
		return checkdate(substr($aStr, 4, 2), substr($aStr, 6, 2), substr($aStr, 0, 4));
	} else if (preg_match("/^([0-9]{2,4})[$aSepList]([0-9]{1,2})[$aSepList]([0-9]{1,2})$/", $aStr, $matches) ) {
		return checkdate($matches[2], $matches[3], $matches[1]);
	} else {
		return FALSE;
	}
}
?>