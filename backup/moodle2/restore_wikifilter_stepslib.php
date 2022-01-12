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
 * Defines backup_wiki_activity_task class
 *
 * @package   mod_wikifilter
 * @category  backup
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2022 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one syllabus activity.
 *
 * @package   mod_wikifilter
 * @category  backup
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2022 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_wikifilter_activity_structure_step extends restore_activity_structure_step {
    /**
     * Function that will return the structure to be processed by this restore_step.
     * Must return one array of @restore_path_element elements.
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('wikifilter', '/activity/wikifilter');
        $paths[] = new restore_path_element('wikifilter_associations', '/activity/wikifilter/associations/association');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process wikifilter.
     *
     * @param stdClass $data
     */
    protected function process_wikifilter($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();

        // Insert the wiki record.
        $newitemid = $DB->insert_record('wikifilter', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process wikifilter association.
     *
     * @param stdClass $data
     */
    protected function process_wikifilter_associations($data) {
        global $DB;

        $data = (object)$data;

        $data->wikifilter_id = $this->get_new_parentid('wikifilter');

        $newitemid = $DB->insert_record('wikifilter_associations', $data);

    }

    /**
     * After execute function.
     */
    protected function after_execute() {
        // Add wikifilter related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_wikifilter', 'intro', null);
    }
}
