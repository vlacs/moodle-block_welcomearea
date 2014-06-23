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
 * This script shows the hisotry of the welcome areas for the given user or�
 * default user. It allows users to revert to a previous version or the default
 *
 * @package     block_welcomearea
 * @copyright   2010 VLACS
 * @author      Dave Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/weblib.php');

require_once("$CFG->dirroot/blocks/welcomearea/lib.php");

$courseid   = optional_param('courseid', 0, PARAM_INT);   // Block passes the course id so we can get some context.
$default    = optional_param('default', 0, PARAM_BOOL);   // Flag to indicate we want to edit the default welcomearea.
$set        = optional_param('set', 0, PARAM_INT);        // timestamp of the entry we want to revert to. 0 to display history.
$ownerid    = optional_param('ownerid', 0, PARAM_INT);    // id of user of message area that is being edited 
$use_default    = optional_param('use_default', 0, PARAM_INT);    // used to revert to the default

require_login();

if ($courseid == SITEID) {
    $courseid = 0;
}
if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $PAGE->set_course($course);
    $context = $PAGE->context;
} else {
    $context = context_system::instance();
    $PAGE->set_context($context);
}

$urlparams = array();
if ($courseid) {
    $urlparams['courseid'] = $courseid;
}
if ($ownerid) {
    $urlparams['ownerid'] = $ownerid;
}
if ($default) {
    $urlplarams['default'] = $default;
}
$url = new moodle_url('/block/welcomarea/edit.php', $urlparams);
$PAGE->set_url($url);

$title = get_string('historytitle', 'block_welcomearea');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_welcomearea'));
$PAGE->navbar->add($title);

echo $OUTPUT->header();

if ($set) {                                                 // if set is non-zero then we're doing a revert operation

    if ((has_capability('block/welcomearea:managedefault', $context)) ||
            (has_capability('moodle/course:update', $context) and ($USER->id == $ownerid))) {

        if (welcomearea_revert($set, $ownerid, $use_default)) {         // attempt to do a revert

            echo $OUTPUT->notification(get_string('confirmation', 'block_welcomearea'), 'notifysuccess');     // if it works, give a confirmation

        } else {

            echo $OUTPUT->notification(get_string('editerror', 'block_welcomearea'));                       // if it doesn't work, give an error message

        }

        echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id'=>$courseid)));
        echo $OUTPUT->footer();
        die;

    }

    echo $OUTPUT->notification(get_string('error', 'block_welcomearea'));       // if the user is neither a teacher or admin, give them and error
    echo $OUTPUT->continue_button(new moodle_url('/'));
    echo $OUTPUT->footer();
    die;

}

// if set is 0 then we need to view the history

if ($default and has_capability('block/welcomearea:managedefault', $context)) {       // admin?

    welcomearea_links('historydefault', $courseid);     // print the links

} else if (has_capability('block/welcomearea:managedefault', $context)) {

    welcomearea_links('historymessage', $courseid);

} else if (has_capability('moodle/course:update', $context) and ($ownerid == $USER->id)) {            // teacher?

    welcomearea_links('historymessage', $courseid);            // print the links

} else {

    echo $OUTPUT->notification(get_string('error', 'block_welcomearea'));       // if the user is neither a teacher or admin, give them and error
    echo $OUTPUT->continue_button(new moodle_url('/'));
    echo $OUTPUT->footer();
    die;

}

welcomearea_history($courseid, $ownerid);          // print the history

echo $OUTPUT->footer();

?>
