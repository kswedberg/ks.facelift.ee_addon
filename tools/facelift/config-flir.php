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

define('ALLOWED_DOMAIN', 			false); // ex: "example.com" or "subdomain.domain.com" or ".allsubdomains.com" false means disabled

define('UNKNOWN_FONT_SIZE', 		16); // in pixels

define('CACHE_CLEANUP_FREQ', 		-1); // -1 disable, 1 everytime, 10 would be about 1 in 10 times this script runs (higher number decreases frequency)
define('CACHE_KEEP_TIME', 			604800); // 604800: 7 days, also how long the browser is told to cache file
define('CACHE_SINGLE_DIR', 		false); // don't create subdirs to store cached files (good for small sites)

define('FONT_DISCOVERY', 			false);

define('CACHE_DIR', 					'cache');
define('FONTS_DIR', 					'fonts');
define('PLUGIN_DIR',					'plugins');
define('RENDER_PLUGIN_DIR',		PLUGIN_DIR.'/render');
define('PREPROC_PLUGIN_DIR',		PLUGIN_DIR.'/pre');
define('POSTPROC_PLUGIN_DIR',		PLUGIN_DIR.'/post');

define('HBOUNDS_TEXT', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz[]{}()_'); // see http://facelift.mawhorter.net/docs/

// Each font you want to use should have an entry in the fonts array.
$fonts = array();
$fonts['dineng'] = 'DINEngschrift.otf';
$fonts['tribalbenji'] 	= 'Tribal_Font.ttf';
$fonts['illuminating'] 	= 'ArtOfIlluminating.ttf';
$fonts['konstytucyja'] 	= 'Konstytucyja_1.ttf';
$fonts['stunfilla'] 	= 'OPN_StunFillaWenkay.ttf';
$fonts['animaldings'] 	= 'Animal_Silhouette.ttf';
// The font will default to the following (put your most common font here).
$fonts['default']    = $fonts['dineng'];


// font-stretch:condensed; font-style:italic; font-variant:small-caps; font-weight: bold; text-decoration:underline
// $fonts['multiple']   = array();
// $fonts['multiple'][]   = array('file' => 'test/AGOR___.TTF');
// $fonts['multiple'][]   = array('file' => 'test/AGOpus-BoldOblique.ttf'
//                    ,'font-stretch'     => ''
//                    ,'font-style'       => 'italic'
//                    ,'font-variant'     => ''
//                    ,'font-weight'        => 'bold'
//                    ,'text-decoration'    => '');
// $fonts['multiple'][]   = array('file' => 'test/AGOB___.TTF'
//                    ,'font-stretch'     => ''
//                    ,'font-style'       => ''
//                    ,'font-variant'     => ''
//                    ,'font-weight'        => 'bold'
//                    ,'text-decoration'    => '');
// $fonts['multiple'][]   = array('file' => 'test/AGOO___.TTF'
//                    ,'font-stretch'     => ''
//                    ,'font-style'       => 'italic'
//                    ,'font-variant'     => ''
//                    ,'font-weight'        => ''
//                    ,'text-decoration'    => '');



// $fonts['berndal']    = array();
// $fonts['berndal'][]  = array('file' => 'test/Berndal/BerndalLTStd-Regular.otf');
// $fonts['berndal'][]  = array('file' => 'test/Berndal/BerndalLTStd-Italic.otf'
//                    ,'font-style'       => 'italic');
// $fonts['berndal'][]  = array('file' => 'test/Berndal/BerndalLTStd-BoldItalic.otf'
//                    ,'font-style'       => 'italic'
//                    ,'font-weight'        => 'bold');
// $fonts['berndal'][]  = array('file' => 'test/Berndal/BerndalLTStd-Bold.otf'
//                    ,'font-weight'        => 'bold');
// $fonts['berndal'][] = array('file' => 'test/Berndal/BerndalLTStd-SC.otf','font-variant' => 'small-caps');
// 
// $fonts['greeksym'] = 'i18l/greeksymb.ttf';
// $fonts['greekhead'] = 'i18l/greekfp.ttf';
// $fonts['bleed'] = 'test/Bleeding_Cowboys.ttf';
// 
// $fonts['labtop'] = 'labtop/labtop.ttf';
// $fonts['labtop_bold'] = 'labtop/labtop-bold.ttf';
// 
// 
// $fonts['graublau'] = 'grablau/GraublauWeb.otf';
// $fonts['graublau_ttf'] = array();
// $fonts['graublau_ttf'][] = array('file' => 'grablau/GraublauWeb.ttf');
// $fonts['graublau_ttf'][] = array('file' => 'grablau/GraublauWebBold.ttf'
//                                             ,'font-weight' => 'bold');



// Set replacements for "web fonts" here
// $fonts['arial'] = $fonts['helvetica'] = $fonts['sans-serif']     = $fonts['puritan'];
// $fonts['times new roman'] = $fonts['times'] = $fonts['serif']    = $fonts['bentham'];
// $fonts['courier new'] = $fonts['courier'] = $fonts['monospace']  = $fonts['geo'];

define('IM_EXEC_PATH', '/opt/local/bin/'); // Path to ImageMagick (with trailing slash).  ImageMagick is needed by some plugins, but not necessary.

//define('USER_LOCALE', 'en_US');

// pipe-separated list of processing plugins to autorun
// modify settings by including them in querystring style
define('PREPROC_AUTORUN', false);
define('POSTPROC_AUTORUN', 'supercache');

// these values should match the ones set in flir.js
// you probably don't have to touch this, true/false values must be in quotes
$Default_FLIRStyle_Values = array(
   'mode'      => 'wrap'
	,'output' 		=>	'auto'
	,'fixBaseline' => 'false'
	,'hq' 			=>	'false'
);
?>