<?php
/*
Facelift Image Replacement v2.0 beta 2
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

define('FLIR_VERSION', '2.0b2');
define('IS_WINDOWS', (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN'));

define('DEBUG', false);

require_once('config-flir.php');
require_once('inc-flir.php');

// Make sure referrer matches the ALLOWED_DOMAIN, if set
// This is not 100% as referrers can be easily spoofed and may block legit web users
if(false !== ALLOWED_DOMAIN && $_SERVER['HTTP_REFERER'] != '') {
	$refhost = get_hostname($_SERVER['HTTP_REFERER']);
	if(substr(ALLOWED_DOMAIN, 0, 1) == '.') {
		if(false === strpos($refhost, substr(ALLOWED_DOMAIN, 1)))
			err('DISALLOWED_DOMAIN');
	}else {
		if($refhost != ALLOWED_DOMAIN) 
			err('DISALLOWED_DOMAIN');
	}
}

$fonts_dir = str_replace('\\', '/', realpath(FONTS_DIR.'/')); // fix FONTS_DIR path
if(substr($fonts_dir, -1) != '/')
	$fonts_dir .= '/';

// Grab the CSS settings being passed with request
$CSS = parse_css_codes($_GET['c']);
							
$FLIR = array();

$passed_fstyle = preg_match('#^\{("[\w]+":"?[^"]*"?,?)*\}$#i', $_GET['f'])?json_decode($_GET['f'], true):array();
$FStyle = array_merge($Default_FLIRStyle_Values, $passed_fstyle);

$FLIR['mode']        		= isset($FStyle['mode']) && preg_match('#^[a-z0-9_-]+$#i', $FStyle['mode']) ? $FStyle['mode'] : '';
$FLIR['output']        		= isset($FStyle['output']) ? $FStyle['output'] : 'png';

$FLIR['bkg_transparent'] 	= is_transparent($CSS['background-color']);

if($FLIR['output'] == 'auto')
	$FLIR['output'] = $FLIR['bkg_transparent'] ? 'png' : 'gif';
    
// format not supported, fall back to png
if($FLIR['output'] == 'gif' && !function_exists('imagegif'))
	$FLIR['output'] = 'png';

$FLIR['dpi'] 					= preg_match('#^\d+$#', $_GET['d']) ? $_GET['d'] : 96;
if($FStyle['hq'] == 'true')
	$FLIR['dpi']/=2;
$FLIR['size']     			= is_number($CSS['font-size'], true) ? $CSS['font-size'] : UNKNOWN_FONT_SIZE; // pixels
$FLIR['size_pts'] 			= get_points($FLIR['dpi'], $FLIR['size']);
$FLIR['maxheight']			= is_number($_GET['h']) ? $_GET['h'] : UNKNOWN_FONT_SIZE; // pixels
$FLIR['maxwidth']				= is_number($_GET['w']) ? $_GET['w'] : 800; // pixels

$FLIR['color']         		= convert_color($CSS['color']);

if($FLIR['bkg_transparent']) {
	$FLIR['bkgcolor'] = array('red' 		=> abs($FLIR['color']['red']-100)
									, 'green'	=> abs($FLIR['color']['green']-100)
									, 'blue'		=> abs($FLIR['color']['blue']-100));
}else {
	$FLIR['bkgcolor'] = convert_color($CSS['background-color'], false, 'FFFFFF');
}

$FLIR['opacity'] = is_number($CSS['opacity'], true) ? $CSS['opacity']*100 : 100;
if($FLIR['opacity'] > 100 || $FLIR['opacity'] < 0) 
	$FLIR['opacity'] = 100;    

$FLIR['text_encoded'] 		= $_GET['t']!=''? prepare_text($_GET['t']) :'null';
$FLIR['text'] 					= html_entity_decode_utf8($FLIR['text_encoded']);

/*
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body>
<pre>';
print_r($FLIR);
exit;
*/

$md5 = md5(($FLIR['mode']=='wrap'?$FLIR['maxwidth']:'').$FLIR['font'].($_GET['c'].$FLIR['text']));
$FLIR['cache']					= get_cache_fn($md5, $FLIR['output']);
$FLIR['file_meta']			= array(); // plugins can store info in file_meta that will be available to other plugins

$font_file = '';
$CSS['font-family'] = strtolower($CSS['font-family']);
$FONT_PARENT = false;
if(isset($fonts[$CSS['font-family']])) {
	$font_file = $fonts[$CSS['font-family']];
	
	if(is_array($font_file)) {
		$FONT_PARENT = reset($font_file);
		$font_file = match_font_style($font_file);
		$FONT_PARENT = $fonts_dir.(isset($FONT_PARENT['file']) ? $FONT_PARENT['file'] : $font_file);
	}
}elseif(FONT_DISCOVERY) {
	$font_file = discover_font($fonts['default'], $CSS['font-family']);
}else {
	$font_file = $fonts['default'];
}
$FLIR['font']     			= $fonts_dir.$font_file;

if(!is_file($FLIR['font']))
	err('FONT_DOESNT_EXIST');
    
$SPACE_BOUNDS = false;
if(is_number($CSS['letter-spacing'], true, false, true)) {
	$SPACE_BOUNDS = bounding_box(' ');
	$spaces = ceil(($CSS['letter-spacing']/$SPACE_BOUNDS['width']));
	if($spaces>0) {
		$FLIR['text'] = space_out($FLIR['text'], $spaces);
		define('SPACING_GAP', $spaces);
	}
}

if(($SPACES_COUNT = substr_count($FLIR['text'], ' ')) == strlen($FLIR['text'])) {
	if(false === $SPACE_BOUNDS)
		$SPACE_BOUNDS = bounding_box(' '); 
	
	$FLIR['cache'] = get_cache_fn(md5($FLIR['font'].$FLIR['size'].$SPACES_COUNT));
	$FLIR['mode'] = 'spacer';
}

if(false !== PREPROC_AUTORUN) {
	$autorun = explode('|', PREPROC_AUTORUN);
	if(!empty($autorun)) {
		foreach($autorun as $request) {
			if(false !== strpos($request, '?')) {
				list($plugin, $qs) = explode('?', $request, 2);
				parse_str($qs, $SETTINGS);
			}else {
				$plugin = $request;
				$SETTINGS = array();
			}
			
			include(PREPROC_PLUGIN_DIR.'/'.$plugin.'.php');
		}
	}
}

if(file_exists($FLIR['cache']) && !DEBUG) {
	output_file($FLIR['cache']);
}else {
	// If FLIR is running, you can comment these verify_ functions out
	verify_safemode(); 
	verify_gd();
    
	$REAL_HEIGHT_BOUNDS = $FStyle['fixBaseline']=='true' ? bounding_box(HBOUNDS_TEXT, (false !== $FONT_PARENT ? $FONT_PARENT : $FLIR['font'])): false;
    
	switch($FLIR['mode']) {
		default:
			$dir = dir(RENDER_PLUGIN_DIR);
			$php_mode = strtolower($FLIR['mode'].'.php');
			while(false !== ($entry = $dir->read())) {
				$p = RENDER_PLUGIN_DIR.'/'.$entry;
				if(is_dir($p) || $entry == '.' || $entry == '..') continue;
		
				if($php_mode == strtolower($entry)) {
					$dir->close();
					$PLUGIN_ERROR = false;                    
		
					include($p);
						 
					if(false !== $PLUGIN_ERROR)
						break;
					else
						break(2);
				}
			}
			$dir->close();

		case 'static':				
			$bounds = bounding_box($FLIR['text']);
			if($FStyle['fixBaseline']!='true') 
				$REAL_HEIGHT_BOUNDS = $bounds;
			
			if(false === (@$image = imagecreatetruecolor($bounds['width'], $REAL_HEIGHT_BOUNDS['height'])))
				err('COULD_NOT_CREATE');
			
			gd_alpha();
			imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), gd_bkg());
			imagettftext($image, $FLIR['size_pts'], 0, $bounds['xOffset']
							,$REAL_HEIGHT_BOUNDS['yOffset'], gd_color($image), $FLIR['font'], $FLIR['text']);
			break;
		case 'wrap':
//		is_number($str, $bAllowDecimals=false, $bAllowZero=false, $bAllowNeg=false)
			if(!is_number($CSS['line-height'], true, true, true))
				$CSS['line-height'] = 1.0;

			$bounds = bounding_box($FLIR['text']);
			if($FStyle['fixBaseline']!='true') 
				$REAL_HEIGHT_BOUNDS = $bounds;
 
			// if mode is wrap, check to see if text needs to be wrapped, otherwise let continue to progressive
			if($bounds['width'] > $FLIR['maxwidth']) {
				$image = imagettftextbox($FLIR['size_pts'], 0, 0, 0, $FLIR['color'], $FLIR['font'], $FLIR['text'], $FLIR['maxwidth'], strtolower($CSS['text-align']), $CSS['line-height']);
				if(false !== $image) // if cannot wrap, don't break and continue on to next mode
					break;
			}
		case 'progressive':
			$bounds = bounding_box($FLIR['text']);
			if($FStyle['fixBaseline']!='true') 
				$REAL_HEIGHT_BOUNDS = $bounds;
		
			$offset_left = 0;
		
			$nsize=$FLIR['size_pts'];
			while(($REAL_HEIGHT_BOUNDS['height'] > $FLIR['maxheight'] || $bounds['width'] > $FLIR['maxwidth']) && $nsize > 2) {
				$nsize-=0.5;
				$bounds = bounding_box($FLIR['text'], NULL, $nsize);
				$REAL_HEIGHT_BOUNDS = $FStyle['fixBaseline']=='true' ? bounding_box(HBOUNDS_TEXT, NULL, $nsize) : $bounds;
			}
			$FLIR['size_pts'] = $nsize;
		
			if(false === (@$image = imagecreatetruecolor($bounds['width'], $REAL_HEIGHT_BOUNDS['height'])))
				err('COULD_NOT_CREATE');
		
			gd_alpha();
			imagefilledrectangle($image, $offset_left, 0, imagesx($image), imagesy($image), gd_bkg());
		
			imagettftext($image, $FLIR['size_pts'], 0, $bounds['xOffset']
							,$REAL_HEIGHT_BOUNDS['yOffset'], gd_color(), $FLIR['font'], $FLIR['text']);
			break;
		
		case 'spacer':
			if(false === (@$image = imagecreatetruecolor(($SPACE_BOUNDS['width']*$SPACES_COUNT), 1)))
			err('COULD_NOT_CREATE');
			
			imagesavealpha($image, true);
			imagealphablending($image, false);
			
			imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), gd_bkg());
			break;
	}

	if($FStyle['hq'] == 'true') {
		$w = imagesx($image);
		$h = imagesy($image);
		$origw = round($w/2);
		$origh = round($h/2);
		$nimg = imagecreatetruecolor($origw, $origh);
		imagecopyresampled($nimg, $image, 0, 0, 0, 0, $origw, $origh, $w, $h);
		imagedestroy($image);
		$image = $nimg;
	}

	if(false !== $image) {
		if($FLIR['output']=='gif')
			imagegif($image, $FLIR['cache']);
		else
			imagepng($image, $FLIR['cache']);
		imagedestroy($image);
	}

	if(false !== POSTPROC_AUTORUN) {
		$autorun = explode('|', POSTPROC_AUTORUN);
		if(!empty($autorun)) {
			foreach($autorun as $request) {
				if(false !== strpos($request, '?')) {
					list($plugin, $qs) = explode('?', $request, 2);
					parse_str($qs, $SETTINGS);
				}else {
					$plugin = $request;
					$SETTINGS = array();
				}
				
				include(POSTPROC_PLUGIN_DIR.'/'.$plugin.'.php');
			}
		}
	}

	output_file($FLIR['cache']);
} // if(file_exists($FLIR['cache'])) {

flush();

if(CACHE_CLEANUP_FREQ != -1 && rand(1, CACHE_CLEANUP_FREQ) == 1)
	@cleanup_cache();
?>