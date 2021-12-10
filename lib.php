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
 * insert new mod_wikifilter object associations.
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
 * updates mod_wikifilter associations.
 *
 * @param int $id mod_ikifilter id.
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
 * @param int $id Wiki id.
 * @return array
 */
function get_wiki_pages_tags($id) {
    $wikipagestags = array();
    if ($pages = wiki_get_page_list($id)) {
        // Go through each page and get tags.
        foreach ($pages as $page) {
            $wikipagestags += core_tag_tag::get_item_tags_array('mod_wiki', 'wiki_pages', $page->id);
        }
    }

    return $wikipagestags;
}
