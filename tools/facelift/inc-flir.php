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
if(consttrue('DEBUG')) {
	header('Expires: Wed, 27 Jul 1983 05:00:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache');
}

$ERROR_MSGS = array(
	 'COULD_NOT_CREATE'				=> 'Invalid: Could not create image.'
	,'COULD_NOT_SETLOCALE'			=> 'Unable to set the current local.  You can disable this check by removing it from inc-flir.php starting around line 37.'
	,'DISALLOWED_DOMAIN'				=> 'Bad Domain: Domain is not allowed to generate images.'
	
	,'PHP_TOO_OLD'						=> 'The version of PHP you are using is too old.  Facelift requires at least PHP v4.3.0.'
	,'PHP_UNSUPPORTED'				=> 'The version of PHP you are using is not supported.'
	
	,'GD_NOT_INSTALLED'				=> 'Facelift requires the GD extension for PHP.'
	,'GD_TOO_OLD'						=> 'The version of GD you are using is too old.  Facelift requires at least GD v2.'
	,'GD_NO_IMAGES'					=> 'Facelift needs to be able to create images in PNG or GIF.  You have none of these supported by your version of GD.  Please enable the ability to create PNG or GIF images in your GD installation.'
	,'GD_NO_FREETYPE'					=> 'The version of GD you are using does not have support for FreeType.  Facelift requires GD with FreeType support to work.'
	
	,'FONT_DOESNT_EXIST'				=> 'Cannot find font.  Be sure you have specified a valid default font file.'
	
	,'CACHE_DOESNT_EXIST'			=> 'Cache directory does not exist.'
	,'CACHE_UNABLE_CREATE'			=> 'Unable to create the cache directory.  Verify that permissions are properly set.'
	
	,'SAFE_MLD'							=> 'PHP safe_mode is currently turned on.  To use FLIR with safe_mode on, you must change CACHE_SINGLE_DIR to true.  Please read the docs at http://docs.facelift.mawhorter.net'
);

if(version_compare(PHP_VERSION, '4.3.0', '<'))
    err('PHP_TOO_OLD');
if(version_compare(PHP_VERSION, '6.0.0', '>='))
    err('PHP_UNSUPPORTED');
    
//if(false == setlocale(LC_CTYPE, USER_LOCALE.'.UTF-8'))
//	err('COULD_NOT_SETLOCALE');
	
/***
 *
 * Can be deleted if magic quotes is disabled.  Magic quotes, what a plague it is/was.
 *
*/
if (get_magic_quotes_gpc()) {
	function stripslashes_deep($value) {
		$value = is_array($value) ?
						array_map('stripslashes_deep', $value) :
						stripslashes($value); 
		
		return $value;
	}
	
	$_POST 		= array_map('stripslashes_deep', $_POST);
	$_GET 		= array_map('stripslashes_deep', $_GET);
	$_COOKIE 	= array_map('stripslashes_deep', $_COOKIE);
	$_REQUEST 	= array_map('stripslashes_deep', $_REQUEST);
}

// functions

function get_cache_fn($md5, $ext='png') {
	if(!file_exists(CACHE_DIR))
		err('CACHE_DOESNT_EXIST');
	
	if(CACHE_SINGLE_DIR)
		return CACHE_DIR.'/'.$md5.'.'.$ext;
	
	$tier1 = CACHE_DIR.'/'.$md5[0].$md5[1];
	$tier2 = $tier1.'/'.$md5[2].$md5[3];
	
	if(!file_exists($tier1))
		@mkdir($tier1);
	if(!file_exists($tier2))
		@mkdir($tier2);
	
	if(!file_exists($tier2))
		err('CACHE_UNABLE_CREATE');
	
	return $tier2.'/'.$md5.'.'.$ext;
}

function cleanup_cache() {
	$d1 = dir(CACHE_DIR);
	while(false !== ($tier1 = $d1->read())) {
		if($tier1 == '.' || $tier1 == '..') continue; 
		
		$d2 = dir(CACHE_DIR.'/'.$tier1);
		while(false !== ($tier2 = $d2->read())) {
			if($tier2 == '.' || $tier2 == '..') continue; 
			
			$path = CACHE_DIR.'/'.$tier1.'/'.$tier2;
			$d3 = dir($path);
			while(false !== ($entry = $d3->read())) {
				if($entry == '.' || $entry == '..') continue; 
			
				if((time() - filectime($path.'/'.$entry)) > CACHE_KEEP_TIME)
					unlink($path.'/'.$entry);
			}
			$d3->close();            
		}
		$d2->close();
	}
	$d1->close();
}

function imagettftextbox($size_pts, $angle, $left, $top, $color, $font, $raw_text, $max_width, $align='left', $lineheight=1.0) {
    global $FLIR;
	 
	 if($lineheight<-1) // will cause text to disappear off canvas
	 	$lineheight = 1.0;

    $raw_textlines = explode("\n", $raw_text);
    
    $formatted_lines = $formatted_widths = array();
    $max_values = bounding_box(HBOUNDS_TEXT);
    $previous_bounds = array('width' => 0);
	 $longest_line=0;

    $spaces = ' '.str_repeat(' ', (defined('SPACING_GAP')?SPACING_GAP:0));
        
    foreach($raw_textlines as $text) {        
        $bounds = bounding_box($text);
        if($bounds['height'] > $max_lineheight)
            $max_lineheight = $bounds['height'];
        if($bounds['belowBasepoint'] > $max_baseheight)
            $max_baseheight = $bounds['belowBasepoint'];
        if($bounds['xOffset'] > $max_leftoffset)
            $max_leftoffset = $bounds['xOffset'];
        if($bounds['yOffset'] > $max_rightoffset)
            $max_rightoffset = $bounds['yOffset'];

        if($bounds['width'] < $max_width) { // text doesn't require wrapping
            $formatted_lines[] = $text;
            $formatted_widths[$text] = $longest_line = $bounds['width'];
        }else { // text requires wrapping
            $words = explode($spaces, trim($text));
				$wordcnt = count($words);
            
            $test_line = '';
            for($i=0; $i < count($words); $i++) { // test words one-by-one to see if they put the width over
                $prepend = $i==0 ? '' : $test_line.$spaces; // add space if not the first word
                $working_line = $prepend.$words[$i];
                
                $bounds = bounding_box($working_line);
                
                if($bounds['width'] > $max_width) { // if working line is too big previous line isn't, use that 
							if($wordcnt==1) // cannot wrap a single word that is longer than bounding box
								return false;
								
                    $formatted_lines[] = $test_line;
                    $formatted_widths[$test_line] = $previous_bounds['width'];
                    $test_line = $words[$i];
                    
                    $bounds = bounding_box($test_line);
                }else { // keep adding
                    $test_line = $working_line;
                }
                
					if($bounds['width'] > $longest_line)
						$longest_line = $bounds['width'];
					 
                $previous_bounds = $bounds;
            }
            
            if($test_line!='') { // if words are finished and there is something left in the buffer add it
                $bounds = bounding_box($test_line);

                $formatted_lines[] = $test_line;
                $formatted_widths[$test_line] = $bounds['width'];
            }
        }
    }
    
	 $longest_line += abs($max_leftoffset);

    $max_lineheight = ($max_values['height']*$lineheight);
	 if($lineheight<0)
	 	$max_lineheight += $max_values['height'];
    $image = imagecreatetruecolor(($FStyle['notrim']=='true' ? $max_width : $longest_line)
	 						, (($max_lineheight*(count($formatted_lines)-1))+$max_values['yOffset'])+$max_values['belowBasepoint']);
    
    gd_alpha($image);
    imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), gd_bkg($image));    
    
    for($i=0; $i < count($formatted_lines); $i++) {
        if($i==0)
            $offset_top = $max_values['yOffset'];
        else
            $offset_top = ($max_lineheight*$i)+$max_values['yOffset'];

        switch(strtolower($align)) {
            default:
            case 'left':
                $offset_left = 0;
                break;
            case 'center':
                $offset_left = ($max_width-$formatted_widths[$formatted_lines[$i]])/2;
                break;
            case 'right':
                $offset_left = ($max_width-$formatted_widths[$formatted_lines[$i]])-1;
                break;
        }
        
        imagettftext($image, $size_pts, $angle, $offset_left, $offset_top, gd_color($image), $font, $formatted_lines[$i]);
        $bounds = array('xOffset' => $offset_left, 'yOffset' => $offset_top);
//        render_text($bounds, $formatted_lines[$i], $image, $bounds);
    }

    return $image;
}

function css2hex($css_str, $default_color='000000') {
    $css_color = array();
    
    $css_color['aliceblue']            = 'f0f8ff';
    $css_color['antiquewhite']            = 'faebd7';
    $css_color['aqua']            = '00ffff';
    $css_color['aquamarine']            = '7fffd4';
    $css_color['azure']            = 'f0ffff';
    $css_color['beige']            = 'f5f5dc';
    $css_color['bisque']            = 'ffe4c4';
    $css_color['black']            = '000000';
    $css_color['blanchedalmond']            = 'ffebcd';
    $css_color['blue']            = '0000ff';
    $css_color['blueviolet']            = '8a2be2';
    $css_color['brown']            = 'a52a2a';
    $css_color['burlywood']            = 'deb887';
    $css_color['cadetblue']            = '5f9ea0';
    $css_color['chartreuse']            = '7fff00';
    $css_color['chocolate']            = 'd2691e';
    $css_color['coral']            = 'ff7f50';
    $css_color['cornflowerblue']            = '6495ed';
    $css_color['cornsilk']            = 'fff8dc';
    $css_color['crimson']            = 'dc143c';
    $css_color['cyan']            = '00ffff';
    $css_color['darkblue']            = '00008b';
    $css_color['darkcyan']            = '008b8b';
    $css_color['darkgoldenrod']            = 'b8860b';
    $css_color['darkgray']            = 'a9a9a9';
    $css_color['darkgrey']            = 'a9a9a9';
    $css_color['darkgreen']            = '006400';
    $css_color['darkkhaki']            = 'bdb76b';
    $css_color['darkmagenta']            = '8b008b';
    $css_color['darkolivegreen']            = '556b2f';
    $css_color['darkorange']            = 'ff8c00';
    $css_color['darkorchid']            = '9932cc';
    $css_color['darkred']            = '8b0000';
    $css_color['darksalmon']            = 'e9967a';
    $css_color['darkseagreen']            = '8fbc8f';
    $css_color['darkslateblue']            = '483d8b';
    $css_color['darkslategray']            = '2f4f4f';
    $css_color['darkslategrey']            = '2f4f4f';
    $css_color['darkturquoise']            = '00ced1';
    $css_color['darkviolet']            = '9400d3';
    $css_color['deeppink']            = 'ff1493';
    $css_color['deepskyblue']            = '00bfff';
    $css_color['dimgray']            = '696969';
    $css_color['dimgrey']            = '696969';
    $css_color['dodgerblue']            = '1e90ff';
    $css_color['firebrick']            = 'b22222';
    $css_color['floralwhite']            = 'fffaf0';
    $css_color['forestgreen']            = '228b22';
    $css_color['fuchsia']            = 'ff00ff';
    $css_color['gainsboro']            = 'dcdcdc';
    $css_color['ghostwhite']            = 'f8f8ff';
    $css_color['gold']            = 'ffd700';
    $css_color['goldenrod']            = 'daa520';
    $css_color['gray']            = '808080';
    $css_color['grey']            = '808080';
    $css_color['green']            = '008000';
    $css_color['greenyellow']            = 'adff2f';
    $css_color['honeydew']            = 'f0fff0';
    $css_color['hotpink']            = 'ff69b4';
    $css_color['indianred']            = 'cd5c5c';
    $css_color['indigo']            = '4b0082';
    $css_color['ivory']            = 'fffff0';
    $css_color['khaki']            = 'f0e68c';
    $css_color['lavender']            = 'e6e6fa';
    $css_color['lavenderblush']            = 'fff0f5';
    $css_color['lawngreen']            = '7cfc00';
    $css_color['lemonchiffon']            = 'fffacd';
    $css_color['lightblue']            = 'add8e6';
    $css_color['lightcoral']            = 'f08080';
    $css_color['lightcyan']            = 'e0ffff';
    $css_color['lightgoldenrodyellow']            = 'fafad2';
    $css_color['lightgray']            = 'd3d3d3';
    $css_color['lightgrey']            = 'd3d3d3';
    $css_color['lightgreen']            = '90ee90';
    $css_color['lightpink']            = 'ffb6c1';
    $css_color['lightsalmon']            = 'ffa07a';
    $css_color['lightseagreen']            = '20b2aa';
    $css_color['lightskyblue']            = '87cefa';
    $css_color['lightslategray']            = '778899';
    $css_color['lightslategrey']            = '778899';
    $css_color['lightsteelblue']            = 'b0c4de';
    $css_color['lightyellow']            = 'ffffe0';
    $css_color['lime']            = '00ff00';
    $css_color['limegreen']            = '32cd32';
    $css_color['linen']            = 'faf0e6';
    $css_color['magenta']            = 'ff00ff';
    $css_color['maroon']            = '800000';
    $css_color['mediumaquamarine']            = '66cdaa';
    $css_color['mediumblue']            = '0000cd';
    $css_color['mediumorchid']            = 'ba55d3';
    $css_color['mediumpurple']            = '9370d8';
    $css_color['mediumseagreen']            = '3cb371';
    $css_color['mediumslateblue']            = '7b68ee';
    $css_color['mediumspringgreen']            = '00fa9a';
    $css_color['mediumturquoise']            = '48d1cc';
    $css_color['mediumvioletred']            = 'c71585';
    $css_color['midnightblue']            = '191970';
    $css_color['mintcream']            = 'f5fffa';
    $css_color['mistyrose']            = 'ffe4e1';
    $css_color['moccasin']            = 'ffe4b5';
    $css_color['navajowhite']            = 'ffdead';
    $css_color['navy']            = '000080';
    $css_color['oldlace']            = 'fdf5e6';
    $css_color['olive']            = '808000';
    $css_color['olivedrab']            = '6b8e23';
    $css_color['orange']            = 'ffa500';
    $css_color['orangered']            = 'ff4500';
    $css_color['orchid']            = 'da70d6';
    $css_color['palegoldenrod']            = 'eee8aa';
    $css_color['palegreen']            = '98fb98';
    $css_color['paleturquoise']            = 'afeeee';
    $css_color['palevioletred']            = 'd87093';
    $css_color['papayawhip']            = 'ffefd5';
    $css_color['peachpuff']            = 'ffdab9';
    $css_color['peru']            = 'cd853f';
    $css_color['pink']            = 'ffc0cb';
    $css_color['plum']            = 'dda0dd';
    $css_color['powderblue']            = 'b0e0e6';
    $css_color['purple']            = '800080';
    $css_color['red']            = 'ff0000';
    $css_color['rosybrown']            = 'bc8f8f';
    $css_color['royalblue']            = '4169e1';
    $css_color['saddlebrown']            = '8b4513';
    $css_color['salmon']            = 'fa8072';
    $css_color['sandybrown']            = 'f4a460';
    $css_color['seagreen']            = '2e8b57';
    $css_color['seashell']            = 'fff5ee';
    $css_color['sienna']            = 'a0522d';
    $css_color['silver']            = 'c0c0c0';
    $css_color['skyblue']            = '87ceeb';
    $css_color['slateblue']            = '6a5acd';
    $css_color['slategray']            = '708090';
    $css_color['slategrey']            = '708090';
    $css_color['snow']            = 'fffafa';
    $css_color['springgreen']            = '00ff7f';
    $css_color['steelblue']            = '4682b4';
    $css_color['tan']            = 'd2b48c';
    $css_color['teal']            = '008080';
    $css_color['thistle']            = 'd8bfd8';
    $css_color['tomato']            = 'ff6347';
    $css_color['turquoise']            = '40e0d0';
    $css_color['violet']            = 'ee82ee';
    $css_color['wheat']            = 'f5deb3';
    $css_color['white']            = 'ffffff';
    $css_color['whitesmoke']            = 'f5f5f5';
    $css_color['yellow']            = 'ffff00';
    $css_color['yellowgreen']            = '9acd32';

    $color = isset($css_color[$css_str])?$css_color[$css_str]:$default_color;
    $colors     = explode(',',substr(chunk_split($color, 2, ','), 0, -1));
    $acolor = array();
    $acolor['red']		= hexdec($colors[0]);
    $acolor['green']		= hexdec($colors[1]);
    $acolor['blue']		= hexdec($colors[2]);
    
    return $acolor;
}

function dec2hex($r, $g, $b) {
    $hxr = dechex($r);
    $hxg = dechex($g);
    $hxb = dechex($b);
    
    return strtoupper((strlen($hxr)==1?'0'.$hxr:$hxr).(strlen($hxg)==1?'0'.$hxg:$hxg).(strlen($hxb)==1?'0'.$hxb:$hxb));
}

function output_file($cache_file) {
	$ts = filemtime($cache_file);
	
	$ifmodsince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])?$_SERVER['HTTP_IF_MODIFIED_SINCE']:false;
	if ($ifmodsince && strtotime($ifmodsince) >= $ts) {
		header('Cache-Control: max-age='.CACHE_KEEP_TIME, true);
		header('HTTP/1.0 304 Not Modified', true, 304);
		return;
	}
	
	$etag = isset($_SERVER['HTTP_IF_NONE-MATCH'])?$_SERVER['HTTP_IF_NONE-MATCH']:false;
	if($etag && $etag == md5($ts)) {
		header('Cache-Control: max-age='.CACHE_KEEP_TIME, true);
		header('HTTP/1.0 304 Not Modified', true, 304);
		return;
	}
	
	header('Cache-Control: max-age='.CACHE_KEEP_TIME, true); // cache image for 7 days
	header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', $ts), true);
	header('ETag: "'.md5($ts).'"', true);
	
	switch(exif_imagetype($cache_file)) {
		case IMAGETYPE_PNG:
			header('Content-Type: image/png');
			break;
		case IMAGETYPE_GIF:
			header('Content-Type: image/gif');
			break;
	}
	readfile($cache_file);
}


function bounding_box($text, $font=NULL, $size=NULL) {
	global $FLIR;
	
	if(is_null($font))
		$font = $FLIR['font'];
	
	if(is_null($size))
		$size = $FLIR['size_pts'];
	
	return convertBoundingBox(imagettfbbox($size, 0, $font, $text));
}

/*
0  lower left corner, X position            -3
1     lower left corner, Y position            10
2     lower right corner, X position        735
3     lower right corner, Y position        10
4     upper right corner, X position        735
5     upper right corner, Y position        -44
6     upper left corner, X position            -3
7     upper left corner, Y position            -44

$width = abs($bounds[2]) + abs($bounds[0]);
$height = abs($bounds[7]) + abs($bounds[1]);
*/    
function convertBoundingBox ($bbox) {
	if ($bbox[0] >= -1)
		$xOffset = -abs($bbox[0] + 1);
	else
		$xOffset = abs($bbox[0] + 2);
	$width = abs($bbox[2] - $bbox[0]);
	if ($bbox[0] < -1) $width = abs($bbox[2]) + abs($bbox[0]) - 1;
	$yOffset = abs($bbox[5] + 1);
	if ($bbox[5] >= -1) $yOffset = -$yOffset; // Fixed characters below the baseline.
	$height = abs($bbox[7]) - abs($bbox[1]);
	if ($bbox[3] > 0) $height = abs($bbox[7] - $bbox[1]) - 1;
	return array(
		'width' => $width,
		'height' => $height,
		'xOffset' => $xOffset, // Using xCoord + xOffset with imagettftext puts the left most pixel of the text at xCoord.
		'yOffset' => $yOffset, // Using yCoord + yOffset with imagettftext puts the top most pixel of the text at yCoord.
		'belowBasepoint' => max(0, $bbox[1])
	);
}

function is_number($str, $bAllowDecimals=false, $bAllowZero=false, $bAllowNeg=false) {
	if($bAllowDecimals)
		$regex = $bAllowZero?'[0-9]+(\.[0-9]+)?': '(^([0-9]*\.[0-9]*[1-9]+[0-9]*)$)|(^([0-9]*[1-9]+[0-9]*\.[0-9]+)$)|(^([1-9]+[0-9]*)$)';
	else
		$regex = $bAllowZero?'[0-9]+': '[1-9]+[0-9]*';
	
	return preg_match('#^'.($bAllowNeg?'\-?':'').$regex.'$#', $str);
}

function is_hexcolor($str) {
	return preg_match('#^[a-f0-9]{6}$#i', $str);
}

function convert_color($color, $bHex=false, $default_color='000000') {
	$rgb = array();
	if(preg_match('#(([0-9]{1,3}),\s*([0-9]{1,3}),\s*([0-9]{1,3})(,\s*([0-9]{1,3}))?)#i', $color, $m)) {
		$rgb['red']    = $m[2];
		$rgb['green']  = $m[3];
		$rgb['blue']   = $m[4];
	}elseif(preg_match('#^[a-f0-9]{3}|[a-f0-9]{6}$#i', trim($color))) {
		if(strlen($color) == 3)
			$color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
		
		$colors			= explode(',',substr(chunk_split($color, 2, ','), 0, -1));
		$rgb['red']		= hexdec($colors[0]);
		$rgb['green']	= hexdec($colors[1]);
		$rgb['blue']	= hexdec($colors[2]);
	}else {
		$rgb = css2hex($color, $default_color);
	}
	
	return $bHex ? dec2hex($rgb['red'],$rgb['green'],$rgb['blue']) : $rgb;
}

function is_transparent($str) {
    if($str == '0' || trim($str) == '' || $str == 'transparent' || $str == 'none')
        return true;
        
    if(preg_match('#[0-9]{1,3},\s*?[0-9]{1,3},\s*?[0-9]{1,3},\s*0#i', $str, $m))
        return true;
        
    return false;
}

function get_points($dpi, $pxsize) {
    return round(((72/$dpi)*$pxsize), 3);
}

function parse_css_codes($str) {
	if(trim($str) == '') {
		$CSS = array();
	}else {
		$CSS = array_combine(array('background-color' ,'color' ,'font-family'
											,'font-size' ,'letter-spacing' ,'line-height'
											,'text-align' ,'font-stretch' ,'font-style'
											,'font-variant' ,'font-weight' ,'opacity'
											,'text-decoration')
									, explode('|', $str));
	}
								
	// convert codes back into readable
	$CSS['font-style'] = $CSS['font-style']=='1'? 'italic' : '';

	switch($CSS['font-weight']) {
		case '-1':
			$CSS['font-weight'] = 'lighter';
			break;
		case '0':
			$CSS['font-weight'] = 'normal';
			break;
		case '1':
			$CSS['font-weight'] = 'bold';
			break;
		case '2':
			$CSS['font-weight'] = 'bolder';
			break;
	}
	
	return $CSS;
}

function discover_font($default, $passed) {
    $passed_fn = strtolower(get_filename($passed));
    $ret = $default;
    $fdir = str_replace('\\', '/', (getcwd().'/'.FONTS_DIR));
    $d = dir($fdir);
    while(false !== ($entry = $d->read())) {
        if($passed_fn == strtolower(get_filename($entry))) {
            $ret = $entry;
        }
    }
    $d->close();
    
    $rp = realpath(($fdir.'/'.$ret));
    
    return (!$rp || false === strpos(str_replace('\\', '/', $rp), $fdir)) ? $default : $ret;
}

function match_font_style($font) {
    global $CSS;

    $best_match = array();
    $best_match_value = -1.0;
    foreach($font as $k => $v) {
        $stretch     = $CSS['font-stretch']=='normal'			|| $CSS['font-stretch']==''			? '' : $CSS['font-stretch'];
        $style       = $CSS['font-style']=='normal'			|| $CSS['font-style']==''  	     	? '' : $CSS['font-style'];
        $variant     = $CSS['font-variant']=='normal'			|| $CSS['font-variant']==''			? '' : $CSS['font-variant'];
        $weight      = $CSS['font-weight']=='normal'			|| $CSS['font-weight']==''				? '' : $CSS['font-weight'];
        $decoration  = $CSS['text-decoration']=='none'		|| $CSS['text-decoration']==''		? '' : $CSS['text-decoration'];
        
        $total = (
                             ($v['font-stretch']        == $stretch     ? 1 : 0) 
                        +    ($v['font-style']          == $style			? 1 : 0)
                        +    ($v['font-variant']        == $variant     ? 1 : 0)
                        +    ($v['font-weight']         == $weight		? 1 : 0)
                        +    ($v['text-decoration']     == $decoration	? 1 : 0)
                    );
        if($total>0)
            $total /= 5;
        
        if($total > $best_match_value) {
            $best_match_value = $total;
            $best_match = $v['file'];
        }
    }
    
    return $best_match;
}

function space_out($text, $spaces) {
	$ret = '';
	$spacetxt = str_repeat(' ', $spaces);
	for($i=0; $i<strlen($text); $i++)
		$ret .= $text[$i].$spacetxt;
	return rtrim($ret);
}

function verify_safemode() {
	global $ERROR_MSGS;
	
	if(file_exists(CACHE_DIR.'/sm-verified')) return;
	
	if(ini_get('safe_mode') && !CACHE_SINGLE_DIR)
		err('SM_MLD');
	
	touch(CACHE_DIR.'/sm-verified');
	return true;
}

function verify_gd() {
	global $ERROR_MSGS;
	
	if(file_exists(CACHE_DIR.'/gd-verified')) return;
	
	if(!extension_loaded('gd'))
		err('GD_NOT_INSTALLED');
	
	if(function_exists('gd_info')) {
		$gdinfo = gd_info();
		
		$errors = array();
		preg_match('/\d/', $gdinfo['GD Version'], $m);
		if($m[0]!='2')
			$errors[] = $ERROR_MSGS['GD_TOO_OLD'];            
		
		if(!$gdinfo['FreeType Support'])
			$errors[] = $ERROR_MSGS['GD_NO_FREETYPE'];            
		
		if(!$gdinfo['PNG Support'] && !$gdinfo['GIF Create Support'])
			$errors[] = $ERROR_MSGS['GD_NO_IMAGES'];
		
		if(!empty($errors)) {
			echo implode('<br>', $errors);
			exit;
		}
	}
	
	touch(CACHE_DIR.'/gd-verified');
	return true;
}

function gd_bkg($img=NULL) {
    global $FLIR;
    
    if(is_null($img))
        global $image;
    else
        $image = $img;
    
    switch($FLIR['output']) {
        case 'png':
            return imagecolorallocatealpha($image, $FLIR['bkgcolor']['red'], $FLIR['bkgcolor']['green'], $FLIR['bkgcolor']['blue'], 127);
        case 'gif':
            return imagecolorallocate($image, $FLIR['bkgcolor']['red'], $FLIR['bkgcolor']['green'], $FLIR['bkgcolor']['blue']);
    }
}

function gd_color($img=NULL) {
    global $FLIR;
    
    if(is_null($img))
        global $image;
    else
        $image = $img;
    
    $color = '';
    if($opacity != 100)
        $color = imagecolorallocatealpha($image, $FLIR['color']['red'], $FLIR['color']['green'], $FLIR['color']['blue'], round(127-(($FLIR['opacity']/100)*127)));
    else 
        $color = imagecolorallocate($image, $FLIR['color']['red'], $FLIR['color']['green'], $FLIR['color']['blue']);

    return $color;
}

function gd_alpha($img=NULL) {
    global $FLIR;
    
    if(is_null($img))
        global $image;
    else
        $image = $img;

    if($FLIR['output'] == 'png') {
        imagesavealpha($image, true);
        imagealphablending($image, false);
    }
}

function prepare_text($str) {
	return strip_tags(str_replace(array(
								 '{*A}nbsp;'
								, '{*A}'
								, '{*P}'
								, '{*LP}'
								, '{*RP}'
								, "\n"
								, "\r")
							, array(
								 ' '
								,'&'
								,'+'
								,'('
								,')'
								,''
								,'')
							, trim($str, "\t\n\r")));
}

function fix_path($str) {
    return IS_WINDOWS ? str_replace('/', '\\', $str) : str_replace('\\', '/', $str);
}

function consttrue($const) {
    return !defined($const) ? false : constant($const);
}

function err($k) {
	global $ERROR_MSGS;
	die('Facelift Error: '.(isset($ERROR_MSGS[$k]) ? $ERROR_MSGS[$k] : 'Unknown Error'));
}



// PHP Compat stuff
if(!function_exists('json_decode')) {
    // very plain json_decode
    function json_decode($str, $ignore=true) {
        $str = trim($str);
        if(!preg_match('#^\{(("[\w]+":"[^"]*",?)*)\}$#i', $str, $m)) return array();
        $data = explode('","', substr($m[1], 1, -1));
        $ret = array();
        for($i=0; $i<count($data);$i++) {
            list($k,$v) = explode(':', $data[$i], 2);
            $ret[substr($k, 0, -1)] = substr($v, 1);
        }
        
        return $ret;
    }
}

if(!function_exists('exif_imagetype')) {
// http://us3.php.net/manual/en/function.exif-imagetype.php#80383
// orig author: tom dot ghyselinck at telenet dot be
// modified a bit by me
    function exif_imagetype ( $filename ) {
        if ( ( list(,,$type,) = getimagesize( $filename ) ) !== false ) {
            return $type;
        }
        return IMAGETYPE_PNG; // meh
    }
}

if(version_compare(PHP_VERSION, '5.2.0', '<')) {    
    function get_filename($path) {
        $pathinf = pathinfo($path);
        return substr($pathinf['basename'], 0, 0-strlen('.'.$pathinf['extension']) );
    }
}else {
    function get_filename($path) {
        return pathinfo($path, PATHINFO_FILENAME);
    }
}

if(version_compare(PHP_VERSION, '5.1.2', '<')) {
    function get_hostname($url) {
        $urlinf = parse_url($path);
        return $urlinf['host'];
    }
}else {
    function get_hostname($url) {
        return parse_url($url, PHP_URL_HOST);
    }
}

if(version_compare(PHP_VERSION, '5.0.0', '<')) {
    /***
     * The following has all been taken from the http://php.net/html_entity_decode comments.
     */
    function html_entity_decode_utf8($string)
    {
         static $trans_tbl;

//        echo 'starting with: '.$string."<BR>";

         // replace numeric entities
        $string = preg_replace('~&#x0*([0-9a-f]+);~ei', 'code2utf(hexdec("\\1"))', $string);
        $string = preg_replace('~&#0*([0-9]+);~e', 'code2utf(\\1)', $string);
        
         // replace literal entities
         if (!isset($trans_tbl))
         {
              $trans_tbl = array();
             
              foreach (get_html_translation_table(HTML_ENTITIES) as $val=>$key)
                    $trans_tbl[$key] = utf8_encode($val);
         }
        
         return strtr($string, $trans_tbl);
    }
        
    function code2utf($number)
    {
        if ($number < 0)
            return FALSE;
       
        if ($number < 128)
            return chr($number);
       
        // Removing / Replacing Windows Illegals Characters
        if ($number < 160)
        {
                if ($number==128) $number=8364;
            elseif ($number==129) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==130) $number=8218;
            elseif ($number==131) $number=402;
            elseif ($number==132) $number=8222;
            elseif ($number==133) $number=8230;
            elseif ($number==134) $number=8224;
            elseif ($number==135) $number=8225;
            elseif ($number==136) $number=710;
            elseif ($number==137) $number=8240;
            elseif ($number==138) $number=352;
            elseif ($number==139) $number=8249;
            elseif ($number==140) $number=338;
            elseif ($number==141) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==142) $number=381;
            elseif ($number==143) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==144) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==145) $number=8216;
            elseif ($number==146) $number=8217;
            elseif ($number==147) $number=8220;
            elseif ($number==148) $number=8221;
            elseif ($number==149) $number=8226;
            elseif ($number==150) $number=8211;
            elseif ($number==151) $number=8212;
            elseif ($number==152) $number=732;
            elseif ($number==153) $number=8482;
            elseif ($number==154) $number=353;
            elseif ($number==155) $number=8250;
            elseif ($number==156) $number=339;
            elseif ($number==157) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==158) $number=382;
            elseif ($number==159) $number=376;
        } //if
       
        if ($number < 2048)
            return unichr(($number >> 6) + 192) . unichr(($number & 63) + 128);
        if ($number < 65536)
            return unichr(($number >> 12) + 224) . unichr((($number >> 6) & 63) + 128) . unichr(($number & 63) + 128);
        if ($number < 2097152)
            return unichr(($number >> 18) + 240) . unichr((($number >> 12) & 63) + 128) . unichr((($number >> 6) & 63) + 128) . chr(($number & 63) + 128);
       
       
        return FALSE;
    } //code2utf()    
    function unichr($c) {
//            echo 'unichr: '.$c."<BR>";

         if ($c <= 0x7F) {
              return chr($c);
         } else if ($c <= 0x7FF) {
              return chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
         } else if ($c <= 0xFFFF) {
              return chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F)
                                                    . chr(0x80 | $c & 0x3F);
         } else if ($c <= 0x10FFFF) {
              return chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F)
                                                    . chr(0x80 | $c >> 6 & 0x3F)
                                                    . chr(0x80 | $c & 0x3F);
         } else {
              return false;
         }
    }
}else {
    function html_entity_decode_utf8($text) {
        return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    }
}

function pr($arr,$bReturn=false) {
	$d  = '<pre>';
	$d .= htmlentities(print_r($arr,true), ENT_QUOTES, 'utf-8');
	$d .= '</pre>';
	
	if(!$bReturn)
		echo $d;
	else
		return $d;
}
?>