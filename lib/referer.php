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

define('REFERER_SPAM_LIST', CACHE_DIR.'referer_spam.log');

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
		$data = csv_explode(',', $data);
		$result[rawurldecode($data[$uniquekey])] = $data;
	}
	@flock($fp, LOCK_UN);
	fclose ($fp);

	return $result;
}

function ref_save($page)
{
	global $referer, $use_spam_check;

	// if (PKWK_READONLY || ! $referer || empty($_SERVER['HTTP_REFERER'])) return TRUE;
	// if (auth::check_role('readonly') || ! $referer || empty($_SERVER['HTTP_REFERER'])) return TRUE;
	if (! $referer || empty($_SERVER['HTTP_REFERER'])) return TRUE;

	$url = $_SERVER['HTTP_REFERER'];

	// Validate URI (Ignore own)
	$parse_url = parse_url($url);
	if ($parse_url === FALSE || !isset($parse_url['host']) || $parse_url['host'] == $_SERVER['HTTP_HOST'])
		return TRUE;

	// Blocking SPAM
	if ($use_spam_check['referer'] && SpamCheck($parse_url['host']))
		return TRUE;

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
		$str = trim(join(',', $line));
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

// Referer��spam���̃`�F�b�N
function ref_checkspam($url){
	$is_refspam = false;
	// ���t�@���[�X�p�����X�g��ǂݍ���
	// �A�h���X��,���܂܂�Ă����ꍇ�̏������߂�ǂ�������TSV�`��

	// �Ƃ������A:config/referer/blacklist�Ɏ����X�V���������A�z�X�g���ۑ�����Ȃ��̂ł��̎���

	$file = REFERER_SPAM_LIST;
	if (! file_exists($file)) return false;

	$fp = @fopen($file, 'r');
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	rewind($fp);
	
	while (($data = fgetcsv($fp, 1000, "\t")) !== FALSE) {
		if ($data[0] == $url){
			$is_refspam = true;	// �����ŏ����𒆒f����̂͂܂������낤�E�E�E
			break;
		}
	}
	@flock($fp, LOCK_UN);
	fclose ($fp);
	unset($fp);

	// ���t�@���[�X�p�����O�ɋL�ڂ���Ă��Ȃ��ꍇ
	if (!$is_refspam){
		// ���t�@���[�ɃT�C�g�ւ̃A�h���X�����݂������m�F
		$is_refspam = is_valid_ref();
		if ($is_refspam === true){
			// ���݂��Ȃ��ꍇ�X�p�����O�ɋL��
			pkwk_touch_file($file);
			$fp = fopen($file, 'w');
			@flock($fp, LOCK_EX);
			rewind($fp);
			foreach ($data as $line) {
				$str = trim(join(',', $line));
				if ($str != '') fwrite($fp, $str . "\n");
			}
			@flock($fp, LOCK_UN);
			fclose($fp);
			unset($fp);
		}
	}
	return $is_refspam;
}

// ���ہA�����N���ɃA�N�Z�X���Ă��T�C�g�ւ̃A�h���X�����݂��邩�̃`�F�b�N
function is_valid_ref(){
	// �{���͐��K�����ꂽ�A�h���X�Ń`�F�b�N����ׂ����낤���A
	// �߂�ǂ�������X�N���v�g�̃A�h���X���܂ނ��Ń`�F�b�N
	// global $vars;
	// $script = get_page_absuri(isset($vars['page']) ? $vars['page'] : '');

	$script = get_script_uri();
	$error_count = 0;

	do{
		$http = new HTTP_Request($url, array(
				"timeout" => "300",	//HTTP_Request�^�C���A�E�g�̕b���w��
			)
		);
		$http->addHeader("User-Agent", 'Mozilla/5.0 (compatible; '.GENERATOR.')');
//		$http->addHeader("Referer", $script);	// Referer�͓f���Ȃ������������ȁH
//		$http->setBasicAuth($GLOBALS['id'], $GLOBALS['pass']);
		$http->sendRequest();

		// FIXME
		switch ($http->getResponseCode()){
			case 200 :
				$ret = $http->getResponseBody();
			break;
			case 301 :	// Moved Permanently
			case 302 :	// Moved Temporarily
			case 307 :	// Moved Temporarily(HTTP1.1)
			case 403 :	// Forbidden
			case 404 :	// Not Found
			case 401 :	// Unauthorized
				return true;	// ���������y�[�W����˂��Bspam�Ƃ���
			break;
			default:
				$error_count++;
				sleep(10);	// 10�b�ԑҋ@
			break;
		}
	}while($error_count < 2);
	
	unset($error_count);
	
	foreach($ret->find('a') as $element){	// a�^�O�𑖍�
		if (preg_match('/^'.$script.'/',$element->href)){	// a�^�O�Ɏ����̃T�C�g�̃A�h���X���܂܂�Ă����ꍇfalse
			return false;
			break;
		}
	}
	return true;
}

?>
