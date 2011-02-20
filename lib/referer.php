<?php
// PukiWiki Plus! - Yet another WikiWikiWeb clone.
// $Id: referer.php,v 1.8.5 2010/10/25 19:43:00 Logue Exp $
// Copyright (C)
//   2010      PukiWiki Advance Developers Team
//   2006-2008 PukiWiki Plus! Team
//   2003      Originally written by upk
// License: GPL v2 or (at your option) any later version
//
// Referer function

define('REFERER_SPAM_LOG', CACHE_DIR.'referer_spam.log');

function ref_get_data($page, $uniquekey=1)
{
	$file = ref_get_filename($page);
	if (! file_exists($file)) return array();

	$result = array();
	$fp = @fopen($file, 'r');
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	rewind($fp);
	while ($data = @fgets($fp, 8192)) {
		$data = csv_explode("\t", $data);
		$result[rawurldecode($data[$uniquekey])] = $data;
	}
	@flock($fp, LOCK_UN);
	fclose ($fp);

	return $result;
}

function ref_save($page)
{
	global $referer, $use_spam_check;

	$url = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';

	// if (PKWK_READONLY || ! $referer || empty($_SERVER['HTTP_REFERER'])) return TRUE;
	// if (auth::check_role('readonly') || ! $referer || empty($_SERVER['HTTP_REFERER'])) return TRUE;
	if (! $referer || empty($url)) return TRUE;

	// Validate URI (Ignore own)
	$parse_url = parse_url($url);
	if ($parse_url === FALSE || !isset($parse_url['host']) || $parse_url['host'] == $_SERVER['HTTP_HOST'])
		return TRUE;
		
	// Blocking SPAM
	if ($use_spam_check['referer'] && SpamCheck($parse_url['host'])) return TRUE;
	if (is_refspam($url) === true){
		return TRUE;
	}

	if (! is_dir(REFERER_DIR))      die_message('No such directory: REFERER_DIR');
	if (! is_writable(REFERER_DIR)) die_message('Permission denied to write: REFERER_DIR');

	// Update referer data
	if (preg_match("[,\"\n\r]", $url))
		$url = '"' . str_replace('"', '""', $url) . '"';

	$data  = ref_get_data($page, 3);
	$d_url = rawurldecode($url);
	if (! isset($data[$d_url])) {
		$data[$d_url] = array(
			'',    // [0]: Last update date
			UTIME, // [1]: Creation date
			0,     // [2]: Reference counter
			$url,  // [3]: Referer header
			1      // [4]: Enable / Disable flag (1 = enable)
		);
	}
	$data[$d_url][0] = UTIME;
	$data[$d_url][2]++;

	$filename = ref_get_filename($page);
	pkwk_touch_file($filename);
	$fp = fopen($filename, 'w');
	if ($fp === FALSE) return FALSE;
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	rewind($fp);
	foreach ($data as $line) {
		$str = trim(join("\t", $line));
		if ($str != '') fwrite($fp, $str . "\n");
	}
	@flock($fp, LOCK_UN);
	fclose($fp);

	return TRUE;
}

// Get file name of Referer data
function ref_get_filename($page)
{
	return REFERER_DIR . encode($page) . '.ref';
}

// Count the number of TrackBack pings included for the page
function ref_count($page)
{
	$filename = ref_get_filename($page);
	if (!file_exists($filename)) return 0;
	if (!is_readable($filename)) return 0;
	if (!($fp = fopen($filename,'r'))) return 0;
	$i = 0;
	while ($data = @fgets($fp, 4096)) $i++;
	fclose($fp);
	unset($data);
	return $i;
}

define('CONFIG_REFERER_BL',		'plugin/referer/BlackList');
define('CONFIG_REFERER_WL',		'plugin/referer/WhiteList');

// Referer��spam���̃`�F�b�N
function is_refspam($url){
	// URL���p�[�X
	$parse_url = parse_url($url);
	// �T�C�g�̃��[�g�̃A�h���X
	$script = get_script_uri();

	// �h���C���͏������ɂ���B�i�h���C���̑啶���������͋�ʂ��Ȃ��̂ƁAstrpos��stripos�ő��x�ɔ{���炢�Ⴂ�����邽�߁j
	// �Ǝ��h���C���łȂ��ꍇ���l�����ăp�X�i/~hoge/�j��]������B
	// QueryString�i?aa=bb�j�͕]�����Ȃ��B
	$condition = strtolower($parse_url['host']).$parse_url['path'];	
	
	// �z���C�g���X�g�ɓ����Ă���ꍇ�̓`�F�b�N���Ȃ�
	$WhiteList = new Config(CONFIG_REFERER_WL);
	$WhiteList->read();
	$WhiteListLines = $WhiteList->get('WhiteList');
	foreach ($WhiteListLines as $WhiteListLine){
		if( strpos($WhiteListLine[0], $condition) !== false){
			return false;
		}
	}
	unset($WhiteList,$WhiteListLines,$WhiteListLine);

	$line = array();
	$is_refspam = false;
	
	// �u���b�N���X�g���m�F
	$BlackList = new Config(CONFIG_REFERER_BL);
	$BlackList->read();
	$BlackListLines = $BlackList->get('BlackList');
	/* |~referer|~count|~ban|h */
	foreach ($BlackListLines as $BlackListLine){
		if( strpos($BlackListLine[0],$condition) !== false){
			// �ߋ��ɓ������t�@���[����A�N�Z�X���������ꍇ

			if ($BlackListLine[2] == 1 || $BlackListLine[1] == 10){
				// �o���t���O�������Ă���ꍇ���A�������l��10��𒴂����ꍇ�o��
				$BAN = true;
			}

			$BlackListLine[1]++;
			$is_refspam = true;
		}
		$line[] = array($BlackListLine[0],$BlackListLine[1],$BlackListLine[2]);
	}
	if ($is_refspam === false){
		// ���t�@���[�ɃT�C�g�ւ̃A�h���X�����݂��邩���m�F
		$is_refspam = is_not_valid_referer($url, $script);
		
		if ($is_refspam === true){
			// ���݂��Ȃ��ꍇ�̓X�p�����X�g�ɒǉ�
			$line[] = array($condition,1,0);
		}
	}
	// �u���b�N���X�g���X�V
	$BlackList->put('BlackList',$line);
	$BlackList->write();
	unset($BlackList,$BlackListLines,$BlackListLine,$line);

	if ($BAN === true) die();
	
	if ($is_refspam === true){
		// �X�p���������ꍇ�A���O�Ɋ���ۑ�����B
		$log = array(
			MUTIME,
			$url,
			$_SERVER['HTTP_USER_AGENT'],
			$_SERVER['REMOTE_ADDR']
		);
		$filename = REFERER_SPAM_LOG;
		pkwk_touch_file($filename);
		$fp = fopen($filename, 'a');
		@flock($fp, LOCK_EX);
		fwrite($fp, join("\t",$log)."\n" );
		@flock($fp, LOCK_UN);
		fclose($fp);
	}

	return $is_refspam;
}

// �����N���ɃA�N�Z�X���Ď��T�C�g�ւ̃A�h���X�����݂��邩�̃`�F�b�N
function is_not_valid_referer($ref,$rel){
	// �{���͐��K�����ꂽ�A�h���X�Ń`�F�b�N����ׂ����낤���A
	// �߂�ǂ�������X�N���v�g�̃A�h���X���܂ނ��Ń`�F�b�N
	// global $vars;
	// $script = get_page_absuri(isset($vars['page']) ? $vars['page'] : '');

	$script = parse_url($rel);
	$condition = $script['host'].$script['path'];	// QueryString�͕]�����Ȃ��B

	// useragent setting
	$header = "User-Agent: Mozilla/5.0 (Nintendo Family Computer; U; Family Basic 2.0A; ja-JP) AppleWebKit/525.19 (KHTML, like Gecko) Version/3.1.2 Safari/525.21\r\n";	// �t�@�~�R����Safari���Ĉ�́E�E�E(w
	$header .= "Referer: ".$rel;
	$header_options = array("http"=> array("method" => "GET", "header" => $header));
	$header_context = stream_context_create($header_options);
	$html = file_get_html($ref, FALSE, $header_context);
	foreach($html->find('a') as $element){	// href��http����n�܂�a�^�O�𑖍�
		if (strpos($element->href,$condition)){	// a�^�O�Ɏ����̃T�C�g�̃A�h���X���܂܂�Ă����ꍇtrue�i�������Ahttp://���画�肷��j
			return false;
			break;
		}
	}
	return true;
}
?>
