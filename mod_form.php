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
 * The main mod_wikifilter configuration form.
 *
 * @package   mod_wikifilter
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2021 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package   mod_wikifilter
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2021 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wikifilter_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $PAGE, $OUTPUT, $DB;

        // Adding js and css files.
        $PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'));
        $PAGE->requires->css(new moodle_url('/mod/wikifilter/assets/wikifilter.css'));
        $PAGE->requires->js_call_amd('mod_wikifilter/wikifilter', 'init');

        $mform = $this->_form;
        $required = get_string('required');

        $currentcourse = $this->current->course;

        // Get course roles.
        if (!$course = $DB->get_record('course', array('id' => $currentcourse))) {
            new moodle_exception('invalidcourseid');
        }
        $coursecontext = context_course::instance($currentcourse);
        $roles = get_assignable_roles($coursecontext, ROLENAME_BOTH);

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('wikifiltername', 'wikifilter'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', $required, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        // Adding the optional "intro" and "introformat" pair of fields.
        $this->standard_intro_elements(get_string('wikifilterintro', 'wikifilter'));

        // Adding wiki fieldset.
        $mform->addElement('header', 'wiki_header', get_string('wiki', 'wikifilter'));

        // Get wiki pages tags.
        $wikis = get_all_instances_in_course("wiki", $course);
        if (!empty($wikis)) {
            $wikioptions = array();
            foreach ($wikis as $wiki) {
                $wikioptions[$wiki->id] = $wiki->name;
            }

            $currentwiki = $wikis[0]->id;
            if ($this->current->instance) {
                $currentwiki = $this->current->wiki;
            }

            // Adding wiki field.
            $mform->addElement('select', 'wiki', null, $wikioptions);

        } else {
            $nowikimessage = get_string('nowikiincourse', 'wikifilter');
            $mform->addElement('html', $OUTPUT->render_from_template('wikifilter/nowiki', ['message' => $nowikimessage] ));

            $mform->addElement('text', 'nowiki', '', ['class' => 'hidden']);
            $mform->setType('wiki', PARAM_RAW);
            $mform->addRule('nowiki', $required, 'required', null, 'client');
        }

        if (!empty($wikis)) {
            // Adding associations fieldset.
            $mform->addElement('header', 'associations_header', get_string('associations', 'wikifilter'));

            $wikipagestags = get_wiki_pages_tags($currentwiki);

            // Adding associations table.
            $associationstable  = array('roles' => array(), 'hasroles' => false);
            if ($this->current->instance) {
                $currentinstance = $this->current->id;

                $sql = "SELECT role_id, GROUP_CONCAT(tag_id) AS role_tags
                FROM mdl_wikifilter_associations
                WHERE wikifilter_id = $currentinstance
                GROUP BY role_id;";

                $associations = $DB->get_records_sql($sql);
                $associationstable['hasroles'] = !empty($associations);
                foreach ($associations as $association) {
                    $roletagsarray = array();
                    $tagsids = explode(',', $association->role_tags);
                    foreach ($tagsids as $tagid) {
                        array_push($roletagsarray, array('tagid' => $tagid, 'tagname' => $wikipagestags[$tagid]));
                    }

                    $associationstable['roles'][] = array('roleid' => $association->role_id,
                        'rolename' => $roles[$association->role_id],
                        'tags' => $roletagsarray
                    );
                }
            }

            $mform->addElement('html', $OUTPUT->render_from_template('wikifilter/associations_list', $associationstable));
            $mform->addElement('hidden', 'associations', '');
            $mform->setType('associations', PARAM_RAW);

            // Adding association editor modal.
            $mform->addElement('html', $OUTPUT->render_from_template('wikifilter/association_editor_modal/header', []));
            $mform->addElement('html', '<div class="modal-body">');

            // Adding role field in modal.
            $mform->addElement('select', 'role', get_string('role', 'wikifilter'), $roles);

            $options = array(
                'multiple' => true,
                'noselectionstring' => get_string('selectionarea', 'wikifilter'),
            );

            // Adding tags field in modal.
            $mform->addElement('autocomplete', 'wikitags', get_string('tags', 'wikifilter'), $wikipagestags, $options);

            $mform->addElement('html', '<p class="select-tag-error hidden">'.get_string('selecttagerror', 'wikifilter').'</p>');
            $mform->addElement('html', '</div>');
            $mform->addElement('html', $OUTPUT->render_from_template('wikifilter/association_editor_modal/footer', []));

        }

        // Adding standard elements.
        $this->standard_coursemodule_elements();

        // Adding standard buttons.
        $this->add_action_buttons();
    }
}
