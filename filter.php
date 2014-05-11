<?php //$Id: filter.php,v 1.20 2007/06/19 17:24:34 skodak Exp $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This program is part of Moodle - Modular Object-Oriented Dynamic      //
// Learning Environment - http://moodle.org                              //
//                                                                       //
// Copyright (C) 2012  Valery Fremaux <valery.fremaux@gmail.com>         //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

// Given XML multilinguage text, return relevant text according to
// current language:
//   - look for multilang blocks in the text.
//   - if there exists texts in the currently active language, print them.
//   - else, if there exists texts in the current parent language, print them.
//   - else, print the first language in the text.
// Please note that English texts are not used as default anymore!
//
// This version is based on original multilang filter by Gaetan Frenoy,
// rewritten by valery fremaux.
//
// Following new syntax is not compatible with old one:
//   <span lang="XX" class="multilang">one lang</span>

class filter_multilangenhanced extends moodle_text_filter {
	function filter($text, array $options = array()) {
	    global $CFG;

    	$mylang = current_language();

		// some way to force target langauge from an outside global
	    if (isset($CFG->multilangenhanced_target_language)){
	    	$mylang = $CFG->multilangenhanced_target_language;
	    }
	
	    static $parentcache;
	
	    if (!isset($parentcache)) {
	        $parentcache = array();
	    }
	    if (!array_key_exists($mylang, $parentcache)) {
	        $parentlang = get_string('parentlanguage');
	        $parentcache[$mylang] = $parentlang;
	    } else {
	        $parentlang = $parentcache[$mylang];
	    }
	    
	    $mylangs = array($mylang, $parentlang);
	
		// <vf>
		// This rewritting is stronger then original multilang filter, although probably 
		// slower : It will handle nested spans in case some content has text colouring or other
		// formatting attribute using spans. 
		// using this filter, any marked content will be processed either being alone language or not
		// this makes content handling simpler in case real multilanguage is used that will not provide
		// all language version everywhere.
	
	    if (empty($text) or is_numeric($text)) {
	        return $text;
	    }
	
	    if (empty($CFG->filter_multilang_force_old) and !empty($CFG->filter_multilang_converted)) {
	        // new syntax
	        $outbuffer = '';
	        $searchstart = '/^(.*?)<span[^>]+lang="([a-zA-Z0-9_-]+)"[^>]*>(.*)$/si';
	        $searchforward = '/^(.*?)(<span[^>]*'.'>|<\/span[^>]*'.'>)(.*)$/si';
	        
	        while(preg_match($searchstart, $text, $matches)){
	        	$outbuffer .= $matches[1];
	        	$nesting = 0;
	        	$blocklang = $matches[2];
	        	$text = $matches[3];
	        	$catchbuffer = '';
	        	$innerloop = true;
	        	while($innerloop && preg_match($searchforward, $text, $matches)){
	        		$text = $matches[3];
	        		if (strstr($matches[2], '<span') !== false){        			
	        			$catchbuffer .= $matches[1].$matches[2];
	        			$nesting++;
	        		} else {
	        			// we have all nestings
	    				$catchbuffer .= $matches[1].$matches[2];
	        			if ($nesting == 0){
	        				if (in_array($blocklang, $mylangs)){
	        					$outbuffer .= $catchbuffer;
	        				}
	    					$innerloop = false;
	        				// if not expected language, just go further
	        			}
	        			$nesting--;
	        		}
	        	}
	       	}
	        if (!empty($text)) $outbuffer .= $text;
	
	
	    } else {
	        $search1 = '/(<lang\s+language="[a-zA-Z0-9_-]*"[^>]*?'.'>.*?<\/lang\s*>)/is';
	    	$outbuffer = preg_replace_callback($search1, 'multilangenhanced_filter_lang_impl', $text);
	    }
	
		return $outbuffer;            
	}
}

/**
* for old syntax (easier to discriminate)
*
*/
function multilangenhanced_filter_lang_impl($langblock) {
    global $CFG;
    
    $mylang = current_language();

    if (isset($CFG->multilangenhanced_target_language)){
    	$mylang = $CFG->multilangenhanced_target_language;
    }

    static $parentcache;

    if (!isset($parentcache)) {
        $parentcache = array();
    }
    if (!array_key_exists($mylang, $parentcache)) {
        $parentlang = get_string('parentlanguage');
        $parentcache[$mylang] = $parentlang;
    } else {
        $parentlang = $parentcache[$mylang];
    }

    $searchtosplit = '/<lang\s+language="([a-zA-Z0-9_-]+)"[^>]*>(.*?)<\/lang\s*>/is';

    if (!preg_match_all($searchtosplit, $langblock[0], $rawlanglist)) {
        //skip malformed blocks
        return $langblock[0];
    }

    $langlist = array();
    foreach ($rawlanglist[1] as $index => $lang) {
        $lang = str_replace('-','_',strtolower($lang)); // normalize languages
        $langlist[$lang] = $rawlanglist[2][$index];
    }

    if (array_key_exists($mylang, $langlist)) {
        return $langlist[$mylang];
    } else if (array_key_exists($parentlang, $langlist)) {
        return $langlist[$parentlang];
    } else {
        return ''; // we just process a single tag for more editing flexibility.
    }
}

?>
