<?php

/*
=====================================================
 Plugin Facelift
-----------------------------------------------------
Author: Karl Swedberg for Fusionary Media
http://www.fusionary.com/
--------------------------------------------------------
You may use this Plugin for free as long as this
header remains intact.
========================================================
File: pi.facelift.php
--------------------------------------------------------
Purpose: Adds or removes beginning/ending slashes to a string.
=====================================================

*/


$plugin_info = array(
                 'pi_name'          => 'Facelift',
                 'pi_version'       => '0.5',
                 'pi_author'        => 'Karl Swedberg and Tim Kelty',
                 'pi_author_url'    => 'http://www.fusionary.com/',
                 'pi_description'   => 'Turns text into an image',
                 'pi_usage'         => Facelift::usage()
               );


class Facelift {

    var $return_data;

    

    function Facelift()
    {
        global $TMPL;                
        // fetch params
        $path_to_flir = '/tools/facelift/';
        
        $default_css = array(
          'background_color' => ($TMPL->fetch_param('background_color')) ? $TMPL->fetch_param('background_color') : '',
          'color'            => ($TMPL->fetch_param('color')) ? $TMPL->fetch_param('color') : '000000',
          'font_family'      => ($TMPL->fetch_param('font_family')) ? $TMPL->fetch_param('font_family') : '',
          'font_size'        => ($TMPL->fetch_param('font_size')) ? $TMPL->fetch_param('font_size') : '30',
          'letter_spacing'   => ($TMPL->fetch_param('letter_spacing')) ? $TMPL->fetch_param('letter_spacing') : '',
          'line_height'      => ($TMPL->fetch_param('line_height')) ? $TMPL->fetch_param('line_height') : '',
          'text_align'       => ($TMPL->fetch_param('text_align')) ? $TMPL->fetch_param('text_align') : '',
          'font_stretch'     => ($TMPL->fetch_param('font_stretch')) ? $TMPL->fetch_param('font_stretch') : '',
          'font_style'       => ($TMPL->fetch_param('font_style')) ? $TMPL->fetch_param('font_style') : '',
          'font_variant'     => ($TMPL->fetch_param('font_variant')) ? $TMPL->fetch_param('font_variant') : '',
          'font_weight'      => ($TMPL->fetch_param('font_weight')) ? $TMPL->fetch_param('font_weight') : '',
          'opacity'          => ($TMPL->fetch_param('opacity')) ? $TMPL->fetch_param('opacity') : '',
          'text_decoration'  => ($TMPL->fetch_param('text_decoration')) ? $TMPL->fetch_param('text_decoration') : '',
        );
        $height = 'h=' . $default_css['font_size'] . '&';
        $css = implode('|', $default_css);

        $flir = '';
        $pretext = $TMPL->tagdata;
        $text_parts = preg_split("/<br.*>/", $pretext);
        foreach ($text_parts as $text) {
          $urlText = urlencode($text);
          $altText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);
          $img = '<img src="' . $path_to_flir . 'generate.php?t=' . $urlText . '&' . $height . 'c='. $css . '&d=96&f=%7B%7D" alt="' . $altText . '" />';
          $flir .= '<span class="flir-replaced">' . $img . '</span>';

          // $img = '<img src="' . $path_to_flir . 'generate.php?t=' . $urlText . '&' . $height . 'c='. $css . '&d=96&f=%7B%7D" alt="" />';
          // $flir .= '<span class="flir-replaced"><span class="replace" style="height: 0">' . $altText . '</span>' . $img . '</span>';

        }
        
        $this->return_data = $flir;
    }
    // END
    
    
// ----------------------------------------
//  Plugin Usage
// ----------------------------------------

// This function describes how the plugin is used.

function usage()
{
ob_start(); 
?>
This plugin will convert text to an image using the Facelift Image Replacement script.

See /tools/facelift/config-flir.php to set up default fonts, paths, etc.

Make sure /tools/facelift/cache is writable.

Please abide by the license terms.

*** EXAMPLES ***

# Using Default Values

{exp:facelift}Hello, sir.{/exp:facelift}

Result: <span class="flir-replaced"><img src="/tools/facelift/generate.php?t=Hello%2C+sir.&h=30&c=|000000||30|||||||||&d=96&f=%7B%7D" alt="Hello, sir." /></span>

<?php
$buffer = ob_get_contents();
	
ob_end_clean(); 

return $buffer;
}
// END

}
// END CLASS


?>