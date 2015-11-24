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
 *
 * @package    tool_coursesearch
 * @copyright  2013 Shashikant Vaishnav
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
/**
 * Definition course search results display form.
 *
 * @copyright  2013 Shashikant Vaishnav
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursesearch_resultsui_form extends moodleform
{
    /**
     * Define setting form.
     */
    protected function definition() {
        global $CFG, $PAGE;
        $mform    = $this->_form;
        $instance = $this->_customdata;
        $mform->addElement('text', 'search', null);
        //$mform->addRule('search', get_string('emptyqueryfield', 'tool_coursesearch'), 'required', null, 'client');
        $mform->setType('search', PARAM_TEXT);
        $mform->setDefault('search', optional_param('search', '', PARAM_TEXT));

        /*
         * SEARCH TYPE
        $types = array(
            'all' => get_string('all'),
            'course' => get_string('course'),
            'course_module' => get_string('activity'),
        );

        $plugins = get_plugin_list_with_function('mod', 'get_additional_solr_types');
        foreach ($plugins as $component => $function) {
            $types += $function();
        }

        $select = $mform->addElement('select', 'type', 'Type', $types);
        $select->setSelected(optional_param('type', '', PARAM_TEXT));
        */

        $this->add_action_buttons(false, get_string('search', 'tool_coursesearch'));
    }
}