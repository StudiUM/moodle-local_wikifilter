<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants.
 *
 * @package   mod_wikifilter
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2021 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/wiki/locallib.php');

/**
 * Return if the plugin supports $feature.
 *
 * @param  string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function wikifilter_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_wikifilter into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param  object $moduleinstance An object from the form.
 * @param  mod_wikifilter_mod_form $mform          The form.
 * @return int The id of the newly inserted record.
 */
function wikifilter_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('wikifilter', $moduleinstance);
    $wikiid = $moduleinstance->wiki;

    // Saving wikifilter associations.
    $formdata = $mform->get_data();
    $associations = $formdata->associations;

    if (!empty($associations)) {
        wikifilter_insert_associations($id, $wikiid, $associations);
    }

    return $id;
}

/**
 * Updates an instance of the mod_wikifilter in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param  object $moduleinstance An object from the form in mod_form.php.
 * @param  mod_wikifilter_mod_form $mform          The form.
 * @return bool True if successful, false otherwise.
 */
function wikifilter_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    // Saving wikifilter associations.
    $formdata = $mform->get_data();

    $id = $moduleinstance->instance;
    $wikiid = $moduleinstance->wiki;
    $associations = $formdata->associations;

    if (empty($associations)) {
        $DB->delete_records('wikifilter_associations', array('wikifilter_id' => $id));
    } else {
        wikifilter_update_associations($id, $wikiid, $associations);
    }

    return $DB->update_record('wikifilter', $moduleinstance);
}

/**
 * Removes an instance of the mod_wikifilter from the database.
 *
 * @param  int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function wikifilter_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('wikifilter', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('wikifilter', array('id' => $id));

    return true;
}

/**
 * Insert new mod_wikifilter object associations.
 *
 * @param int $id mod_wikifilter id.
 * @param int $wikiid mod_wiki id
 * @param int $associations mod_ikifilter associations.
 * @return bool True if successful, false on failure.
 */
function wikifilter_insert_associations($id, $wikiid, $associations) {
    global $DB;

    foreach ($associations as $association) {
        $associationarray = explode('-', $association);
        $roleid = $associationarray[0];
        $tagid = $associationarray[1];

        $association = new stdClass();
        $association->role_id = $roleid;
        $association->tag_id = $tagid;
        $association->wiki_id = $wikiid;
        $association->wikifilter_id = $id;

        $DB->insert_record('wikifilter_associations', $association);
    }

    return true;
}

/**
 * Updates mod_wikifilter associations.
 *
 * @param int $id mod_wikifilter id.
 * @param int $wikiid mod_wiki id
 * @param int $associations mod_wikifilter associations.
 * @return bool True if successful, false on failure.
 */
function wikifilter_update_associations($id, $wikiid, $associations) {
    global $DB;

    $DB->delete_records('wikifilter_associations', array('wikifilter_id' => $id));
    wikifilter_insert_associations($id, $wikiid, $associations);

    return true;

}

/**
 * Returns wiki pages tags.
 *
 * @param int $wid Wiki id.
 * @param int $courseid Course id.
 * @return array
 */
function get_wiki_pages_tags($wid, $courseid) {
    global $USER, $PAGE;

    // Getting wiki by id.
    $wiki = wiki_get_wiki($wid);

    // Getting course module by id.
    $cm = get_coursemodule_from_instance('wiki', $wid, $courseid);

    // Getting current group id.
    $currentgroup = groups_get_activity_group($cm);

    // Getting current user id.
    if ($wiki->wikimode == 'individual') {
        $userid = $USER->id;
    } else {
        $userid = 0;
    }

    // Getting subwiki.
    $subwiki = wiki_get_subwiki_by_group($wiki->id, $currentgroup, $userid);

    $wikipagestags = array();
    if ($pages = wiki_get_page_list($subwiki->id)) {
        // Go through each page and get tags.
        foreach ($pages as $page) {
            $wikipagestags += core_tag_tag::get_item_tags_array('mod_wiki', 'wiki_pages', $page->id);
        }
    }

    return $wikipagestags;
}

/**
 * Check if user can view a wiki page.
 *
 * @param object $moduleinstance wikifilter object.
 * @param int $wikipageid wiki page id.
 * @return bool
 */
function can_user_view_wiki_page($moduleinstance, $wikipageid) {
    global $USER;
    $canview = false;
    if (is_siteadmin($USER->id)) {
        $canview = true;
    } else {
        $pagetagsids = array_keys(core_tag_tag::get_item_tags_array('mod_wiki', 'wiki_pages', $wikipageid));
        $coursecontext = context_course::instance($moduleinstance->course);
        $userroles = get_user_roles($coursecontext, $USER->id);
        $associations = get_wikifilter_associations($moduleinstance->id);
        foreach ($userroles as $userrole) {
            if (array_key_exists($userrole->roleid, $associations)) {
                $roletagsids = explode(',', $associations[$userrole->roleid]->role_tags);
                if (!empty(array_intersect($roletagsids, $pagetagsids))) {
                    $canview = true;
                    break;
                }
            }
        }
    }

    return $canview;
}

/**
 * Print wiki page content.
 *
 * @param object $moduleinstance wikifilter object.
 * @param object $page wiki page object.
 * @param object $context module context.
 * @param object $subwikiid subwiki ID.
 */
function print_wiki_page_content($moduleinstance, $page, $context, $subwikiid) {
    global $OUTPUT, $CFG;

    if ($page->timerendered + WIKI_REFRESH_CACHE_TIME < time()) {
        $content = wiki_refresh_cachedcontent($page);
        $page = $content['page'];
    }

    if (isset($content)) {
        $box = '';
        foreach ($content['sections'] as $s) {
            $box .= '<p>' . get_string('repeatedsection', 'wiki', $s) . '</p>';
        }

        if (!empty($box)) {
            echo $OUTPUT->box($box);
        }
    }

    $html = file_rewrite_pluginfile_urls($page->cachedcontent,
                                        'pluginfile.php',
                                        $context->id,
                                        'mod_wiki',
                                        'attachments',
                                        $subwikiid);
    $html = format_text($html, FORMAT_HTML, array('overflowdiv' => true, 'allowid' => true));
    $html = process_wiki_page_links($moduleinstance, $html);
    $html = format_wiki_page_content($context->instanceid, $html);

    echo $OUTPUT->box($html);
    echo $OUTPUT->tag_list(core_tag_tag::get_item_tags('mod_wiki', 'wiki_pages', $page->id),
            null, 'wiki-tags');

    wiki_increment_pageviews($page);
}

/**
 * Format wiki page content.
 *
 * @param int $moduleinstanceid mod_wikifilter id.
 * @param string $content wiki page html.
 * @return string
 */
function format_wiki_page_content($moduleinstanceid, $content) {
    $editlinkspattern = '/<a href=\"(edit\.php.*?)\">(.*?)<\/a>/';
    $viewlinkspattern = '/(mod\/wiki\/view\.php\?pageid=(\d))/';

    $content = preg_replace($editlinkspattern, "", $content);
    $content = preg_replace($viewlinkspattern, "mod/wikifilter/view.php?id=$moduleinstanceid&pageid=$2", $content);

    return $content;
}

/**
 * Process wiki page links.
 *
 * @param object $moduleinstance wikifilter object.
 * @param string $content wiki page html.
 * @return string
 */
function process_wiki_page_links($moduleinstance, $content) {
    $baseurl = new moodle_url('/mod/wiki/view.php', array('pageid' => 'PAGEID'));
    $baseurl = $baseurl->out(false);
    $baseurl = preg_quote($baseurl);
    $baseurl = str_replace('PAGEID', '(\d+)', $baseurl);

    if (preg_match_all("|$baseurl|", $content, $matches)) {
        $urls = $matches[0];
        $pagesids = $matches[1];

        foreach ($pagesids as $index => $pageid) {
            if (!can_user_view_wiki_page($moduleinstance, $pageid)) {
                $find = $urls[$index];
                $replace = 'inaccessiblelink';
                $content = str_replace($find, $replace, $content);
            }
        }
    }

    $pattern = '/<a href=\"inaccessiblelink\">(.*?)<\/a>/';
    $content = preg_replace($pattern, '\1', $content);

    return $content;
}

/**
 * Get wikifilter associations.
 *
 * @param int $moduleinstanceid wikifilter object.
 * @return array
 */
function get_wikifilter_associations($moduleinstanceid) {
    global $DB;

    $sql = "SELECT role_id, GROUP_CONCAT(tag_id) AS role_tags
        FROM {wikifilter_associations}
        WHERE wikifilter_id = $moduleinstanceid
        GROUP BY role_id;";

    $associations = $DB->get_records_sql($sql);
    return $associations;
}

