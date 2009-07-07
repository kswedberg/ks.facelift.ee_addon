<?php
// JavaScript Document

/*
FancyFonts v0.4.0

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

$PLUGIN_ERROR = false;
define('FULL_CACHE_PATH', fix_path(getcwd().'/'.$FLIR['cache']));
define('CONVERT', IM_EXEC_PATH.'convert');

if(DEBUG && file_exists(FULL_CACHE_PATH))
	unlink(FULL_CACHE_PATH);

$image = false;

if($FLIR['text'][0] == '@') $FLIR['text'] = '\\'.$FLIR['text'];

$bounds = bounding_box($FLIR['text']);// convertBoundingBox(imagettfbbox($FLIR['size_pts'], 0, $FLIR['font'], $FLIR['text']));

$fulltrim = '';
if($FStyle['fixBaseline']!='true') {
	$bounds['height'] += 200;
	$REAL_HEIGHT_BOUNDS = $bounds;	
	$fulltrim = '-trim +repage';
}
	
$fore_hex = dec2hex($FLIR['color']['red'], $FLIR['color']['green'], $FLIR['color']['blue']);
$bkg_hex = $FLIR['output'] == 'png' ? 'transparent' : ('"#'.dec2hex($FLIR['bkgcolor']['red'], $FLIR['bkgcolor']['green'], $FLIR['bkgcolor']['blue']).'"');

$opacity = '';
if($FLIR['opacity'] < 100 && $FLIR['opacity'] >= 0)
	$opacity = strlen($FLIR['opacity']) == 1 ? '0'.$FLIR['opacity'] : (strlen($FLIR['opacity'])>2?substr($FLIR['opacity'], 0, 2) : $FLIR['opacity']);

switch($FStyle['cAlign']) {
	case 'center':
		$align = 'center';
		break;
	default:
		$align = 'west';
		break;
	case 'right':
		$align = 'east';
		break;
}

if($FStyle['ff_Wrap']=='true') {
	$cmd = CONVERT.' -background '.$bkg_hex.' '
						.' -font '.escapeshellarg(fix_path($FLIR['font']))
						.' -fill '.escapeshellarg('#'.$fore_hex.$opacity)
						.' -density '.$FLIR['dpi'].' -pointsize '.$FLIR['size_pts'].' -gravity '.$align
						.'  -size '.$FLIR['maxwidth'].'x'
						.' caption:'.escapeshellarg($FLIR['text'])
						.' '.escapeshellarg(FULL_CACHE_PATH); 
}else {
	$xOffset = $bounds['xOffset'] >= 0 ? ('+'.$bounds['xOffset']) : $bounds['xOffset'];
	$yOffset = $bounds['yOffset'] >= 0 ? ('+'.$bounds['yOffset']) : $bounds['yOffset'];
	$cmd = CONVERT.' -size '.($bounds['width']+300).'x'.$REAL_HEIGHT_BOUNDS['height'].' xc:'.$bkg_hex.' '
						.' -font '.escapeshellarg(fix_path($FLIR['font']))
						.' -fill '.escapeshellarg('#'.$fore_hex.$opacity)
						.' -density '.$FLIR['dpi'].' -pointsize '.$FLIR['size_pts']
						.' -annotate 0x0'.$xOffset.$yOffset.' '.escapeshellarg($FLIR['text'])
						.' '.$fulltrim.' '.escapeshellarg(FULL_CACHE_PATH); 
}

//die($cmd);
exec($cmd);

if($FStyle['ff_BlurEdges']=='true') {
	$cmd2 = CONVERT.' '.escapeshellarg(FULL_CACHE_PATH).' -matte -virtual-pixel transparent -channel A -blur 0x0.3  -level 0,90%  '.escapeshellarg(FULL_CACHE_PATH);	
	exec($cmd2);
}


if($FStyle['ff_Wrap']!='true' && $FStyle['fixBaseline']=='true') { // trim sides
	$info = shell_exec(CONVERT.' '.escapeshellarg(FULL_CACHE_PATH).' -trim info:');
	if(preg_match('#(PNG|GIF|JPEG) ([0-9]+)x([0-9]+) ([0-9]+)x([0-9]+)([+-][0-9]+)([+-][0-9]+)#', $info, $m))
		exec(CONVERT.' '.escapeshellarg(FULL_CACHE_PATH)
				.' -crop '.$m[2].'x'.$m[5].$m[6].'+0 +repage '
				.escapeshellarg(FULL_CACHE_PATH));
}
?>