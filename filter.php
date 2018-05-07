<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Given XML multilinguage text, return relevant text according to
 * current language:
 *   - look for multilang blocks in the text.
 *   - if there exists texts in the currently active language, print them.
 *   - else, if there exists texts in the current parent language, print them.
 *   - else, print the first language in the text.
 * Please note that English texts are not used as default anymore!
 *
 * This version is based on original multilang filter by Gaetan Frenoy,
 * rewritten by valery fremaux.
 *
 * Following new syntax is not compatible with old one:
 *   <span lang="XX" class="multilang">one lang</span>
 *
 * @package     filter_multilangenhanced
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @category    filter
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class filter_multilangenhanced extends moodle_text_filter {

    public function filter($text, array $options = array()) {
        global $CFG;

        $config = get_config('filter_multilangenhanced');
        $mylang = current_language();

        // Some way to force target language from an outside global.
        // This is NOT a true setting.
        if (isset($CFG->filter_multilangenhanced_target_language)) {
            $mylang = $CFG->filter_multilangenhanced_target_language;
        }

        static $parentcache;

        if (!isset($parentcache)) {
            $parentcache = array();
        }

        if (!array_key_exists($mylang, $parentcache)) {
            $parentlang = get_string('parentlanguage', 'filter_multilangenhanced');
            $parentcache[$mylang] = $parentlang;
        } else {
            $parentlang = $parentcache[$mylang];
        }

        $mylangs = array($mylang, $parentlang);

        /*
         * This rewritting is stronger then original multilang filter, although probably
         * slower : It will handle nested spans in case some content has text colouring or other
         * formatting attribute using spans.
         * using this filter, any marked content will be processed either being alone language or not
         * this makes content handling simpler in case real multilanguage is used that will not provide
         * all language version everywhere.
         */

        if (empty($text) || is_numeric($text)) {
            return $text;
        }

        if (!empty($config->replaceglobals)) {
            $text = $this->replace_globals($text);
        }

        $input = $text;

        // Reuse the original setting !
        if (empty($CFG->filter_multilang_force_old) and !empty($CFG->filter_multilang_converted)) {

            // New syntax.
            $outbuffer = '';
            $searchstart = '/^(.*?)<(span|div)[^>]+lang="([a-zA-Z0-9_-]+)"[^>]*>(.*)$/si';
            $searchforwardpattern = '/^(.*?)(<{$langtag}[^>]*'.'>|<\/{$langtag}[^>]*'.'>)(.*)$/si';

            /* Let describe seach forward automaton
             * status variable : $nesting :
             * 0 = not started
             * 1 = found lang span
             * 2 and upper = found non lang span (waiting for closing span)
             */

            while (preg_match($searchstart, $text, $matches)) {
                $outbuffer .= $matches[1];
                $nesting = 1;
                $langtag = $matches[2]; // Now we know the lang switch uses div or span.
                $searchforward = str_replace('{$langtag}', $langtag, $searchforwardpattern);
                $blocklang = $matches[3];
                $langmatch = in_array($blocklang, $mylangs);
                $text = $matches[4];
                $catchbuffer = '';
                $innerloop = true;
                while ($innerloop && preg_match($searchforward, $text, $matches)) {
                    $pretag = $matches[1];
                    $tag = $matches[2];
                    $text = $matches[3];
                    if (strstr($tag, '<'.$langtag) !== false) {
                        // A new span or div opens, aggegate text and nest it deeper.
                        if ($langmatch) {
                            $catchbuffer .= $pretag.$tag;
                        }
                        $nesting++;
                    } else {
                        // A closing span is detected. (all other cases, but most expected is </span or /div.
                        if ($langmatch) {
                            $catchbuffer .= $pretag;
                        }
                        if ($nesting == 1) {
                            // We have all nestings this closing span closes the lang span.
                            if ($langmatch) {
                                $outbuffer .= $catchbuffer;
                                $catchbuffer = ''; // Clear catch buffer by security.
                            }
                            $innerloop = false;
                            // If not expected language, just go further.
                        } else {
                            // This is any inner closing tag. Take it.
                            if ($langmatch) {
                                $catchbuffer .= $tag;
                            }
                        }
                        $nesting--;
                    }
                }
                $outbuffer .= $catchbuffer;
            }
            if (!empty($text)) {
                $outbuffer .= $text;
            }
        } else {
            $search1 = '/(<lang\s+language="[a-zA-Z0-9_-]*"[^>]*?'.'>.*?<\/lang\s*>)/is';
            $outbuffer = preg_replace_callback($search1, 'multilangenhanced_filter_lang_impl', $text);
        }

        return $outbuffer;
    }

    protected function replace_globals($text) {
        global $CFG, $COURSE, $SITE, $USER, $DB;

        $text = str_replace('%CID%', $COURSE->id, $text);
        $text = str_replace('%CIDNUMBER%', $COURSE->idnumber, $text);
        $text = str_replace('%CSHORTNAME%', $COURSE->shortname, $text);
        $text = str_replace('%CFULLNAME%', $COURSE->fullname, $text);
        $text = str_replace('%UID%', @$USER->id, $text);
        $text = str_replace('%UUSERNAME%', @$USER->username, $text);
        $text = str_replace('%UFIRSTNAME%', @$USER->firstname, $text);
        $text = str_replace('%ULASTNAME%', @$USER->lastname, $text);
        $text = str_replace('%UIDNUMBER%', @$USER->idnumber, $text);
        $text = str_replace('%SNAME%', $SITE->fullname, $text);
        $text = str_replace('%SSHORTNAME%', $SITE->shortname, $text);
        $text = str_replace('%WWWROOT%', $CFG->wwwroot, $text);

        if ($groupid = groups_get_course_group($COURSE)) {
            $group = $DB->get_record('groups', array('id' => $groupid));
            $text = str_replace('%GID%', $group->id, $text);
            $text = str_replace('%GNAME%', $group->name, $text);
        }

        return $text;
    }

}

/**
 * for old syntax (easier to discriminate)
 *
 */
function multilangenhanced_filter_lang_impl($langblock) {
    global $CFG;

    $mylang = current_language();

    if (isset($CFG->multilangenhanced_target_language)) {
        $mylang = $CFG->multilangenhanced_target_language;
    }

    static $parentcache;

    if (!isset($parentcache)) {
        $parentcache = array();
    }
    if (!array_key_exists($mylang, $parentcache)) {
        $parentlang = get_parent_language($mylang);
        $parentcache[$mylang] = $parentlang;
    } else {
        $parentlang = $parentcache[$mylang];
    }

    $searchtosplit = '/<lang\s+language="([a-zA-Z0-9_-]+)"[^>]*>(.*?)<\/lang\s*>/is';

    if (!preg_match_all($searchtosplit, $langblock[0], $rawlanglist)) {
        // Skip malformed blocks.
        return $langblock[0];
    }

    $langlist = array();
    foreach ($rawlanglist[1] as $index => $lang) {
        $lang = str_replace('-', '_', strtolower($lang)); // Normalize languages.
        $langlist[$lang] = $rawlanglist[2][$index];
    }

    if (array_key_exists($mylang, $langlist)) {
        return $langlist[$mylang];
    } else if (array_key_exists($parentlang, $langlist)) {
        return $langlist[$parentlang];
    } else {
        return ''; // We just process a single tag for more editing flexibility.
    }
}
