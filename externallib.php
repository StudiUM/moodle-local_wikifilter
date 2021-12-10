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
 * Wikifilter webservice functions
 *
 * @package   mod_wikifilter
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2021 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/mod/wiki/locallib.php');

use external_api;
use core_tag_tag;
use external_function_parameters;
use external_value;

/**
 * This is the external API for this report.
 *
 * @package   mod_wikifilter
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2021 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wikifilter_external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_wiki_pages_tags_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id'))
            );
    }

    /**
     * Returns wiki pages tags.
     *
     * @param int $id Wiki id.
     * @return array
     */
    public static function get_wiki_pages_tags($id) {

        $params = self::validate_parameters(self::get_wiki_pages_tags_parameters(), array('id' => $id));

        $wikipagestags = array();
        if ($pages = wiki_get_page_list($params['id'])) {
            // Go through each page and get tags.
            foreach ($pages as $page) {
                $wikipagestags += core_tag_tag::get_item_tags_array('mod_wiki', 'wiki_pages', $page->id);
            }
        }

        return json_encode($wikipagestags);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_wiki_pages_tags_returns() {
        return new external_value(PARAM_RAW, 'wiki pages tags');
    }
}
