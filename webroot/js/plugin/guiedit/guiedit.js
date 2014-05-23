// PukiWiki - Yet another WikiWikiWeb clone.
// CKEditor for PukiWiki GUIedit

// Copyright (c) 2010 PukiWiki Advance Developer Team

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 2 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.


// This program is inspired from garand's guiedit works.
// But this is NOT compatible to garand's version.

/* jslint evil: true */

var pukiwiki_guiedit = {
	meta : {
		'@prefix': '<http://purl.org/net/ns/doas#>',
		'@about': '<guiedit.js>', a: ':JavaScript',
		 title: 'GUIEdit wraping script for Pukiwiki Advance',
		 created: '2010-09-03', release: {revision: '1.0.0', created: '2010-09-03'},
		 author: {name: 'Logue', homepage: '<http://logue.be/>'},
		 acknowledgement: {name: "�܂����[�n���J", 'homepage': "<http://teao.te.kyusan-u.ac.jp/bluemoon/index.php?%A5%D7%A5%E9%A5%B0%A5%A4%A5%F3%2FGUI%CA%D4%BD%B8>" },
		 license: '<http://www.gnu.org/licenses/gpl-2.0.html>'
	},
	// CKEditor�̐ݒ�iCKEditor.config�̃G�C���A�X�j
	// http://docs.cksource.com/CKEditor_3.x/Developers_Guide/Setting_Configurations
	config:{
		// CKEditor�̃X�L��
		skin : 'moono',
		// ���T�C�Y�������݂̂ɂ���
		resize_dir : 'vertical',
		// �G�f�B�b�^���̃X�^�C���V�[�g
		contentsCss : [COMMON_URI+'css/pukiwiki.css'],
		// �v���O�C��
//		extraPlugins : 'InternalEx, FontFormatEx,AlignEx,ListEx,IndentEx,InsertText,PukiWikiPlugin,TableEx,HRuleEx,SmileyEx,SpecialCharEx,Comment',
		// �����^�錾
		// docType: '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		docType: '<!DOCTYPE html>',	// Adv.��HTML5
		//	�I�u�W�F�N�g�̃T�C�Y�ύX�𖳌�
		disableObjectResizing: true,
		//	�\�[�X�̃|�b�v�A�b�v
		// SourcePopup : true,	// ����`�H
		//	�V���[�g�J�b�g�L�[
		keystrokes : [
			[ CKEDITOR.CTRL + 65 /*A*/, true ],
			[ CKEDITOR.CTRL + 67 /*C*/, true ],
			[ CKEDITOR.CTRL + 70 /*F*/, true ],
			[ CKEDITOR.CTRL + 83 /*S*/, true ],
			[ CKEDITOR.CTRL + 88 /*X*/, true ],
			[ CKEDITOR.CTRL + 86 /*V*/, 'Paste' ],
			[ CKEDITOR.CTRL + 90 /*Z*/, 'Undo' ],
			[ CKEDITOR.CTRL + 89 /*Y*/, 'Redo' ],
			[ CKEDITOR.CTRL + 76 /*L*/, 'Link' ],
			[ CKEDITOR.CTRL + 50 /*B*/, 'Bold' ],
			[ CKEDITOR.CTRL + 73 /*I*/, 'Italic' ],
			[ CKEDITOR.CTRL + 85 /*U*/, 'Underline' ],
			[ CKEDITOR.CTRL + CKEDITOR.ALT + 13 /*ENTER*/, 'FitWindow' ],
			[ CKEDITOR.CTRL + CKEDITOR.ALT + 83 /*S*/, 'Source' ]
		],
		//	�c�[���o�[
		toolbar : [
			['Source','-','Cut','Copy','Paste','PasteText','PasteWord','-','SpellCheck'],
			['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
			['Link','Unlink','Anchor'],
			['InsertText','Attachment','PukiWikiPlugin','Note','Comment'],
			['Table','Rule','Smiley','SpecialChar','-','PageBreak'],
			['FitWindow','ShowBlocks','-','About'],
			'/',
			['FontFormat','FontSize'],
			['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
			['OrderedList','UnorderedList','DList','-','Outdent','Indent','Blockquote'],
			['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
			['TextColor','BGColor']
		],
		//	�R���e�L�X�g���j���[
		menu_groups : ['Generic','Link','Anchor'],
		// �t�H�[�}�b�g
	
		// �����̑���
		fontSize_sizes : '8px;9px;10px;11px;12px;14px;16px;18px;20px;24px;28px;32px;40px;48px;60px;'
			+ 'xx-small;x-small;small;medium;large;x-large;xx-large',
		coreStyles_bold: {element : 'strong'},
		coreStyles_italic: {element :'em'},
		// �����N

	},
	init: function(){
		var self = this;
		// �t�H�[���̃��b�N
		$('input','button','select','textarea').attr('disabled','disabled');
		this.editor = $('textarea[name=msg]');	// �ϊ�����t�H�[��
		this.editor.ckeditor(
			function(){
				/*
				// CKEditor���s�O�Ɏ��s���鏈��
				$("textarea[name='msg']").before('<div id="indicator" style="text-align:right;"></div>');
				// �v���r���[��ʂ�DOM����
				$('#guiedit').after([
					'<div id="realview_outer" style="clear:both;">',
					'	<div id="realview"></div>',
					'</div>'
				].join("\n"));
				// �C���W�P�[�^�[
				$('div#indicator').html('<img src="'+pukiwiki_skin.image_dir+'spinner.gif" alt="Loading..." />Now Loading...');
				$('div#indicator').animate({height:'20px'});
				// �f�t�H���g�̃e�L�X�g��ǂݍ���
				*/
				self.load();
			}
			//this.config	// �ݒ����
		);

		this.CKEditor = this.editor.ckeditorGet();	// CKEditor�̃G�C���A�X
	},
	// ���[�h
	load : function(){
		var self = this;
		// �e�L�X�g�G���A�̓��e���擾
		// �����āA�X�L����UIblock���g��
		$.ajax({
			url:SCRIPT,
			dataType:'json',
			type:'GET',
			data: {
				cmd : 'guiedit',
				edit: 1,
				page: $('input[name=page]').val(),
				id:$('input[name=id]').val()
			},
			success: function(data){
				self.editor.val(data.msg);
				// �����ɂ����������Ԃ��C���W�P�[�^�[�ɕ\��
				$('div#indicator').html('Convert time :'+data.taketime);
	
				// ���e�{�^���̃C�x���g����
				$('button[name=write]').click(function(){
					self.write();
				});
	
				// �v���r���[�{�^���̃C�x���g����
				$('button[name=preview]').click(function(){
					self.preview();
				});
	
				// �e���v���[�g�̃|�b�v�A�b�v���j���[�̃C�x���g���蓖�āi�l�ύX���ɃC�x���g���s�j
				$('select[name=template_page]').change(function(){
					self.template();
				});
	
				// �t�H�[�����b�N������
				$('input','button','select','textarea').attr('disabled');
			},
			error : function(data,status,thrown){
				alert(status);
				console.error(data);
				$('input[name=cancel]').attr('disabled');
			}
		});
	},
	// �e���v���[�g�ďo
	template: function(){
		var self = this;
		$.ajax({
			url:SCRIPT,
			dataType:'json',
			type:'POST',
			data: {
				cmd : 'guiedit',
				edit: 1,
				page: PAGE,
				template_page:$('select[name=template_page]').val()
			},
			beforeSend: function(){
				$('#indicator').html('Now Loading...');
				$('input','button','select','textarea').attr('disabled','disabled');
			},
			success: function(data){
				self.editor.val(data.msg);
				$('#indicator').html('Convert time :'+data.taketime);
			},
			error : function(data,status,thrown){
				$('#indicator').html('<span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>Error!');
				console.error(data);
				$('input[name=cancel]').attr('disabled');
			}
		});
	},
	// �v���r���[�擾
	preview: function(){
		var self = this;
		$.ajax({
			url:SCRIPT,
			dataType:'json',
			type:'POST',
			global : false,
			data: {
				cmd : 'guiedit',
				edit: 1,
				page: PAGE,
				msg : this.editor.val()
			},
			beforeSend: function(){
				$('#indicator').html('Now Loading...');
				$('input','button','select','textarea').attr('disabled','disabled');
			},
			success: function(data){
				$("#realview_outer").animate({height:'200px'});
				$('#indicator').html('Convert time :'+data.taketime);
				$('input','button','select','textarea').attr('disabled');
				$("#realview").html(data.msg);
			},
			error : function(data,status,thrown){
				$('#indicator').html('<span class="fa fa-danger"></span>Error!');
				console.error(data);
				$('input[name=cancel]').attr('disabled');
			}
		});
	},
	// ��������
	write: function(){
		this.unload(true);
	},
	// �y�[�W����o�鎞
	unload: function(bEnable){
		window.onbeforeunload = function () {
			if(bEnable && this.CKEditor.checkDirty()){
				return '���s����ƕύX���e���j������܂��B';
			}
			return;
		};
	}
};

$(document).ready(function(){
	pukiwiki_guiedit.init();
});

