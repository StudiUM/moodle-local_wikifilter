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
 * Prints an instance of mod_wikifilter.
 *
 * @package   mod_wikifilter
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2021 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Wiki page ID.
$pageid = optional_param('pageid', 0, PARAM_INT);

// Getting course module instance.
$cm = get_coursemodule_from_id('wikifilter', $id);

// Checking course instance.
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);

$moduleinstance = $DB->get_record('wikifilter', array('id' => $cm->instance), '*', MUST_EXIST);
$modulecontext = context_module::instance($cm->id);

// Getting wiki instance.
$wiki = wiki_get_wiki($moduleinstance->wiki);

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

$PAGE->set_url('/mod/wikifilter/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_cm($cm);

if ($pageid) {
    $wikipages = $DB->get_records('wiki_pages', array('subwikiid' => $subwiki->id));
    $wikipage = $wikipages[$pageid];

} else {
    // Getting first page.
    $wikipage = wiki_get_first_page($subwiki->id, $wiki);
}

echo $OUTPUT->header();
if (can_user_view_wiki_page($moduleinstance, $wikipage)) {
    print_wiki_page_content($id, $wikipage, $modulecontext, $subwiki->id);
} else {
    echo $OUTPUT->render_from_template('wikifilter/permissiondenied', []);
}

echo $OUTPUT->footer();
