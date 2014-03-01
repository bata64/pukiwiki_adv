<?php
/**
 * PingBack�T�[�r�X
 *
 * @package   PukiWiki
 * @access    public
 * @author    Logue <logue@hotmail.co.jp>
 * @copyright 2012-2014 PukiWiki Advance Developers Team
 * @create    2014/02/27
 * @license   GPL v2 or (at your option) any later version
 * @version   $Id: PingBackService.php,v 1.0.1 2014/02/27 16:54:00 Logue Exp $
 */

namespace PukiWiki\Service;

use PukiWiki\Factory;
use PukiWiki\File\PingBackFile;
use Zend\Http\Client;
use Zend\Uri\Uri;

class PingBack{
	/**
	 * ���M�ɐ�������
	 */
	const RESPONSE_SUCCESS                  = -1;
	/**
	 * ���M�Ɏ��s����
	 */
	const RESPONSE_FAULT_GENERIC            = 0;
	/**
	 * �\�[�XURI��������Ȃ�
	 */
	const RESPONSE_FAULT_SOURCE             = 0x0010;
	/**
	 * �\�[�X�Ƀ^�[�Q�b�g�̃����N�����݂��Ȃ�
	 */
	const RESPONSE_FAULT_SOURCE_LINK        = 0x0011;
	/**
	 * �^�[�Q�b�gURI��������Ȃ�
	 */
	const RESPONSE_FAULT_TARGET             = 0x0020;
	/**
	 * �^�[�Q�b�g��URI�������ł���
	 */
	const RESPONSE_FAULT_TARGET_INVALID     = 0x0021;
	/**
	 * ���łɓo�^����Ă���
	 */
	const RESPONSE_FAULT_ALREADY_REGISTERED = 0x0030;
	/**
	 * �A�N�Z�X����
	 */
	const RESPONSE_FAULT_ACCESS_DENIED      = 0x0031;
	/**
	 * �^�C�g�����Ȃ��ꍇ�̖��O
	 */
	const UNTITLED_TITLE = 'Untitled';
	/**
	 * Pingback
	 *
	 * @param string $source �y�[�W��Ping���M�p�̃A�h���X
	 * @param string $target �y�[�W��Ping�Ҏ�p�̃A�h���X
	 * @return int
	 */
	public function ping($source, $target) {
		global $url_suffix;

		// Zend\Uri\Uri�I�u�W�F�N�g�𐶐�
		$source_url = Uri::factory($source);
		$target_url = Uri::factory($target);
		
		// �����ȃA�h���X
		if (!$target_url->isValid()){
			return self::RESPONSE_FAULT_TARGET_INVALID;
		}
		if (!$source_url->isValid()){
			return self::RESPONSE_FAULT_GENERIC;
		}

		if ($target_url->getHost() === $source_url->getHost()){
			// �^�[�Q�b�g�ƃ\�[�X�̃z�X�g���ꏏ
			// TODO: �����h���C���̃T�C�g�̏ꍇ�A�����T�C�g�Ƃ݂Ȃ����
			return self::RESPONSE_FAULT_SOURCE;
		}

		// ����̃T�C�g�ɐڑ�
		$source_client = new Client($source_url);
		$source_response = $source_client->request(Client::GET);

		// �ڑ��ł��������`�F�b�N
		if (!$source_response->isSuccessful()) {
			return self::RESPONSE_FAULT_SOURCE;
		}

		// ����̃T�C�g�̒��g���擾
		$source_body = $source_response->getBody();
		
		// ���g���擾�ł��Ȃ�
		if (!$source_body){
			return self::RESPONSE_FAULT_SOURCE;
		}

		if ($target_url->getHost() !== $source_url->getHost() && (strpos($source_body, $source_url)) === false) {
			// �\�[�X URI �̃f�[�^�Ƀ^�[�Q�b�g URI �ւ̃����N�����݂��Ȃ����߁A�\�[�X�Ƃ��Ďg�p�ł��Ȃ��B
			return self::RESPONSE_FAULT_SOURCE_LINK;
		}

		// ����T�C�g�̃^�C�g�����擾�iXML�Ƃ��ď����������������H�j
		$source_titles = array();
		preg_match('/<title>([^<]*?)</title>/is', $source_body, $source_titles);
		// �^�C�g�������݂��Ȃ�Untitled
		$source_title = empty($source_titles[1]) ? self::UNTITLED_TITLE : $source_titles[1];

		// �^�[�Q�b�g�̃N�G�����擾�i���T�C�g�j
		$query = $target_url->getQuery();
		if ( empty($query) ){
			// http://[host]/[pagename]�̏ꍇ�i�X���b�V���͍ăG���R�[�h�j
			$r_page = str_replace('/', '%2F', $target_url->getPath());
			// $url_suffix���܂܂��ꍇ�A���K�\���ł������폜
			//$page = empty($url_suffix) ? $r_page : preg_replace('/'.$url_suffix.'$/', '', $r_page);
			$page = rawurldecode($r_page);
			unset($r_page);
		}else{
			// �^�[�Q�b�g��=���܂܂��ꍇ�̓y�[�W�ł͂Ȃ��̂Ŗ���
			if (strpbrk($query, '=')) return self::RESPONSE_FAULT_TARGET_INVALID;
			$page = $query;
		}

		// �y�[�W������Wiki���Ăяo��
		$wiki = Factory::Wiki($page);
		
		if (!$wiki->isValied()){
			// �����ȃy�[�W��
			return self::RESPONSE_FAULT_TARGET_INVALID;
		}

		if (!$wiki->isReadable()){
			// �ǂݍ��ݕs�ȃy�[�W
			return self::RESPONSE_FAULT_ACCESS_DENIED;
		}

		// PingBack�t�@�C����ǂݍ���
		$pb = new PingBackFile($page);
		$lines = $pb->get();
		
		if (count($lines) !== 0){
			foreach ($lines as $line){
				list($time, $url, $title) = explode("\t", $line);
				
				if ($url === $target_url){
					// ���łɓo�^����Ă���
					return self::RESPONSE_FAULT_ALREADY_REGISTERED;
				}
			}
		}
		// �V�����f�[�^�[��o�^
		$lines[] = join("\t", array(UTIME, $source_url, $source_title));
		// �ۑ�
		$pb->set($lines);
		
		return self::RESPONSE_SUCCESS;
	}
}