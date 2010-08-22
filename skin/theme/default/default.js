// PukiWiki - Yet another WikiWikiWeb clone.
// xxxloggue skin script.
// Copyright (c)2010 PukiWiki Advance Developers Team

// $Id: xxxlogue.js,v 1.0.0 2010/08/19 16:27:00 Logue Exp$

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

var colorset = ['blue',		'green',		'orange'];
var ui_theme = ['redmond',	'south-street',	'ui-lightness'];
var default_set_num = 0;

pukiwiki_skin.custom = {
	// �X�L���X�N���v�g��init�����s�����O�Ɏ��s�����֐�
	before_init : function(){
		// �N�b�L�[����`����Ă��Ȃ��Ƃ��́Ablue�Ƃ��A�N�b�L�[�ɕۑ�
		if (!$.cookie('pkwk-colorset')){ $.cookie('pkwk-colorset',default_set_num,{expires:30,path:'/'}); }
		document.getElementById('coloring').href = SKIN_DIR+'theme/'+THEME_NAME+'/'+colorset[$.cookie('pkwk-colorset')]+'.css';
		document.getElementById('ui-theme').href = 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/themes/'+ui_theme[$.cookie('pkwk-colorset')]+'/jquery-ui.css';

		// �J���[�Z�b�g�̃����N�{�^���𐶐�
		var buffer = '';
		$('#header').before('<p id="colorset" style="float:right;"></p>');
		for (var n=0; n<colorset.length; n++){
			buffer += '<span style="color:'+colorset[n]+';cursor:pointer;" id="colorset-'+n+'">&#x25fc;</span>&nbsp;';
		}
		$('#colorset').html(buffer);
	},
	// �X�L���X�N���v�g��init�����s���ꂽ�O�Ɏ��s�����֐�
	init : function(){
		// �J���[�Z�b�g�̃����N�{�^���ɃC�x���g���蓖��
		$('#colorset span').click(function(){
			var no = this.id.split('-')[1];
			document.getElementById('coloring').href = SKIN_DIR+'theme/'+THEME_NAME+'/'+colorset[no]+'.css';
			document.getElementById('ui-theme').href = 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/themes/'+ui_theme[no]+'/jquery-ui.css';
			$.cookie('pkwk-colorset',no,{expires:30,path:'/'});
		});
	},
	// �X�L���X�N���v�g��unload�����s�����O�Ɏ��s�����֐�
	before_unload : function(){
	},
	// �X�L���X�N���v�g��init�����s���ꂽ��Ɏ��s�����֐�
	unload: function(){
	},
	// Superfish�ݒ�
	// http://users.tpg.com.au/j_birch/plugins/superfish/#options
//	suckerfish : { }
}