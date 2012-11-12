<?php

// Let's get some Moodle up in here.

require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/weblib.php');

// and our function library

require_once("$CFG->dirroot/blocks/welcomearea/lib.php");

global $CFG, $USER;

$courseid   = optional_param('courseid', 0, PARAM_INT);   // Block passes the course id so we can get some context.
$default    = optional_param('default', 0, PARAM_BOOL);   // Flag to indicate we want to edit the default welcomearea.
$set        = optional_param('set', 0, PARAM_INT);        // timestamp of the entry we want to revert to. 0 to display history.
$ownerid    = optional_param('ownerid', 0, PARAM_INT);    // id of user of message area that is being edited 
$use_default    = optional_param('use_default', 0, PARAM_INT);    // used to revert to the default

$COURSE = get_record('course', 'id', $courseid);          // COURSE object

$context        = get_context_instance(CONTEXT_COURSE, $courseid);
$sitecontext    = get_context_instance(CONTEXT_SYSTEM, SITEID);

$title = get_string('historytitle', 'block_welcomearea');
$nav = array ();
$nav[] = array( 'name' => $title );

print_header($title, $title, build_navigation($nav));

if ($set) {                                                 // if set is non-zero then we're doing a revert operation

    if ((has_capability('moodle/site:doanything', $sitecontext)) ||
            (has_capability('moodle/course:update', $context) and ($USER->id == $ownerid))) {

        if (welcomearea_revert($set, $ownerid, $use_default)) {         // attempt to do a revert

            notify(get_string('confirmation', 'block_welcomearea'), 'notifysuccess');     // if it works, give a confirmation

        } else {

            notify(get_string('editerror', 'block_welcomearea'));                       // if it doesn't work, give an error message

        }

        redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);

    }

    notify(get_string('error', 'block_welcomearea'));       // if the user is neither a teacher or admin, give them and error
    redirect($CFG->wwwroot);                                // and get them out of here

}

// if set is 0 then we need to view the history

if ($default and has_capability('moodle/site:doanything', $sitecontext)) {       // admin?

    welcomearea_links('historydefault', $courseid);     // print the links

} else if (has_capability('moodle/course:update', $context) and ($ownerid == $USER->id)) {            // teacher?

    welcomearea_links('historymessage', $courseid);            // print the links

} else {

    notify(get_string('error', 'block_welcomearea'));       // if the user is neither a teacher or admin, give them and error
    redirect($CFG->wwwroot);                            // and get them out of here

}

welcomearea_history($courseid, $ownerid);          // print the history

print_footer();

?>
