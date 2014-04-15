<?php
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	$toString = false;
	foreach (debug_backtrace() as $backtrace)
		if($backtrace['function'] == '__toString')
			$toString = true;
		
	if($toString) die("<b>Error:</b> <span style=\"color: #cc0000;\">$errstr</span> <b>in</b> $errfile <b>line</b> $errline");
	else throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

set_error_handler("exception_error_handler");

function gx_force_var($var, $as = 'string'){
	$array = array();
	$string = '';
	$object = new stdClass();

	if(is_array($var)){
		if($as == 'array') return $var;
		$string = implode(',', $var);
		$object = (object)$var;
	} else if(is_string($var)){
		if($as == 'string') return $var;
		$array = preg_split('[;,]', $var, -1, PREG_SPLIT_NO_EMPTY);
		$object = (object)$array;
	} else if(is_object($var)){
		if($as == 'object') return $var;
		$array = get_object_vars($var);
		$string = implode(',', $array);
	}
	
	switch ($as){
		case 'string': return $string; break;
		case 'array': return $array; break;
		case 'object': return $object; break;
		default: return $var; break;
	}
}

function extend(&$array, $defaults){
    $arr_is_obj = is_object($array);
    $def_is_obj = is_object($defaults);
    $array = (array)$array;
    $defaults = (array)$defaults;
    
    foreach($defaults as $key => $value){
		if(is_array($value)) extend($array[$key], $value);
		else if(!isset($array[$key])) $array[$key] = $value;
    }
    if($arr_is_obj || $def_is_obj) $array = (object)$array;
}

function setValue(&$var, $value, $validValues = null){
    if(!is_null($validValues)){
	makeArray($validValues);
	if(in_array($value, $validValues))
	    $var = $value;
	else
	    $var = $validValues[0];
    } else
	$var = $value;
}

function makeArray(&$var, $index = null){
    $var = is_array($var)? $var : (
	is_null($index)? array($var): array($index=>$var)
    );
}

function in_multiarray($needle, $haystacks) {
    foreach ($haystacks as $haystack)
        if (in_array($needle, $haystack))
            return true;
    return false;
}

function getContent($url, $data = null){
    $ch = curl_init();
    if(!is_null($data))
	curl_setopt($ch, CURLOPT_POSTFIELDS, 	$data);
    curl_setopt($ch, CURLOPT_URL, 		$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 	1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 	5);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function roundBy($number, $by = 15){
    $floor = $number - ($number % $by);
    $ceil = $number + ($by - ($number % $by));
    return $ceil-$number < $number-$floor? $ceil: $floor;
}

function boolToStr($value){
    if(!is_bool($value)) return 'false';
    return $value? 'true': 'false';
}

/**
 * Truncates text.
 *
 * Cuts a string to the length of $length and replaces the last characters
 * with the ending if the text is longer than length.
 *
 * @param string  $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param string  $ending Ending to be appended to the trimmed string.
 * @param boolean $exact If true, $text will not be cut mid-word
 * @param boolean $considerHtml If true, HTML tags would be handled correctly
 * @return string Trimmed string.
 */
function str_truncate($text, $length = 100, $ending = '...', $exact = true, $considerHtml = false) {
    if ($considerHtml) {
	// if the plain text is shorter than the maximum length, return the whole text
	if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
		return $text;
	}
	// splits all html-tags to scanable lines
	preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
	$total_length = strlen($ending);
	$open_tags = array();
	$truncate = '';
	foreach ($lines as $line_matchings) {
	    // if there is any html-tag in this line, handle it and add it (uncounted) to the output
	    if (!empty($line_matchings[1])) {
		// if it's an "empty element" with or without xhtml-conform closing slash (f.e. <br/>)
		if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
		    // do nothing
		    // if tag is a closing tag (f.e. </b>)
		} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
		    // delete tag from $open_tags list
		    $pos = array_search($tag_matchings[1], $open_tags);
		    if ($pos !== false) {
			unset($open_tags[$pos]);
		    }
		// if tag is an opening tag (f.e. <b>)
		} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
		    // add tag to the beginning of $open_tags list
		    array_unshift($open_tags, strtolower($tag_matchings[1]));
		}
		// add html-tag to $truncate'd text
		$truncate .= $line_matchings[1];
	    }
	    // calculate the length of the plain text part of the line; handle entities as one character
	    $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
	    if ($total_length+$content_length> $length) {
		// the number of characters which are left
		$left = $length - $total_length;
		$entities_length = 0;
		// search for html entities
		if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
		    // calculate the real length of all entities in the legal range
		    foreach ($entities[0] as $entity) {
			if ($entity[1]+1-$entities_length <= $left) {
			    $left--;
			    $entities_length += strlen($entity[0]);
			} else {
			    // no more characters left
			    break;
			}
		    }
		}
		$truncate .= substr($line_matchings[2], 0, $left+$entities_length);
		// maximum lenght is reached, so get off the loop
		break;
	    } else {
		$truncate .= $line_matchings[2];
		$total_length += $content_length;
	    }
	    // if the maximum length is reached, get off the loop
	    if($total_length>= $length) {
		break;
	    }
	}
    } else {
	if (strlen($text) <= $length)
	    return $text;
	else
	    $truncate = substr($text, 0, $length - strlen($ending));
    }
    // if the words shouldn't be cut in the middle...
    if ($exact) {
	// ...search the last occurance of a space...
	$spacepos = strrpos($truncate, ' ');
	if (isset($spacepos)) {
	    // ...and cut the text in this position
	    $truncate = substr($truncate, 0, $spacepos);
	}
    }
    // add the defined ending to the text
    $truncate .= $ending;
    if($considerHtml) {
	// close all unclosed html-tags
	foreach ($open_tags as $tag) 
	    $truncate .= '</' . $tag . '>';
    }
    return $truncate;
}

//taken from wordpress
function utf8_uri_encode( $utf8_string, $length = 0 ) {
    $unicode = '';
    $values = array();
    $num_octets = 1;
    $unicode_length = 0;

    $string_length = strlen( $utf8_string );
    for ($i = 0; $i < $string_length; $i++ ) {

        $value = ord( $utf8_string[ $i ] );

        if ( $value < 128 ) {
            if ( $length && ( $unicode_length >= $length ) )
                break;
            $unicode .= chr($value);
            $unicode_length++;
        } else {
            if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;

            $values[] = $value;

            if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
                break;
            if ( count( $values ) == $num_octets ) {
                if ($num_octets == 3) {
                    $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
                    $unicode_length += 9;
                } else {
                    $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
                    $unicode_length += 6;
                }

                $values = array();
                $num_octets = 1;
            }
        }
    }

    return $unicode;
}

//taken from wordpress
function seems_utf8($str) {
    $length = strlen($str);
    for ($i=0; $i < $length; $i++) {
        $c = ord($str[$i]);
        if ($c < 0x80) $n = 0; # 0bbbbbbb
        elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
        elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
        elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
        elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
        elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
        else return false; # Does not match any model
        for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
            if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                return false;
        }
    }
    return true;
}

//function sanitize_title_with_dashes taken from wordpress
function sanitize($title, $length = 200) {
    $title = strip_tags($title);
    // Preserve escaped octets.
    $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
    // Remove percent signs that are not part of an octet.
    $title = str_replace('%', '', $title);
    // Restore octets.
    $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

    if (seems_utf8($title)) {
        if (function_exists('mb_strtolower')) {
            $title = mb_strtolower($title, 'UTF-8');
        }
        $title = utf8_uri_encode($title, $length);
    }

    $title = strtolower($title);
    $title = preg_replace('/&.+?;/', '', $title); // kill entities
    $title = str_replace('.', '-', $title);
    $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
    $title = preg_replace('/\s+/', '-', $title);
    $title = preg_replace('|-+|', '-', $title);
    $title = trim($title, '-');

    return $title;
}

function jsonsql_object_var($var, $value){
	if(is_string($value)) $value = '"'.$value.'"';
	else if(is_bool($value)) $value = $value? 1: 0;
	return 'LIKE \'%"'.$var.'":'.$value.'%\'';
}
function jsonsql_in_array($array, $value){
	$value = preg_match('/[0-9]+/', $value)? '"?'.$value.'"?': '"'.$value.'"';
	return 'REGEXP \'"'.$array.'":[[.left-square-bracket.]]([^]]+)?'.$value.'([^]]+)?[[.right-square-bracket.]]\'';
}



function debug($expression, $exit = true){
    var_dump($expression);
    if($exit) exit();
}
?>