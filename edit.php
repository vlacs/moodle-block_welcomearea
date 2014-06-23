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
 * This script allows users to edit the welcome block. Admins use this page to�
 * edit the default block also.
 *
 * @package     block_welcomearea
 * @copyright   2010 VLACS
 * @author      Dave Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/weblib.php');

require_once("$CFG->dirroot/blocks/welcomearea/form.php");
require_once("$CFG->dirroot/blocks/welcomearea/lib.php");

$courseid = optional_param('courseid', 0, PARAM_INT);   // Block passes the course id so we can get some context.
$ownerid  = optional_param('ownerid', 0, PARAM_INT);    // Id of user of message area that is being edited.
$default  = optional_param('default', 0, PARAM_INT);    // flag for editing the default

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

$title = get_string('editortitle', 'block_welcomearea');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_welcomearea'));
$PAGE->navbar->add($title);

echo $OUTPUT->header();

require_capability('block/welcomearea:manageownarea', $context);

$mform = new welcomearea_form();

if($mform->is_cancelled()) {

    // if the form is cancelled, send them back to the course

    echo $OUTPUT->notification(get_string('nochange', 'block_welcomearea'));

    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id'=>$courseid)));
    echo $OUTPUT->footer();
    die;

} elseif ($fromform=$mform->get_data()) {

    // this section handles the submitted form. We should probably revalidate that the user is a teacher.

    $courseid       = $fromform->courseid;
    $welcometext    = $fromform->text["text"];
    $ownerid        = $fromform->ownerid;

    if (has_capability('block/welcomearea:managedefault', $context) ||                           // teacher (and editing their own message) or admin?
            (has_capability('moodle/course:update', $context) and ($ownerid == $USER->id))) {

        if (welcomearea_setcontent($ownerid, $welcometext)) {
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

} else {

    // this section handles the printing the form (i.e. it hasn't been cancelled and hasn't been submitted)

    if ($default && has_capability('block/welcomearea:managedefault', $context)) {

        welcomearea_links('editingdefault', $courseid);         // print our links for editing the default

    } elseif (has_capability('block/welcomearea:managedefault', $context)) {
        // editing displayed welcome area
        welcomearea_links('editingmessage', $courseid);

    } elseif (has_capability('moodle/course:update', $context) and ($ownerid == $USER->id)) {

        welcomearea_links('editingmessage', $courseid);         // print our links for editing personal welcome area

    } else {

        echo $OUTPUT->notification(get_string('error', 'block_welcomearea'));       // if the user is neither a teacher or admin, give them and error
        echo $OUTPUT->continue_button(new moodle_url('/'));
        echo $OUTPUT->footer();
        die;

    }

    $welcomearea = welcomearea_getcontent($ownerid);
    $toform = new stdClass;
    $toform->courseid = $courseid;
    $toform->ownerid = $ownerid;
    $toform->text["text"] = $welcomearea->content;

    $mform->set_data($toform);                                  // set the default values
    $mform->display();                                          // display the form

}

echo $OUTPUT->footer();

?>
