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
 * Course search plugin event handler definition.
 *
 * @package tool_coursesearch
 * @category admin tool
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* List of handlers */
require_once(dirname(__FILE__) . '/../../../../config.php');
global $CFG;
$handlers = array(
    'course_created' => array(
        'handlerfile' => "/$CFG->admin/tool/coursesearch/locallib.php",
        'handlerfunction' => 'tool_coursesearch_course_created_handler',
        'schedule' => 'instant',
        'internal' => 1
    ),
    'course_updated' => array(
        'handlerfile' => "/$CFG->admin/tool/coursesearch/locallib.php",
        'handlerfunction' => 'tool_coursesearch_course_updated_handler',
        'schedule' => 'instant',
        'internal' => 1
    ),
    'course_deleted' => array(
        'handlerfile' => "/$CFG->admin/tool/coursesearch/locallib.php",
        'handlerfunction' => 'tool_coursesearch_course_deleted_handler',
        'schedule' => 'instant',
        'internal' => 1
    )
);

$observers = array(
    array(
        'eventname' => '\core\event\course_module_created',
        'callback' => '\tool_coursesearch\coursemodule_observers::created_handler',
        'internal' => true
    ),
    array(
        'eventname' => '\core\event\course_module_updated',
        'callback' => '\tool_coursesearch\coursemodule_observers::updated_handler',
        'internal' => true
    ),
    array(
        'eventname' => '\core\event\course_module_deleted',
        'callback' => '\tool_coursesearch\coursemodule_observers::deleted_handler',
        'internal' => true
    ),
    array(
        'eventname' => '\mod_forum\event\post_created',
        'callback' => '\tool_coursesearch\forum_observers::post_created_handler',
        'internal' => true
    ),
    array(
        'eventname' => '\mod_forum\event\post_updated',
        'callback' => '\tool_coursesearch\forum_observers::post_updated_handler',
        'internal' => true
    ),
    array(
        'eventname' => '\mod_forum\event\post_deleted',
        'callback' => '\tool_coursesearch\forum_observers::post_deleted_handler',
        'internal' => true
    ),
    array(
        'eventname' => '\mod_forum\event\discussion_created',
        'callback' => '\tool_coursesearch\forum_observers::discussion_created_handler',
        'internal' => true
    ),

    array(
        'eventname' => '\mod_forum\event\discussion_deleted',
        'callback' => '\tool_coursesearch\forum_observers::discussion_deleted_handler',
        'internal' => true
    )
);
// Discussion updated is handled by post updated