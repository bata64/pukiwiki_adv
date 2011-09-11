<?php
/**
 :prefix <http://purl.org/net/ns/doas#> .
 :about "<mml.inc.php>", a: ":PHPScript",
 :shortdesc "JSMML Player for PukiWiki";
 :created "2008-07-28", release: {revision: "1.2.5", created: "2011-02-05"},
 :author [:name "Logue"; :homepage <http://logue.be/> ];
 :license <http://www.gnu.org/licenses/gpl-3.0.html>;
*/
// JSMML for PukiWiki.
// Copyright (c)2008-2011 Logue <http://logue.be/> All rights reserved.

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

define('JSMML_PATH', JS_URI . 'plugin/jsmml');

function plugin_mml_convert(){
	global $script, $mml_count;
	$lang = null;

	if ($mml_count == '') $mml_count = 0;

	// �����ǂݍ���
	$value = func_get_args();	#���g
	$args = func_num_args();	#�����̐�

	if ($args == 0){
		return '#mml() USAGE:#mml(title){mmldata}';
	// mabimml�Ƃ̌݊������B�i���Ă���̂��H�j
	}else if ($args == 1){
		// #mml(mml)
		$data = $value[0];
	}else if ($args == 2){
		// #mml(���O){MML}
		$title = $value[0];
		if ($title == "reverse"){
			$data = plugin_mml_octave_reverse($data);
			$title = '';
		}
		$data = $value[1];
	}else{
		// #mml(���O,�y��){MML}	//Mabinogi�݊������i�������̓I�N�^�[�u���t�]�j
		$title = $value[0];
		$inst = $value[1];
		$data = $value[$args-1];
		//�I�N�^�[�u���]
		$data = plugin_mml_mabimml2mml($data);
	}

	// Mabinogi�p��MML�����͂���Ă����ꍇ�imabimml.inc.php�Ƃ̌݊������j
	if (preg_match ('/^MML@/i', $data)){
		$data = plugin_mml_mabimml2mml($inst,$data);
	}
	
	$ret = '<pre class="mml-source">'.htmlsc($data).'</pre>';
	if($title){
		$html = <<<HTML
<fieldset>
	<legend class="mml-title">$title</legend>
	$ret
</fieldset>
HTML;
	}else{
		$html = $ret;
	}
	
	if ($mml_count == 0){
		global $js_tags, $css_blocks;

		$jsmml_path = JSMML_PATH;
		$js_tags[] = array('type'=>'text/javascript', 'src'=>$jsmml_path.'/mml.js');
	}
	$mml_count++;
	return $html;
}
?>