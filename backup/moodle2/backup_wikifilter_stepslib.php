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
 * Define all the backup steps that will be used by the backup_syllabus_activity_task
 *
 * @package   mod_wikifilter
 * @category  backup
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2022 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the backup steps that will be used by the backup_wikifilter_activity_task
 *
 * @package   mod_wikifilter
 * @category  backup
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2022 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_wikifilter_activity_structure_step extends backup_activity_structure_step {
    /**
     * Function that will return the structure to be processed by this restore_step.
     * Must return one array of @restore_path_element elements
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $wikifilter = new backup_nested_element('wikifilter', array('id'), array('course', 'wiki', 'name',
            'timecreated', 'timemodified', 'intro', 'introformat'));

        $associations = new backup_nested_element('associations');

        $association = new backup_nested_element('association', array('id'), array('role_id', 'tag_id', 'wiki_id',
            'wikifilter_id'));

        // Build the tree.
        $wikifilter->add_child($associations);
        $associations->add_child($association);

        // Define sources.
        $wikifilter->set_source_table('wikifilter', array('id' => backup::VAR_ACTIVITYID));
        $association->set_source_table('wikifilter_associations', array('wikifilter_id' => backup::VAR_PARENTID));

        // Define file annotations.
        $wikifilter->annotate_files('mod_wikifilter', 'intro', null); // This file area hasn't itemid.

        // Return the root element (wikifilter), wrapped into standard activity structure.
        return $this->prepare_activity_structure($wikifilter);
    }

}
