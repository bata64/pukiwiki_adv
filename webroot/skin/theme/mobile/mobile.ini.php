<?php
/**
 * PukiWiki - Yet another WikiWikiWeb clone.
 * $Id: mobile.ini.php,v 0.0.5 2014/04/06 17:50:30 Logue Exp $
 * 
 * PukiWiki Adv. Mobile Theme
 * Copyright (C)
 *   2012-2014 PukiWiki Advance Developer Team
 */
defined('IS_MOBILE') or define('IS_MOBILE', true);

return array(
	/**
	 * �����͕ύX���Ȃ��ł�������
	 */
	'showicon' => true,
	'ui_theme' => null,
	'default_css' => false,
	/**
	 * ���o�C���̃e�[�}
	 * default: �f�t�H���g
	 * inverse: �������]
	 */
	'mobile_theme' => 'default',
	/**
	 * Bootswatch�̃e�[�}�iinverse�̎��̓R�����g�A�E�g�j
	 */
	//'bootswatch' => 'slate',
	/**
	 * ���j���[�ŕ\������鍀��
	 */
	'navibar' => 'top,edit,freeze,diff,backup,upload,reload,new,list,search,recent,help,login',
	/**
	 * �L���\���̈�
	 */
	'adarea'	=> <<<EOD
EOD
);

/* End of file mobile.ini.php */
/* Location: ./webroot/skin/theme/mobile/mobile.ini.php */