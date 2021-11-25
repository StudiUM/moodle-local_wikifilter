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
 * Display information about all the mod_wikifilter modules in the requested course.
 *
 * @package   mod_wikifilter
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2021 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT); // Course ID.
$PAGE->set_url('/mod/wikifilter/index.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    throw new moodle_exception('invalidcourseid');
}

require_login($course, true);
$PAGE->set_pagelayout('incourse');
$context = context_course::instance($course->id);

$event = \mod_wikifilter\event\course_module_instance_list_viewed::create(array('context' => $context));
$event->add_record_snapshot('course', $course);
$event->trigger();

// Get all required stringswiki.
$strwikifilters = get_string("modulenameplural", "wikifilter");
$strwikifilter = get_string("modulename", "wikifilter");

// Print the header.
$PAGE->navbar->add($strwikifilters, "index.php?id=$course->id");
$PAGE->set_title($strwikifilters);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strwikifilters);

// Get all the appropriate data.
if (!$wikifilters = get_all_instances_in_course("wikifilter", $course)) {
    notice("There are no wikifilters", "../../course/view.php?id=$course->id");
    die;
}

$usesections = course_format_uses_sections($course->format);

// Print the list of instances (your module will probably extend this).

$timenow = time();
$strname = get_string("name");
$table = new html_table();

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    $table->head = array($strsectionname, $strname);
} else {
    $table->head = array($strname);
}

foreach ($wikifilters as $wikifilter) {
    $linkcss = null;
    if (!$wikifilter->visible) {
        $linkcss = array('class' => 'dimmed');
    }
    $link = html_writer::link(new moodle_url('/mod/wikifilter/view.php', array('id' => $wikifilter->coursemodule)),
                              $wikifilter->name, $linkcss);

    if ($usesections) {
        $table->data[] = array(get_section_name($course, $wikifilter->section), $link);
    } else {
        $table->data[] = array($link);
    }
}

echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
