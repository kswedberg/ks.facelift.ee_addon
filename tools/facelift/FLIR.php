<?php
// version 0.1

// I am placing this file in the public domain.  Do with it what you want.  

// It'd be great if someone would extend this to actually parse CSS and traverse the DOM on the PHP side!

/***
 * If you don't want to use the Javascript files to automatically replace your elements, 
 * you can use this class in your backend to inject the necessary code to make Facelift work
 * It is basically just a PHP rewrite of the FLIRStyle object included with flir.js without 
 * the ability to inherit CSS.
 */
class FLIR
{
	var $flirPath = '';
	var $debug = false;
	var $IE6 = false;

	var $replaceBackground	= false;
	var $hoverStyle			= NULL;
	
	// options are sent along with the query string
	var $options = array();
	//these are the default settings for FLIR. If you change a default here, be sure 
	//to also change it in generate.php, otherwise you may run into problems
	var $defaults = array(
		 mode 			=> 'static' // static, wrap, progressive or name of a plugin
		,output 			=> 'png' // png, gif
		,fixBaseline 	=> false
		,hq				=> false // use high quality rendering
		,css				=> array(
									 'background-color'	=> ''
									,'color'					=> ''
									,'font-family'			=> ''
									,'font-size'			=> ''
									,'letter-spacing'		=> ''
									,'line-height'       => ''
									,'text-align'			=> ''
									,'font-stretch'		=> ''
									,'font-style'			=> ''
									,'font-variant'		=> ''
									,'font-weight'       => ''
									,'opacity'           => ''
									,'text-decoration'   => ''
								)
	);

	function FLIR($options=NULL) {
		foreach($this->defaults as $k => $v) // set defaults
			$this->options[$k] = $v;
		
		if(is_array($options)) {
			foreach($options as $k=>$v) {
				if(isset($this->$k)) {
					$this->$k = $v;
				}else {
					$this->options[$k] = $v;
				}
			}
		}	
	}
	
	// generate a url based on an object
	function URL($enc_text, $maxHeight=200, $maxWidth=800) {
		$enc_text = $this->encodeText($enc_text);
		
		$url = $this->flirPath.'generate.php?t='.$enc_text
					.'&h='.$maxHeight.'&w='.$maxWidth
					.'&c='.$this->flattenCSS()
					.'&d=96&f='.$this->serialize();
		
		if($this->debug)
			$url .= '&rand='.md5(microtime());
		
		return $url;
	}
	
	function encodeText($str) { 
		$str = urlencode(str_replace(array('&','+','(',')'), array('{*A}','{*P}','{*LP}','{*RP}'), $str));
		if($this->IE6)
			$str = urlencode($str);
		return $str;
	}
	
	function serialize() {
		$sdata = '';
		foreach($this->options as $k => $v) {
			if($k=='css' || $this->options[$k] == $this->defaults[$k]) continue;
			$sdata .= ',"'.$k+'":"'+str_replace('"', "'", $this->options[$k]).'"';
		}
		
//		return $this->encodeURIComponent('{'.substr($sdata, 1).'}');
		return '{'.substr($sdata, 1).'}';
	}
	
	function flattenCSS() {
		return implode('|', $this->options['css']);
	}
	
	function replace($text) {
		if($this->IE6 && $this->options['mode'] == 'png') {
			// ie6 png code
		}else {
			if($this->replaceBackground) {
				echo $this->replaceBkg($text);			
			}else {
				echo '<img src="'.$this->URL($text).'" alt="'.str_replace('"', '&quot;', $text).'" />';
			}
		}
	}
	
	function replaceBkg($text, $bCSSOnly=false) {
		if($bCSSOnly)
			echo 'background: url('.$this->URL($text).') no-repeat; text-indent: -9999px;';
		else
			echo '<div style="background: url('.$this->URL($text).') no-repeat; text-indent: -9999px;">'.$text.'</div>';
	}
}


?>