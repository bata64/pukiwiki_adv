<?php
/**
 * WikiRpc�T�[�r�X�N���X
 *
 * @package   PukiWiki
 * @access    public
 * @author    Logue <logue@hotmail.co.jp>
 * @copyright 2012-2014 PukiWiki Advance Developers Team
 * @create    2014/02/27
 * @license   GPL v2 or (at your option) any later version
 * @version   $Id: WikiRpcService.php,v 1.0.1 2014/02/27 22:57:00 Logue Exp $
 **/

namespace PukiWiki\Service;

use PukiWiki\Factory;
use PukiWiki\Listing;
use PukiWiki\Recent;
use PukiWiki\Relational;
use PukiWiki\Renderer\RendererFactory;

/**
 * WikiRpc�T�[�r�X�N���X
 * �Q�l�F
 * �@http://www.hyuki.com/yukiwiki/wiki.cgi?WikiRPC
 * �@http://www.ecyrd.com/JSPWiki/wiki/WikiRPCInterface2
 * �@http://trac-hacks.org/wiki/XmlRpcPlugin
 * �@https://www.dokuwiki.org/devel:xmlrpc
 *
 * ��Zend\XmlRpc�̎d�l��Aphpdoc�̃R�����g���̍ŏ��̍s��������Ƃ��ĕԂ����߁A
 * �@���{�ꂪ�܂܂���XML�G���[�ɂȂ��Ă��܂��B
 * �@���̂��߁A�Q�s�ڂɓ��{��̐�����������B
 */
class WikiRpc{
	/**
	 * WikiRpc�̃o�[�W����
	 */
	const WIKI_RPC_VERSION = 2;
	/**
	 * Returns 2 with the supported RPC API version.
	 * �T�|�[�g���Ă���WikiRpc�̃o�[�W�����B�Q��Ԃ��B
	 * @return int
	 */
	public function getRPCVersionSupported(){
		return self::WIKI_RPC_VERSION;
	}
	/**
	 * Returns the permission of the given wikipage
	 * �y�[�W�̌�����Ԃ��B
	 * @param string �y�[�W��
	 */
	public function aclCheck($page){
		$wiki = Factory::Wiki($page);
		return $wiki->isReadable();
	}
	/**
	  * Returns the raw Wiki text for a page.
	  * �y�[�W�̐��̃e�L�X�g��Ԃ�
	  * @param string $pagename �y�[�W��
	  * @return string
	  */
	public function getPage( $page ){
		$wiki = Factory::Wiki($page);
		return $wiki->isReadable() ? $wiki->get(true) : false;
	}
	/**
	  * Returns the raw Wiki text for a specific revision of a Wiki page.
	  * �y�[�W�̃o�b�N�A�b�v���擾����
	  * @param string $pagename �y�[�W��
	  * @param int $version ��
	  * @return string
	  */
	public function getPageVersion( $page, $version ){
		return Factory::Backup($page)->get($version);
	}
	/**
	  * Returns the available versions of a Wiki page. The number of pages in the result is controlled via the recent configuration setting. The offset can be used to list earlier versions in the history.
	  * 
	  * @param string $pagename �y�[�W��
	  * @param int $version ��
	  * @return string
	  */
	public function getPageVersions( $page, $version ){
		// ������
	}
	/**
	  * Returns information about a Wiki page.
	  * �y�[�W�̏����擾����
	  * @param string $pagename �y�[�W��
	  * @return struct
	  */
	public function getPageInfo( $page ){
		$wiki = Factory::Wiki($page);
		return array(
			'name' => $page,
			'lastModified' => $wiki->time(),
		);
	}
	/**
	 * Returns the rendered XHTML body of a Wiki page.
	 * �y�[�W�̍ŐV�ł�HTML��Ԃ��B
	 * @param string $pagename �y�[�W��
	 * @return string
	 */
	public function getPageHTML( $pagename ){
		return Factory::Wiki($pagename);
	}
	/**
	 * Returns the rendered HTML of a specific version of a Wiki page.
	 * �ł��w�肵�ăy�[�W��HTML��Ԃ��B
	 * @param string $pagename �y�[�W��
	 * @param int $version ��
	 * @return string
	 */
	public function getPageHTMLVersion( $page, $version ){
		return RendererFactory::factory(Factory::Backup($page)->get($version));
	}
	/**
	  * Saves a Wiki Page.
	  * �y�[�W��ҏW����B
	  * @param string $pagename �y�[�W��
	  * @param string $content �y�[�W�̓��e
	  * @param struct $attributes �^�C���X�^���v���X�V���邵�Ȃ��Ȃǂ̃t���O�̓������I�u�W�F�N�g
	  * @return array
	  */
	public function putPage( $pagename, $content, $attributes ){
		global $notimeupdate;
		$notimestamp = isset($attributes['notimestamp']) || $notimeupdate ? $attributes['notimestamp'] : false;
		$wiki = Factory::Wiki($pagename);
		if (!$wiki->isValied() || !$wiki->isEditable()) {
			return false;
		}
		return $wiki->set($content, $notimestamp);
	}
	/**
	  * Returns a list of all links contained in a Wiki page.
	  * �y�[�W���̂��ׂẴ����N�̃��X�g�B
	  * @param string $pagename �y�[�W��
	  * @return array
	  */
	public function listLinks( $pagename ){
		$links = array();
		preg_match_all('/href="(https?:\/\/[^"]+)"/', Factory::Wiki($pagename)->render(), $links, PREG_PATTERN_ORDER);
		return array_unique($links[1]);
	}
	/**
	  * Returns a list of all Wiki pages in the remote Wiki
	  * �S�Ẵy�[�W������Ȃ�z���Ԃ��B
	  * @return array
	  */
	public function getAllPages(){
		return Listing::pages();
	}
	/**
	  * Returns a list of backlinks of a Wiki page.
	  * ���̃y�[�W�Ƀ����N���Ă���y�[�W�̔z���Ԃ��B
	  * @param string $pagename �y�[�W��
	  * @return array
	  */
	public function getBackLinks( $pagename ){
		$links = new Relational($pagename);
		return $links->getRelated();
	}
	/**
	  * Returns a list of recent changes since given timestamp.
	  * timestamp�iUTC�j�ȍ~�ɍX�V���ꂽ�y�[�W�̃��X�g�𓾂�B
	  * @param string $timestamp ����
	  * @return array
	  */
	public function getRecentChanges( $timestamp = 0 ){
		$ret = array();
		$recents = Recent::get();
		if ($timestamp === 0) return array_keys($recents);
		foreach ($recents as $page=>$time){
			if ($time < $timestamp) continue;
			$ret[] = $page;
		}
		return $ret;
	}
	/**
	  * �o�[�W�����w��Ńy�[�W���B
	  *
	  * @param string $pagename �y�[�W��
	  * @param int $version ��
	  * @return struct
	  */
	public function getPageInfoVersion( $pagename, $version ){
		// ������
	}
	/**
	  * Returns a list of media files
	  * �Y�t�t�@�C�����̃��X�g
	  * @param string $pagename �y�[�W��
	  * @return array
	  */
	public function listAttachments( $pagename ){
		$wiki = Factory::Wiki($pagename);
		if (!$wiki->isValied() || !$wiki->isReadable()) {
			return false;
		}
		return array_keys($wiki->attach())[0];
	}
	/**
	 * Returns the binary data of a media file
	 * base64�G���R�[�h���ꂽ�Y�t�t�@�C����Ԃ��B
	 * @param string $page �y�[�W��
	 * @param string $filename �t�@�C����
	 * @return base64
	 */
	public function getAttachment( $page, $filename ){
		$wiki = Factory::Wiki($pagename);
		if (!$wiki->isValied() || !$wiki->isReadable()) {
			return false;
		}
		$attach = Factory::Attach($page, $filename);
		if (!$attach->has()) {
			return false;
		}
		return base64_encode($attach->get());
	}
	/**
	 * Returns information about a media file
	 * �t�@�C���̏ڍ׏��
	 * @param string $page �y�[�W��
	 * @param string $filename �t�@�C����
	 * @return struct
	 */
	public function getAttachmentInfo($page, $filename ){
		$wiki = Factory::Wiki($pagename);
		if (!$wiki->isValied() || !$wiki->isReadable()) {
			return false;
		}
		$attach = Factory::Attach($page, $filename);
		if (!$attach->has()) {
			return false;
		}
		
		$f = new File(UPLOAD_DIR . $this->files[0]);
		
		return array(
			'mime' => $attach->getMime(),
			'size' => $f->getSize(),
			'lastModified' => $f->getMTime(),
			'md5'  => $f->md5()
		);
		
	}
	/**
	 * Uploads a file 
	 * �t�@�C����Y�t����B
	 * @param string $page �y�[�W��
	 * @param string $filename �t�@�C����
	 * @param string $data base64�ϊ����ꂽ�f�[�^�[
	 */
	public function putAttachment($page, $filename, $data ){
		$wiki = Factory::Wiki($pagename);
		if (!$wiki->isValied() || !$wiki->isEditable()) {
			return false;
		}
		$attach = Factory::Attach($page, $filename);
		if ($attach->has()) {
			return false;
		}
		return $attach->set(base64_decode($data));
	}
	/**
	 * Deletes a file. Fails if the file is still referenced from any page in the wiki.
	 * �t�@�C�����폜����
	 * @param string $page �y�[�W��
	 * @param string $filename �t�@�C����
	 */
	public function deleteAttachment($page, $filename ){
		if (!$wiki->isValied() || !$wiki->isEditable()) {
			return false;
		}
		$attach = Factory::Attach($page, $filename);
		return $attach->delete();
	}
}