<?php
/**
 * ZendSearch�iLucene�j�ɂ�錟������
 *
 * @package   PukiWiki
 * @access    public
 * @author    Logue <logue@hotmail.co.jp>
 * @copyright 2013-2014 PukiWiki Advance Developers Team
 * @create    2013/05/23
 * @license   GPL v2 or (at your option) any later version
 * @version   $Id: Search.php,v 1.0.3 2014/03/10 19:24:00 Logue Exp $
 */
namespace PukiWiki;

use PukiWiki\Auth\Auth;
use PukiWiki\Factory;
use PukiWiki\Utility;
use Igo\Tagger;
use ZendSearch\Lucene\Lucene;
use ZendSearch\Lucene\Document\Html as LuceneDocHtml;
/**
 * �����N���X
 */
class SearchLucene extends Search{
	/**
	 * �����C���f�b�N�X��
	 */
	const INDEX_NAME = 'search-index';
	/**
	 * �C���f�b�N�X�t�@�C���𐶐�
	 */
	public static function updateIndex(){
		static $igo;
		
		if (empty($igo)){
			$igo = new Tagger('../ipadic', 'reduce_mode'  => true);
		}
		// �����̍쐬
		$index = Lucene::create(CACHE_DIR . self::INDEX_NAME);
		foreach (Listing::pages() as $page) {
			
			if (empty($page)) continue;

			$wiki = Factory::Wiki($page);

			// �ǂތ������Ȃ��ꍇ�X�L�b�v
			if (!$wiki->isReadable() || $wiki->isHidden()) continue;
			
			// HTML�o��
			$html[] = '<html><head>';
			$html[] = '<meta http-equiv="Content-type" content="text/html; charset=UTF-8"/>';
			$html[] = '<title>' . $wiki->title() . '</title>';
			$html[] = '</head>';
			// HTML���e�L�X�g�ɕϊ����ĕ����������������̂�body�Ƃ���B
			$html[] = '<body>' . $igo->wakati(strip_tags($wiki->render)) . '</body>';
			$html[] = '</html>';
			// HTML�̉��
			$doc = LuceneDocHtml::loadHTML(join("\n", $html), false);
			
			// �����֕����̓o�^
			$index->addDocument($doc);
		}
		
		//$hits = $index->find('hoge');
		//var_dump($hits);
	}
	/**
	 * �������C������
	 * @param string $word �������[�h
	 * @param string $type �������@�iand, or�j
	 * @param boolean $non_format
	 * @param string $base �x�[�X�ƂȂ�y�[�W
	 * @return string
	 */
	public static function do_search($word, $type = 'and', $non_format = FALSE, $base = ''){
		// �C���f�b�N�X�t�@�C�����J��
		$index = Lucene::open(CACHE_DIR . self::INDEX_NAME);
		
		// �����N�G�����p�[�X
		$query = \ZendSearch\Lucene\Search\Query\Boolean();
		$keys = parent::get_search_words(preg_split('/\s+/', $word, -1, PREG_SPLIT_NO_EMPTY));
		// Lucene�ɓn��
		foreach ($keys as $key=>$value)
			$query->addSubquery( new \ZendSearch\Lucene\Index\Term($value), true);
		
		//  �����Ǝ��s
		$hits = $index->find($query);
		var_dump($hits);
		
		if ($non_format){
			//
		}
		return $hits;
	}
}