<?php
// Wordpress likes to change what you type into something else... this includes changing single quotes to apostrophes and more... who cares if it is "correct", it is annoying when anything changes what you type!  Remove smart quotes WP!!

// This list is definitely not exhaustive, but it should help

$TRANSLATE = array(
	 '“' => '"'
	,'”' => '"'
	,'‘' => "'"
	,'’' => "'"
	,'—' => '--' // em
	,'–' => '-' // en
	,'…' => '...' // ellipsis
);

$FLIR['text'] = str_replace(array_keys($TRANSLATE), $TRANSLATE, $FLIR['text']);
?>