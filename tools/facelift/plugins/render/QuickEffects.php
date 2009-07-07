<?php
// JavaScript Document

/*
QuickEffects v0.4.0

Facelift was written and is maintained by Cory Mawhorter.  
It is available from http://facelift.mawhorter.net/

===

This file is part of Facelife Image Replacement ("FLIR").

FLIR is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

FLIR is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Facelift Image Replacement.  If not, see <http://www.gnu.org/licenses/>.
*/

function add_girth($wh, $bHeight=false) {
	global $add_height,$add_width;
	
	if($bHeight) 
		if($wh>$add_height)
			$add_height = $wh;
	else
		if($wh>$add_width)
			$add_width = $wh;
}


$PLUGIN_ERROR = false;
define('FULL_CACHE_PATH', fix_path(getcwd().'/'.$FLIR['cache']));
define('QE_DIR', fix_path(getcwd().'/'.PLUGIN_DIR.'/QuickEffects'));
define('CONVERT', IM_EXEC_PATH.'convert');
	
if(!file_exists(QE_DIR)) {
	@mkdir(QE_DIR);
	if(!file_exists(QE_DIR))
		die('QuickEffects directory does not exists and cannot be created.');
}

if(DEBUG && file_exists(FULL_CACHE_PATH))
	unlink(FULL_CACHE_PATH);

$image = false;

if($FLIR['text'][0] == '@') $FLIR['text'] = '\\'.$FLIR['text'];
if(substr_count($FLIR['text'], ' ') == strlen($FLIR['text'])) exit;

$cmdtext = escapeshellarg($FLIR['text']);

$bounds = convertBoundingBox(imagettfbbox($FLIR['size_pts'], 0, $FLIR['font'], $FLIR['text']));

$fulltrim = '';
if($FStyle['fixBaseline']!='true') {
	$bounds['height'] += 200;
	$REAL_HEIGHT_BOUNDS = $bounds;	
	$fulltrim = '-trim +repage ';
}
	
$fore_hex = dec2hex($FLIR['color']['red'], $FLIR['color']['green'], $FLIR['color']['blue']);
$bkg_hex = $FLIR['output'] == 'png' ? 'transparent' : ('"#'.dec2hex($FLIR['bkgcolor']['red'], $FLIR['bkgcolor']['green'], $FLIR['bkgcolor']['blue']).'"');
$out_width  = $bounds['width']+300;
$out_height = $REAL_HEIGHT_BOUNDS['height'];


$opacity = '';
if($FLIR['opacity'] < 100 && $FLIR['opacity'] >= 0)
	$opacity = strlen($FLIR['opacity']) == 1 ? '0'.$FLIR['opacity'] : (strlen($FLIR['opacity'])>2?substr($FLIR['opacity'], 0, 2) : $FLIR['opacity']);

$add_width = $add_height = 0;

$stroke = '';
$strokewidth = 0;
if(isset($FStyle['qe_Stroke'])) {
	list($strokewidth, $strokecolor, $quality) = explode(',', $FStyle['qe_Stroke'], 3);
	
	$strokewidth=trim($strokewidth);
	$strokecolor=trim($strokecolor);
	$strokewidth = is_number($strokewidth)?$strokewidth:1;
	$strokecolor = escapeshellarg('#'.(is_hexcolor($strokecolor)?strtoupper($strokecolor):'FF0000'));

	if(trim($quality) == 'high') {
		for($i=1; $i <= $strokewidth; $i++) {
			$stroke .= '-fill '.$strokecolor.' -annotate 0x0-'.$i.'-'.$i.' '.$cmdtext.' ';
			$stroke .= '-fill '.$strokecolor.' -annotate 0x0+'.$i.'-'.$i.' '.$cmdtext.' ';
			$stroke .= '-fill '.$strokecolor.' -annotate 0x0+'.$i.'+'.$i.' '.$cmdtext.' ';
			$stroke .= '-fill '.$strokecolor.' -annotate 0x0-'.$i.'+'.$i.' '.$cmdtext.' ';
		}
	}else {
		$stroke = '-strokewidth '.$strokewidth.' -stroke '.$strokecolor;
	}
	
	add_girth($strokewidth*2);
	add_girth($strokewidth*2, true);
}

$fill = '-fill '.escapeshellarg('#'.$fore_hex.$opacity).' ';
if(isset($FStyle['qe_Fill'])) {
	list($fill_type, $fill_options) = explode(',', $FStyle['qe_Fill'], 2);
	switch($fill_type) {
		case 'pattern':
			$pattern_file = QE_DIR.(IS_WINDOWS?'\\':'/').basename($fill_options);
			if(!file_exists($pattern_file))
				die('Pattern file does not exists.  Be sure that you have uploaded the pattern file to the QuickEffects directory.');
			$fill = '-tile '.escapeshellarg($pattern_file).' ';
			break;
		default:
		case 'gradient':
			list($color1, $color2) = explode(',', $fill_options, 2);
			$color1 = is_hexcolor($color1)?strtoupper($color1):'999999';
			$color2 = is_hexcolor($color2)?strtoupper($color2):'000000';
			$fill = '-tile gradient:'.escapeshellarg('#'.$color1.$opacity).'-'.escapeshellarg('#'.$color2.$opacity).' ';
			break;
	}
}

$extrude = '';
if(isset($FStyle['qe_Extrude'])) {
	list($extrudedirection, $extrudewidth, $extrudecolor) = explode(',', $FStyle['qe_Extrude'], 3);
	
	$extrudewidth=trim($extrudewidth);
	$extrudecolor=trim($extrudecolor);
	$extrudewidth = is_number($extrudewidth)?$extrudewidth:1;
	$extrudecolor = escapeshellarg('#'.(is_hexcolor($extrudecolor)?strtoupper($extrudecolor):'FF0000').$opacity);

	add_girth($extrudewidth*2);
	add_girth($extrudewidth*2, true);
	
	$offset = $strokewidth;
	
	for($i=$strokewidth; $i < ($extrudewidth+$strokewidth); $i++) {
		$extrude .= '-fill '.$extrudecolor.' -annotate 0x0';
		switch($extrudedirection) {
			case 'ne':
				$extrude .= '+'.$i.'-'.$i;
				break;
			case 'nw':
				$extrude .= '-'.$i.'-'.$i;
				break;
			default:
			case 'se':
				$extrude .= '+'.$i.'+'.$i;
				break;
			case 'sw':
				$extrude .= '-'.$i.'+'.$i;
				break;		
		}
		$extrude .= ' '.$cmdtext.' ';
	}
	
	if($offset>0) {
		add_girth(($extrudewidth+$strokewidth)*2);
		add_girth(($extrudewidth+$strokewidth)*2, true);
	}else {
		add_girth($extrudewidth*2);
		add_girth($extrudewidth*2, true);
	}
}

// SHADOW
$shadow = '';
if(isset($FStyle['qe_Shadow'])) {
	switch($FStyle['qe_Shadow']) {
		case 'high':
			$shadow = array('opacity' 		=> 55
								,'sigma' 		=> 3
								,'left' => '+2'
								,'top' 	=> '+2'
								,'color' => '000000');
			break;
		case 'low':
			$shadow = array('opacity' 		=> 65
								,'sigma' 		=> 2
								,'left' => '+2'
								,'top' 	=> '+2'
								,'color' => '000000');
			break;
		case 'fuzzy':
			$shadow = array('opacity' 		=> 55
								,'sigma' 		=> 4
								,'left' => '+0'
								,'top' 	=> '+0'
								,'color' => '000000');
			break;
		case 'hard':
			$shadow = array('opacity' 		=> 90
								,'sigma' 		=> 0
								,'left' => '+1'
								,'top' 	=> '+1'
								,'color' => '000000');
			break;
/*
		case 'perspective':
			$shadow = array('opacity' 		=> 75
								,'sigma' 		=> 0
								,'left' => '+1'
								,'top' 	=> '+1');
			break;
		*/
		
		default:
			list($shadow_opac, $shadow_sig, $shadow_ol, $shadow_ot, $shadow_color) = explode(',', $FStyle['qe_Shadow'], 4);
			$shadow_opac = (is_number($shadow_opac) && $shadow_opac<=100) ? $shadow_opac : 75;
			$shadow_sig = is_number($shadow_sig)?$shadow_sig:2;
			$shadow_ol = preg_match('#^[+-][0-9]{1,4}$#', $shadow_ol)?$shadow_ol:'+2';
			$shadow_ot = preg_match('#^[+-][0-9]{1,4}$#', $shadow_ot)?$shadow_ot:'+2';
			$shadow_color = is_hexcolor($shadow_color)?strtoupper($shadow_color):'FF0000';
			$shadow = array('opacity' 	=> $shadow_opac
								,'sigma' 	=> $shadow_sig
								,'left' 		=> $shadow_ol
								,'top' 		=> $shadow_ot
								,'color'		=> $shadow_color);
			break;
	}
	
	$shadow = (IS_WINDOWS?'':'\\').'( +clone -background '.escapeshellarg('#'.$shadow['color']).'  -shadow '.$shadow['opacity'].'x'.$shadow['sigma'].$shadow['left'].$shadow['top'].' '.(IS_WINDOWS?'':'\\').' ) +swap -background none -mosaic -matte ';
	
	$left = substr($shadow['offset-left'], 1);
	$top = substr($shadow['offset-top'], 1);
	
	if($left>0)
		add_girth($left*2);
	if($top>0)
		add_girth($top*2, true);
}

$out_width 	+= $add_width;
$out_height += $add_height;

$cmd = CONVERT.' -size '.$out_width.'x'.$out_height.' xc:'.$bkg_hex.' '
					.'-font '.escapeshellarg(fix_path($FLIR['font']))
					.' -density '.$FLIR['dpi'].' -pointsize '.$FLIR['size_pts'].' -gravity North '
					.$extrude.' '
					.$stroke.' '
					.$fill.' '
					.' -annotate 0 '.$cmdtext.' '
					.$shadow.' '
					.' '.$fulltrim.' '.escapeshellarg(FULL_CACHE_PATH);

//die($cmd);
exec($cmd);

if($FStyle['fixBaseline']=='true') { // trim sides
	$info = shell_exec(CONVERT.' '.escapeshellarg(FULL_CACHE_PATH).' -trim info:');
	if(preg_match('#(PNG|GIF|JPEG) ([0-9]+)x([0-9]+) ([0-9]+)x([0-9]+)([+-][0-9]+)([+-][0-9]+)#', $info, $m))
		exec(CONVERT.' '.escapeshellarg(FULL_CACHE_PATH)
				.' -crop '.$m[2].'x'.$m[5].$m[6].'+0 +repage '
				.escapeshellarg(FULL_CACHE_PATH));
}

?>