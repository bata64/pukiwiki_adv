<?php
/**
 * PukiWiki Plus! 認証処理
 *
 * @author	Katsumi Saito <katsumi@jo1upk.ymt.prug.or.jp>
 * @version     $Id: auth.cls.php,v 0.69 2010/06/15 00:34:00 upk Exp $
 * @license	http://opensource.org/licenses/gpl-license.php GNU Public License (GPL2)
 */
require_once(LIB_DIR . 'auth.def.php');
//require_once(LIB_DIR . 'Opauth/Opauth.php');
use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Mcrypt;
/**
 * 認証クラス
 * @abstract
 */
class auth
{
	/*
	 *	== IIS ==
	 *	AUTH_USER		- 認証ユーザ名
	 *	AUTH_TYPE		- 認証タイプ
	 *	HTTP_AUTHORIZATION	- パスワードのダイジェスト
	 *	LOGON_USER		- サーバへのログオンユーザ名
	*/

	/*
	 * 認証者名を取得
	 * @static
	 */
	public static function check_auth()
	{
		$login = auth::check_auth_pw();
		if (! empty($login)) return $login;

		// 外部認証API
		$auth_key = auth::get_user_name();

		// 暫定管理者(su)
		global $vars;
		if (! isset($vars['pass'])) return $auth_key['nick'];
		if (pkwk_login($vars['pass'])) return UNAME_ADM_CONTENTS_TEMP;
		return $auth_key['nick'];
	}

	static function check_auth_pw()
	{
		global $auth_type;

		$login = '';
		switch ($auth_type) {
		case 1:
			$login = auth::check_auth_basic();
			break;
		case 2:
			$login = auth::check_auth_digest();
			break;
		}

		if (! empty($login)) return $login;

		// NTLM対応
		list($domain, $login, $host, $pass) = auth::ntlm_decode();
		return $login;
	}

	static function check_auth_basic()
	{
		global $auth_users;

		$user = '';
		foreach (array('PHP_AUTH_USER', 'AUTH_USER', 'REMOTE_USER', 'LOGON_USER') as $x) {
			if (isset($_SERVER[$x]) && ! empty($_SERVER[$x])) {
				// Digest だったら確実
				if (! empty($_SERVER['AUTH_TYPE']) && $_SERVER['AUTH_TYPE'] == 'Digest') {
					$user = $_SERVER[$x];
					break;
				}
				// ドメイン認証の確認
				$ms = explode('\\', $_SERVER[$x]);
				if (count($ms) == 3) {
					$user = $ms[2]; // DOMAIN\\USERID
					break;
				}
				// この変数の内容で確定する
				$user = $_SERVER[$x];
				break;
			}
		}
		if (empty($user)) return '';

		// 未定義ユーザは、サーバ側で認証時または、ＯＳでの認証時のワークグループ接続的なイメージ
		if (!isset($auth_users[$user])) return $user;

		// 定義ユーザならパスワードのチェックを行う
		$pass = '';
		foreach (array('PHP_AUTH_PW', 'AUTH_PASSWORD', 'HTTP_AUTHORIZATION') as $pw) {
			//if (! empty($_SERVER[$pw])) return $_SERVER[$x];
			if (isset($_SERVER[$pw]) && ! empty($_SERVER[$pw])) {
				$pass = $_SERVER[$pw];
				break;
			}
		}
                if (empty($pass)) return '';
		if (empty($auth_users[$user][0])) return ''; // パスワードが空は除く
		return (pkwk_hash_compute($pass,$auth_users[$user][0]) === $auth_users[$user][0]) ? $user : '';
        }

	static function check_auth_digest()
	{
		global $auth_users;

		if (! auth::auth_digest($auth_users)) return '';
		$data = auth::http_digest_parse($_SERVER['PHP_AUTH_DIGEST']);
		if (! empty($data['username'])) return $data['username'];
		return '';
        }

	public static function get_user_info()
	{
		// Array ( [role] => 0 [nick] => [key] => [group] => [displayname] => [api] => )
		$retval = auth::get_auth_pw_info();
		if (!empty($retval['api'])) {
			return $retval;
		}
		return auth::get_auth_api_info();
	}

	public static function get_auth_pw_info()
	{
		global $auth_users, $defaultpage;
		$retval = array('role'=>ROLE_GUEST,'nick'=>'','key'=>'','api'=>'','group'=>'','displayname'=>'','home'=>'','mypage'=>'');
		$user = auth::check_auth_pw();
		if (empty($user)) return $retval;

		$retval['api'] = 'plus';
		$retval['key'] = $retval['nick'] = $user;

		// 登録者かどうか
		if (empty($auth_users[$user])) {
                        // 未登録者の場合
                        // 管理者パスワードと偶然一致した場合でも見做し認証者(ROLE_AUTH_TEMP)
			$retval['role']  = ROLE_AUTH_TEMP;
			return $retval;
		}

		$retval['role']  = (empty($auth_users[$user][1])) ? ROLE_ENROLLEE : $auth_users[$user][1];
		$retval['group'] = (empty($auth_users[$user][2])) ? '' : $auth_users[$user][2];
		$retval['home']  = (empty($auth_users[$user][3])) ? $defaultpage : $auth_users[$user][3];
		$retval['mypage']= (empty($auth_users[$user][4])) ? '' : $auth_users[$user][4];
		return $retval;
	}

	static function get_auth_api_info()
	{
		global $auth_api, $auth_wkgrp_user, $defaultpage;

		foreach($auth_api as $api=>$val) {
			// どうしても必要な場合のみ開始
			if (! $val['use']) continue;
			break;
		}

		require_once(LIB_DIR . 'auth_api.cls.php');
		$obj = new auth_api();
		$msg = $obj->auth_session_get();
		if (isset($msg['api']) && $auth_api[$msg['api']]['use']) {
			if (exist_plugin($msg['api'])) {
				$call_func = 'plugin_'.$msg['api'].'_get_user_name';
				$auth_key = $call_func();
				$auth_key['api'] = $msg['api'];
				if (empty($auth_key['nick'])) return array('role'=>ROLE_GUEST,'nick'=>'','key'=>'','group'=>'','displayname'=>'','home'=>'','mypage'=>'','api'=>'');

				// 上書き・追加する項目
				if (! empty($auth_wkgrp_user[$auth_key['api']][$auth_key['key']])) {
					$val = & $auth_wkgrp_user[$auth_key['api']][$auth_key['key']];
					$auth_key['role']
						= (empty($val['role'])) ? ROLE_ENROLLEE : $val['role'];
					$auth_key['group']
						= (empty($val['group'])) ? '' : $val['group'];
					$auth_key['displayname']
						= (empty($val['displayname'])) ? $user : $val['displayname'];
					$auth_key['home']
						= (empty($val['home'])) ? $defaultpage : $val['home'];
					$auth_key['mypage']
						= (empty($val['mypage'])) ? '' : $val['mypage'];
				}
				return $auth_key;
                        }
                }
		return array('role'=>ROLE_GUEST,'nick'=>'','key'=>'','group'=>'','displayname'=>'','home'=>'','mypage'=>'','api'=>'');
	}

	public static function get_user_name()
	{
		$auth_key = auth::get_user_info();
		if (empty($auth_key['nick'])) return $auth_key;
		if (! empty($auth_key['displayname'])) {
			$auth_key['nick'] = $auth_key['displayname'];
		}
		return $auth_key;
	}

	/**
	 * ユーザのROLEを取得
	 * @static
	 */
	static function get_role_level()
	{
		$info = auth::get_user_info();
		return $info['role'];
	}

	/*
	 * 指定されるROLEに属するユーザを列挙
	 * @static
	 */
	static function get_user_list($role)
	{
		global $auth_users;
		$rc = array();
		foreach($auth_users as $user=>$val)
		{
			$def_role = (empty($val[1])) ? ROLE_AUTH : $val[1];
			if ($def_role > $role) continue;
			$rc[] = $user;
		}

		$now_role = auth::get_role_level();
		// if (($now_role == ROLE_AUTH_TEMP && $role == ROLE_AUTH) || ($now_role == ROLE_ADM_CONTENTS_TEMP && $role == ROLE_ADM_CONTENTS))
		if (($now_role == ROLE_AUTH_TEMP && $role == ROLE_AUTH))
		{
			$rc[] = auth::check_auth();
		}

		return $rc;
	}

	/*
	 * 管理者パスワードなのかどうか
	 * @return bool
	 * @static
	 */
	public static function is_temp_admin()
	{
		global $adminpass;
		// 管理者パスワードなのかどうか？
		$temp_admin = ( pkwk_hash_compute($_SERVER['PHP_AUTH_PW'], $adminpass) !== $adminpass) ? false : true;
		if (! $temp_admin && $login == UNAME_ADM_CONTENTS_TEMP) {
			global $vars;
			if (isset($vars['pass']) && pkwk_login($vars['pass'])) $temp_admin = true;
		}
		return $temp_admin;
	}

	/*
	 * ROLEに応じた挙動の確認
	 * @return bool
	 * @static
	 */
	static function check_role($func='')
	{
		global $adminpass;

		switch($func) {
		case 'readonly':
			$chk_role = (defined('PKWK_READONLY')) ? PKWK_READONLY : ROLE_GUEST;
			break;
		case 'safemode':
			$chk_role = (defined('PKWK_SAFE_MODE')) ? PKWK_SAFE_MODE : ROLE_GUEST;
			break;
		case 'su':
			$now_role = auth::get_role_level();
			if ($now_role == 2 || (int)$now_role == ROLE_ADM_CONTENTS) return FALSE; // 既に権限有
			$chk_role = ROLE_ADM_CONTENTS;
			switch ($now_role) {
			case ROLE_AUTH_TEMP:
				// FIXME:
				return TRUE;
			case ROLE_GUEST:
				// 未認証者は、単に管理者パスワードを要求
				$user = UNAME_ADM_CONTENTS_TEMP;
				break;
			case ROLE_ENROLLEE:
			case ROLE_AUTH:
				// 認証済ユーザは、ユーザ名を維持しつつ管理者パスワードを要求
				$user = auth::check_auth();
				break;
			}
			$auth_temp = array($user => array($adminpass) );

			while(1) {
				if (!auth::auth_pw($auth_temp))
				{
					unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
					header( 'WWW-Authenticate: Basic realm="USER NAME is '.$user.'"' );
					header( 'HTTP/1.0 401 Unauthorized' );
					break;
				}
				// ESC : 認証失敗
				return TRUE;
			}
			break;
		case 'role_adm':
			$chk_role = ROLE_ADM;
			break;
		case 'role_adm_contents':
			$chk_role = ROLE_ADM_CONTENTS;
			break;
		case 'role_enrollee':
			$chk_role = ROLE_ENROLLEE;
			break;
		case 'role_auth':
			$chk_role = ROLE_AUTH;
			break;
		default:
			$chk_role = ROLE_GUEST;
		}

		return auth::is_check_role($chk_role);
	}

	public static function is_check_role($chk_role)
	{
		static $now_role;
		if ($chk_role == ROLE_GUEST) return FALSE;      // 機能無効
		if ($chk_role == ROLE_FORCE) return TRUE;       // 強制

		// 役割に応じた挙動の設定
		if (!isset($now_role)) $now_role = (int)auth::get_role_level();
		if ($now_role == ROLE_GUEST) return TRUE;
		return ($now_role <= $chk_role) ? FALSE : TRUE;
	}

	/**
	 * NTLM, Negotiate 認証 (IIS 4.0/5.0)
	 * @static
	 */
	static function auth_ntlm()
	{
		if($_SERVER['HTTP_AUTHORIZATION'] == NULL){
			header( "HTTP/1.0 401 Unauthorized" );
			header( "WWW-Authenticate: NTLM" );
			exit;
		};

		if(!isset($_SERVER['HTTP_AUTHORIZATION'])) return 0;

		list($auth_type,$digest64) = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
		switch( strtoupper($auth_type) ) {
		case 'NTLM':      // IIS 4.0
			return 1;
		case 'NEGOTIATE': // IIS 5.0 ('Negotiate')
			return 2;

		// IIS用 phpMyAdmin-2.6.2-pl1/libraries/auth/http.auth.lib.php
		case 'BASIC':     // 'Basic'
			if (!function_exists('base64_decode')) return array('','');
			return explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
		}

		$digest = 'NTL' . base64_decode( substr($digest64 ,4) );

		if (ord($digest{8})  != 1  ) return 0;
		if (ord($digest[13]) != 178) return 0;

		$strAuth = 'NTLMSSP'
			 . chr(0) . chr(2) . chr(0) . chr(0) . chr(0)
			 . chr(0) . chr(0) . chr(0) . chr(0) . chr(40)
			 . chr(0) . chr(0) . chr(0) . chr(1) . chr(130)
			 . chr(0) . chr(0) . chr(0) . chr(2) . chr(2)
			 . chr(2) . chr(0) . chr(0) . chr(0) . chr(0)
			 . chr(0) . chr(0) . chr(0) . chr(0) . chr(0)
			 . chr(0) . chr(0) . chr(0);

		$strAuth64 = base64_encode($strAuth);
		$strAuth64 = trim($strAuth64);
		header( 'HTTP/1.0 401 Unauthorized' );
		header( "WWW-Authenticate: NTLM $strAuth64" );
		exit;

		return 0;
	}

	/**
	 * HTTP_AUTHORIZATION の解読
	 * @static
	 */
	static function ntlm_decode()
	{
		$rc = array('','','','');
		if (!function_exists('base64_decode')) return $rc;
		if (!isset($_SERVER['HTTP_AUTHORIZATION'])) return $rc;
		// if (substr($_SERVER['HTTP_AUTHORIZATION'],0,4) != 'MSSP') return $rc;

		list($auth_type,$x) = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);

		switch( strtoupper($auth_type) ) {
		// IIS用 (http://homepage1.nifty.com/yito/namazu/gbook/20021127.1530.html)
		case 'BASIC':     // 'Basic'
			list($login, $pass) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
			return array('',$login,'', $pass);
		case 'NTLM':      // IIS 4.0
			break;
		case 'NEGOTIATE': // IIS 5.0 ('Negotiate')
			break;
		default:
			return $rc;
		}

		$x = 'NTL' . base64_decode( substr($x,4) );

		if(ord($x{8}) != 3) return $rc;

		$rc = array();
		for ($i=30; $i<=46; $i += 8)
		{
									// domain login host
			$len    = (ord($x[$i+1])*256 + ord($x[$i  ]));	// 31,30  39,38 47,46
			$offset = (ord($x[$i+3])*256 + ord($x[$i+2]));	// 33,32  41,40 49,48
			$rc[] = substr($x, $offset, $len);
		}
		$rc[] = ''; // pass
		return $rc; // domain, login, hostname, pass
	}

	/**
	 * 認証 (PukiWikiの設定に準ずる)
	 * @static
	 */
	static function auth_pw($auth_users)
	{
		$user = '';
		foreach (array('PHP_AUTH_USER', 'AUTH_USER') as $x) {
			if (isset($_SERVER[$x])) {
				$ms = explode('\\', $_SERVER[$x]);
				if (count($ms) == 3) {
					$user = $ms[2]; // DOMAIN\\USERID
				} else {
					$user = $_SERVER[$x];
				}
				break;
			}
		}

		$pass = '';
		foreach (array('PHP_AUTH_PW', 'AUTH_PASSWORD', 'HTTP_AUTHORIZATION') as $x) {
			if (! empty($_SERVER[$x])) {
				if ($x == 'HTTP_AUTHORIZATION') {
					// NTLM対応 (domain, login, host, pass)
					$tmp_ntlm = auth::ntlm_decode();
					if ($tmp_ntlm[3] == '') continue;
					if (empty($user)) $user = $tmp_ntlm[1];
					$pass = $tmp_ntlm[3];
					unset($tmp_ntml);
					break;
				}
				$pass = $_SERVER[$x];
				break;
			}
		}

		if (empty($user) && empty($pass)) return false;
		if (empty($auth_users[$user][0])) return false;
		if ( pkwk_hash_compute($pass, $auth_users[$user][0]) !== $auth_users[$user][0]) return false;
		return true;
	}

	function auth_digest($auth_users)
	{
		if (! isset($_SERVER['PHP_AUTH_DIGEST']) || empty($_SERVER['PHP_AUTH_DIGEST'])) return false;
		$data = auth::http_digest_parse($_SERVER['PHP_AUTH_DIGEST']);
		if ($data === false) return false;

		list($scheme, $salt, $role) = auth::get_data($data['username'], $auth_users);
		if ($scheme != '{x-digest-md5}') return false;

		// $A1 = md5($data['username'] . ':' . $realm . ':' . $auth_users[$data['username']]);
		$A1 = $salt;
		$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
		$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
		if ($data['response'] != $valid_response) return false;
		return true;
	}

	/**
	 * PHP_AUTH_DIGEST 変数をパースする関数
	 * function to parse the http auth header
	 * @static
	 */
	static function http_digest_parse($txt)
	{
		// protect against missing data
		$needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
		$data = array();

		// url に含まれる文字列を含む必要がある
		// preg_match_all('@(\w+)=([\'"]?)([a-zA-Z0-9=./\_-]+)\2@', $txt, $matches, PREG_SET_ORDER);
		// preg_match_all('@(\w+)=([\'"]?)([a-zA-Z0-9=./%&\?\_-_+]+)\2@', $txt, $matches, PREG_SET_ORDER);
		preg_match_all('@(\w+)=([\'"]?)([a-zA-Z0-9=./%&\?\_-]+)\2@', $txt, $matches, PREG_SET_ORDER);

		foreach ($matches as $m) {
			$data[$m[1]] = $m[3];
			unset($needed_parts[$m[1]]);
		}

		return $needed_parts ? FALSE : $data;
	}

	/**
	 * データの分解
	 * @static
	 */
	static function get_data($user,$auth_users)
	{
		if (!isset($auth_users[$user])) {
			// scheme, salt, role
			return array('','','');
		}

		$role = (empty($auth_users[$user][1])) ? '' : $auth_users[$user][1];
		list($scheme,$salt) = auth::passwd_parse($auth_users[$user][0]);
		return array($scheme,$salt,$role);
	}

	/**
	 * PukiWiki Passwd の分解
	 * @static
	 */
	static function passwd_parse($passwd)
	{
		$regs = array();
		if (preg_match('/^(\{.+\})(.*)$/', $passwd, $regs)) {
			return array($regs[1], $regs[2]);
		}
		return array('',$passwd);
	}

	/**
	 * 署名の抽出
	 * @static
	 */
	static function get_signature($lines)
	{
		$patterns = array(
			"'.*? -- \[\[(.*?)\]\] &new{.*?};'si",	// -- [[xxx]] &new{xxx};
			"'.*? -- (.*?) &new{.*?};'si",		// -- xxx &new{xxx};
			"'.*? - \[\[(.*?)\]\] &new{.*?}'si",	// - [[xxx]] &new{xxx};
			"'.*? - (.*?) &new{.*?}'si",		// - xxx &new{xxx};
			"'.*? -- \[\[(.*?)\]\]'si",		// -- [[xxx]]
			"'.*? -- \[(.*?)\]'si",			// -- [xxx]
			"'.*? -- (.*?)'si",			// -- xxx
		);

		foreach ($lines as $_line) {
			foreach ($patterns as $pat) {
				if (preg_match($pat,$_line,$regs)) {
					return $regs[1];
				}
			}
		}
		return '';
	}

	public static function is_auth_digest() { return version_compare(phpversion(), '5.1', '>='); }

	public static function is_page_readable($page,$uname,$gname='')
	{
		global $read_auth, $read_auth_pages;
		return auth::is_page_auth($page, $read_auth, $read_auth_pages, $uname, $gname);
	}

	public static function is_page_editable($page,$uname,$gname='')
	{
		global $edit_auth, $edit_auth_pages;
		global $read_auth, $read_auth_pages;
		if (! auth::is_page_auth($page, $read_auth, $read_auth_pages, $uname, $gname)) return false;
                return auth::is_page_auth($page, $edit_auth, $edit_auth_pages, $uname, $gname);
	}

	public static function is_page_auth($page, $auth_flag, $auth_pages, $uname, $gname='')
	{
		global $auth_method_type;
		static $info;
		if (! $auth_flag) return true;

		if (!isset($info)) $info = auth::get_user_info();

		$target_str = '';
		switch($auth_method_type) {
		case 'pagename':
			$target_str = $page;
			break;
		case 'contents':
			$target_str = get_source($page, true, true);
			break;
		}

		$user_list = $group_list = $role = '';
		foreach($auth_pages as $key=>$val) {
			if (preg_match($key, $target_str)) {
                                if (is_array($val)) {
					$user_list  = (empty($val['user']))  ? '' : explode(',',$val['user']);
					$group_list = (empty($val['group'])) ? '' : explode(',',$val['group']);
					$role       = (empty($val['role']))  ? '' : $val['role'];
				} else {
					$user_list  = (empty($val))          ? '' : explode(',',$val);
				}
				break;
			}
		}

		// No limit
		if (empty($user_list) && empty($group_list) && empty($role)) return true;
		// 未認証者
		if (empty($uname)) return false;

		// ユーザ名検査
		if (!empty($user_list) && in_array($uname, $user_list)) return true;
		// グループ検査
		if (!empty($group_list) && !empty($gname) && in_array($gname, $group_list)) return true;
		// role 検査
		if (!empty($role) && !auth::is_check_role($role)) return true;
		return false;
	}

	public static function get_existpages($dir = DATA_DIR, $ext = '.txt')
	{
		$rc = array();

		// ページ名の取得
		$pages = get_existpages_cache($dir, $ext);
		// $pages = get_existpages($dir, $ext);
		// ユーザ名取得
		$auth_key = auth::get_user_info();
		// コンテンツ管理者以上は、: のページも閲覧可能
		$is_colon = auth::check_role('role_adm_contents');

		// 役割の取得
		// $now_role = auth::get_role_level();

		$rc = array();
		if (is_array($pages)){
			foreach($pages as $file=>$page) {
				if (! auth::is_page_readable($page, $auth_key['key'], $auth_key['group'])) continue;
				if (substr($page,0,1) != ':') {
					$rc[$file] = $page;
					continue;
				}

				// colon page
				if ($is_colon) continue;
				$rc[$file] = $page;
			}
		}
		return $rc;
	}

	public static function is_role_page($lines)
	{
		global $check_role;
		if (! $check_role) return FALSE;
		$cmd = use_plugin('check_role',$lines);
		if ($cmd === FALSE) return FALSE;
		convert_html($cmd); // die();
		return TRUE;
	}

	function des_session_get($session_name)
	{
		global $adminpass, $session;

		// adminpass の処理
		list($scheme, $salt) = auth::passwd_parse($adminpass);

		// des化された内容を平文に戻す
		if ($session->offsetExists($session_name)) {
			//require_once(LIB_DIR . 'des.php');
			//return des($salt, base64_decode($session->offsetGet($session_name), 0, 0, null) );
			$blockCipher = BlockCipher::factory('mcrypt', array(
				'algo' => 'des',
				'mode' => 'cfb',
				'hash' => 'sha512',
				'salt' => $salt,
				'padding' => 2
			));
			return $blockCipher->decrypt($session->offsetGet($session_name));
		}
		return '';
	}

	public static function des_session_put($session_name, $val)
	{
		global $adminpass, $session;

		// adminpass の処理
		list($scheme, $salt) = auth::passwd_parse($adminpass);
		//require_once(LIB_DIR . 'des.php');
		//$session->offsetSet($session_name, base64_encode( des($salt, $val, 1, 0, null) ) );
//		session_write_close();
		$blockCipher = BlockCipher::factory('mcrypt', array(
			'algo' => 'des',
			'mode' => 'cfb',
			'hash' => 'sha512',
			'salt' => $salt,
			'padding' => 2
		));
		$result = $blockCipher->encrypt($val);
		$session->offsetSet($session_name, $result);
		return $blockCipher;
	}

	// See:
	// Web Services Security: UsernameToken Profile 1.0
	// http://www.xmlconsortium.org/wg/sec/oasis-200401-wss-username-token-profile-1.0-jp.pdf
	function wsse_header($uid,$pass)
	{
		$nonce = hex2bin(md5(rand().UTIME));
		$created = gmdate('Y-m-d\TH:i:s\Z',UTIME);
		$digest = auth::b64_sha1($nonce.$created.$pass);
		return 'UsernameToken Username="'.$uid.'", PasswordDigest="'.$digest.'", Nonce="'.base64_encode($nonce).'", Created="'.$created.'"';
	}

	function b64_sha1($x)
	{
		return base64_encode(hex2bin(sha1($x)));
	}

	public static function is_protect_plugin_action($x)
	{
		global $auth_api;
		static $plugin_list = array('login','redirect');

		foreach($plugin_list as $val) {
			if ($val == $x) return true;
		}

		foreach($auth_api as $api=>$val) {
			if ($api == $x) return true;
		}

		// auth_X plugin OK.
		if (strpos($x,'auth_') === 0 && strlen($x) > 5) return true;
        	return false;
	}

	public static function is_protect()
	{
		return (PLUS_PROTECT_MODE && auth::is_check_role(PLUS_PROTECT_MODE));
	}

	function user_list()
	{
		global $auth_users, $auth_wkgrp_user, $defaultpage;
		$rc = array();

		foreach ($auth_users as $user=>$val) {
			$role  = (empty($val[1])) ? ROLE_ENROLLEE : $val[1];
			$group = (empty($val[2])) ? '' : $val[2];
			$home  = (empty($val[3])) ? $defaultpage : $val[3];
			$mypage= (empty($val[4])) ? '' : $val[4];
			$rc['plus'][$user] = array('role'=>$role,'displayname'=>$user,'group'=>$group,'home'=>$home,'mypage'=>$mypage);
		}

		foreach($auth_wkgrp_user as $api=>$val1) {
			foreach($val1 as $user=>$val) {
				if (is_array($val)) {
					$role  = (empty($val['role'])) ? ROLE_ENROLLEE : $val['role'];
					$group = (empty($val['group'])) ? '' : $val['group'];
					$name  = (empty($val['displayname'])) ? $user : $val['displayname'];
					$home  = (empty($val['home'])) ? $defaultpage : $val['home'];
					$mypage= (empty($val['mypage'])) ? '' : $val['mypage'];
				} else {
					$role = $val;
					$group = '';
					$name = $user;
					$home = $defaultpage;
					$mypage = '';
				}
				$rc[$api][$user] = array('role'=>$role, 'displayname'=>$name,'group'=>$group,'home'=>$home,'mypage'=>$mypage);
			}
		}
		return $rc;
	}
}

/* End of file auth.cls.php */
/* Location: ./wiki-common/lib/auth.cls.php */