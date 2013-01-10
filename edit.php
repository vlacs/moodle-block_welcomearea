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
 * This script allows users to edit the welcome block. Admins use this page to 
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

global $CFG, $USER;

$courseid = optional_param('courseid', 0, PARAM_INT);   // Block passes the course id so we can get some context.
$ownerid  = optional_param('ownerid', 0, PARAM_INT);    // Id of user of message area that is being edited.
$default  = optional_param('default', 0, PARAM_INT);    // flag for editing the default

$COURSE = get_record('course', 'id', $courseid);          // COURSE object

$context        = get_context_instance(CONTEXT_COURSE, $courseid);
$sitecontext    = get_context_instance(CONTEXT_SYSTEM, SITEID);

$title = get_string('editortitle', 'block_welcomearea');
$nav = array ();
$nav[] = array( 'name' => $title );

print_header($title, $title, build_navigation($nav));

$mform = new welcomearea_form();

if($mform->is_cancelled()) {

    // if the form is cancelled, send them back to the course

    notify(get_string('nochange', 'block_welcomearea'));

    redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);

} elseif ($fromform=$mform->get_data()) {

    // this section handles the submitted form. We should probably revalidate that the user is a teacher.

    $courseid       = $fromform->courseid;
    $welcometext    = $fromform->text;
    $ownerid        = $fromform->ownerid;

    if (has_capability('moodle/site:doanything', $sitecontext) ||                           // teacher (and editing their own message) or admin?
            (has_capability('moodle/course:update', $context) and ($ownerid == $USER->id))) {

        if (welcomearea_setcontent($ownerid, $welcometext)) {

            notify(get_string('confirmation', 'block_welcomearea'), 'notifysuccess');     // if it works, give a confirmation

        } else {

            notify(get_string('editerror', 'block_welcomearea'));                       // if it doesn't work, give an error message

        }

        redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);

    } 

    notify(get_string('error', 'block_welcomearea'));       // if the user is neither a teacher or admin, give them and error
    redirect($CFG->wwwroot);                                // and get them out of here

} else {

    // this section handles the printing the form (i.e. it hasn't been cancelled and hasn't bee submitted)

    if ($default && has_capability('moodle/site:doanything', $sitecontext)) {

        welcomearea_links('editingdefault', $courseid);         // print our links for editing the default

    } elseif (has_capability('moodle/site:doanything', $sitecontext)) {
        // editing displayed welcome area
        welcomearea_links('editingmessage', $courseid);

    } elseif (has_capability('moodle/course:update', $context) and ($ownerid == $USER->id)) {

        welcomearea_links('editingmessage', $courseid);         // print our links for editing personal welcome area

    } else {

        notify(get_string('error', 'block_welcomearea'));       // if the user is neither a teacher or admin, give them and error
        redirect($CFG->wwwroot);                                // and get them out of here

    }

    $welcomearea = welcomearea_getcontent($ownerid);
    $toform = array(    'courseid' => $courseid,
            'ownerid' => $ownerid,
            'text' => $welcomearea->content );

    $mform->set_data($toform);                                  // set the default values
    $mform->display();                                          // display the form

}

print_footer();

?>
