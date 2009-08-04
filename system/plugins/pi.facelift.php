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

        // configure defaults
        $path_to_flir = '/tools/facelift/';
        $default_font_size = '70';
        $default_color = '999999';
        $text_transform = $TMPL->fetch_param('text_transform') ? $TMPL->fetch_param('text_transform') : '';
        $width = ($TMPL->fetch_param('width')) ? 'w=' . str_replace('px', '', $TMPL->fetch_param('width')) . '&' : '';
        
         // default style sets
        $sets = array(
          'intro' => array(
            'background_color' => '',
            'color'            => '999999',
            'font_family'      => '',
            'font_size'        => '40',
            'letter_spacing'   => '',
            'line_height'      => '1.1',
            'text_align'       => '',
            'font_stretch'     => '',
            'font_style'       => '',
            'font_variant'     => '',
            'font_weight'      => '',
            'opacity'          => '',
            'text_decoration'  => '',
            'text_transform'   => '',
            'width'            => '610',
          ),
          'title' => array(
            'background_color' => '',
            'color'            => 'ffffff',
            'font_family'      => '',
            'font_size'        => '50',
            'letter_spacing'   => '',
            'line_height'      => '',
            'text_align'       => '',
            'font_stretch'     => '',
            'font_style'       => '',
            'font_variant'     => '',
            'font_weight'      => '',
            'opacity'          => '',
            'text_decoration'  => '',
            'text_transform'   => 'uppercase',
            'width'            => '210',
          ),
          'links' => array(
            'background_color' => '',
            'color'            => '0098b2',
            'font_family'      => '',
            'font_size'        => '18',
            'letter_spacing'   => '',
            'line_height'      => '',
            'text_align'       => '',
            'font_stretch'     => '',
            'font_style'       => '',
            'font_variant'     => '',
            'font_weight'      => '',
            'opacity'          => '',
            'text_decoration'  => '',
            'text_transform'   => 'uppercase',
            'width'            => '170',
          ),
        );
        $default_css = array();
        $css_params = array(
          'background_color' => ($TMPL->fetch_param('background_color')) ? str_replace('#', '', $TMPL->fetch_param('background_color')) : '',
          'color'            => ($TMPL->fetch_param('color')) ? str_replace('#', '', $TMPL->fetch_param('color')) : '',
          'font_family'      => ($TMPL->fetch_param('font_family')) ? $TMPL->fetch_param('font_family') : '',
          'font_size'        => ($TMPL->fetch_param('font_size')) ? str_replace('px', '', $TMPL->fetch_param('font_size')) : '',
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
        if ($TMPL->fetch_param('set') && array_key_exists($TMPL->fetch_param('set'), $sets)) {
        // merge sets with individual css params        
          $default_css = $sets[$TMPL->fetch_param('set')];
          $set_width = array_pop($default_css);
          $set_text_transform = array_pop($default_css);
          if (empty($width)) {
            $width = 'w=' . str_replace('px', '', $set_width) . '&';
          }
          if (empty($text_transform)) {
            $text_transform = $set_text_transform;
          }
          foreach ($css_params as $prop => $value) {
            if ($value) {
              $default_css[$prop] = $value;
            }
          }

        } else {
        // just use params
          $default_css = $css_params;
        }
        $default_css['font_size'] = $default_css['font_size'] ? $default_css['font_size'] : $default_font_size;
        $default_css['font_size'] = $default_css['font_size'] ? $default_css['font_size'] : $default_color;
        
        
        $height = ($TMPL->fetch_param('height') && $TMPL->fetch_param('height') >= $default_css['font_size']) ? str_replace('px', '', $TMPL->fetch_param('height')) : $default_css['font_size'];
        $height = 'h=' . $height . '&';
        $css = implode('|', $default_css);

        $flir = '';
        $pretext = $TMPL->tagdata;
        $text_parts = preg_split("/<br.*>/", $pretext);
        foreach ($text_parts as $text) {
          $urlText = htmlspecialchars_decode($text, ENT_QUOTES);
          $urlText = ($text_transform == 'uppercase') ? strtoupper($urlText) : $urlText;

          $urlText = urlencode($urlText);
          $altText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);
          $img = '<img src="' . $path_to_flir . 'generate.php?t=' . $urlText . '&' . $width . $height . 'c='. $css . '&d=96&f=%7B%7D" alt="' . $altText . '" />';
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