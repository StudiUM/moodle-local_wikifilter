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
 * This file keeps track of upgrades to the wiki module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * @package   mod_wikifilter
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2021 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Main upgrade tasks to be executed on Moodle version bump
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_wikifilter_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021112504) {
        // Define field wiki to be added to wikifilter.
        $table = new xmldb_table('wikifilter');
        $field = new xmldb_field('wiki', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $key = new xmldb_key('fk_wiki', XMLDB_KEY_FOREIGN, ['wiki'], 'wiki', ['id']);

        // Conditionally launch add field wiki.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Adding key fk_wiki to table wikifilter.
        $dbman->add_key($table, $key);

        // Define table wikifilter_associations to be created.
        $table = new xmldb_table('wikifilter_associations');

        // Adding fields to table wikifilter_associations.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('role_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tag_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('wikifilter_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table wikifilter_associations.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_wikifilter', XMLDB_KEY_FOREIGN, ['wikifilter_id'], 'wikifilter', ['id']);

        // Conditionally launch create table for customfield_category.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Wikifilter savepoint reached.
        upgrade_mod_savepoint(true, 2021112504, 'wikifilter');

    }

    if ($oldversion < 2021112507) {
        // Define field wiki to be added to wikifilter.
        $table = new xmldb_table('wikifilter');
        $field = new xmldb_field('wiki', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'course');
        $key = new xmldb_key('fk_wiki', XMLDB_KEY_FOREIGN, ['wiki'], 'wiki', ['id']);

        // Dropping key fk_wiki to table wikifilter.
        $dbman->drop_key($table, $key);

         // Changing the default of wiki field.
         $field = new xmldb_field('wiki', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'course');
         $dbman->change_field_default($table, $field);

         // Dropping key fk_wiki to table wikifilter.
         $dbman->add_key($table, $key);

        // Define field wiki to be added to wikifilter_associations.
        $table = new xmldb_table('wikifilter_associations');
        $field = new xmldb_field('wiki_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'tag_id');
        $key = new xmldb_key('fk_wiki', XMLDB_KEY_FOREIGN, ['wiki_id'], 'wiki', ['id']);

        // Conditionally launch add field wiki.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Changing the default of wiki_id field.
        $field = new xmldb_field('wiki_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'tag_id');
        $dbman->change_field_default($table, $field);

        // Adding key fk_wiki to table wikifilter.
        $dbman->add_key($table, $key);

        // Wikifilter savepoint reached.
        upgrade_mod_savepoint(true, 2021112507, 'wikifilter');

    }

    return true;

}
