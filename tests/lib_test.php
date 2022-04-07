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
 * Unit tests for mod_wikifilter lib.
 *
 * @package   mod_wikifilter
 * @category  test
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2022 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_wikifilter;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for mod_wiki lib
 *
 * @package   mod_wikifilter
 * @category  test
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2022 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {

    /**
     * Set up for every test
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test get_wiki_pages_tags.
     *
     * @return void
     */
    public function test_get_wiki_pages_tags() {

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $wiki = $this->getDataGenerator()->create_module('wiki', array('course' => $course->id, 'wikimode' => 'collaborative'));
        $context = \context_module::instance($wiki->cmid);
        $firstpage = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_first_page($wiki);

        $expectedtags = array('biologie', 'master', 'medecine', 'cours');

        foreach ($expectedtags as $tag) {
            \core_tag_tag::add_item_tag('mod_wiki', 'wiki_pages', $firstpage->id, $context, $tag);
        }

        $result = array_values(get_wiki_pages_tags($wiki->id, $course->id));

        $this->assertEquals($expectedtags, $result);

    }

    /**
     * Test can_user_view_wiki_page.
     *
     * @return void
     */
    public function test_can_user_view_wiki_page() {
        global $DB;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a wiki instance.
        $wikiinstancedata = array('course' => $course->id, 'wikimode' => 'collaborative');
        $wikiinstance = $this->getDataGenerator()->create_module('wiki', $wikiinstancedata);

        // Create the front page of the created wiki instance with tags.
        $context = \context_module::instance($wikiinstance->cmid);
        $firstpage = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_first_page($wikiinstance);
        $firstpagetags = array('biologie', 'medecine');

        foreach ($firstpagetags as $tag) {
            \core_tag_tag::add_item_tag('mod_wiki', 'wiki_pages', $firstpage->id, $context, $tag);
        }

        // Create a wikifilter instance.
        $wikifilterinstancedata = array('course' => $course->id, 'wiki' => $wikiinstance->id);
        $wikifilterinstance = $this->getDataGenerator()->create_module('wikifilter', $wikifilterinstancedata);

        // Create users.
        $student = self::getDataGenerator()->create_user();
        $teacher = self::getDataGenerator()->create_user();

        // Users enrolments.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id, 'manual');

        // Create wikifilter instance associations.
        $firstpagetagsids = array_keys(\core_tag_tag::get_item_tags_array('mod_wiki', 'wiki_pages', $firstpage->id));
        $associations = array(
            $teacherrole->id.'-'.$firstpagetagsids[0],
            $teacherrole->id.'-'.$firstpagetagsids[1]
        );

        wikifilter_insert_associations($wikifilterinstance->id, $wikiinstance->id, $associations);

        // Admin can view all wiki pages.
        $this->assertTrue(can_user_view_wiki_page($wikifilterinstance, $firstpage->id));

         // Teacher can view wiki front page.
         $this->setUser($teacher);
         $this->assertTrue(can_user_view_wiki_page($wikifilterinstance, $firstpage->id));

        // Student can't view wiki front page.
        $this->setUser($student);
        $this->assertFalse(can_user_view_wiki_page($wikifilterinstance, $firstpage->id));
    }
}
