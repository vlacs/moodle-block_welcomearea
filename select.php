<?php

// Let's get some Moodle up in here.

require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/weblib.php');

// and our function library

require_once("$CFG->dirroot/blocks/welcomearea/lib.php");

global $CFG, $USER;

$courseid   = optional_param('courseid', 0, PARAM_INT);     // Block passes the course id so we can get some context.
$remove     = optional_param('remove', 0, PARAM_BOOL);      // Flag to indicate we want to remove an override.
$set        = optional_param('set', 0, PARAM_BOOL);         // Flag to indicate we're adding a rule
$ownerid    = optional_param('ownerid', 0, PARAM_INT);      // id of user we'd like to set the overide too. 
$nodisplay  = optional_param('nodisplay', 0, PARAM_BOOL);   // flag to indicate we don't want to display a welcome area for this page

$COURSE = get_record('course', 'id', $courseid);          // COURSE object

$context        = get_context_instance(CONTEXT_COURSE, $courseid);
$sitecontext    = get_context_instance(CONTEXT_SYSTEM, SITEID);

$title = get_string('historytitle', 'block_welcomearea');
$nav = array ();
$nav[] = array( 'name' => $title );

print_header($title, $title, build_navigation($nav));

if (!has_capability('moodle/site:doanything', $sitecontext)) {                          // not an admin?

    notify(get_string('error', 'block_welcomearea'));                                   // give them and error
    redirect($CFG->wwwroot);                                                            // and get them out of here

}

if ($remove and !$set) {                                                            // if remove is true and ownerid is 0, we're simply removing a rule 

    if (welcomearea_rule_remove($courseid)) {

        notify(get_string('removesuccess', 'block_welcomearea'), 'notifysuccess');      // if it works, give a confirmation

    } else {

        notify(get_string('removeerror', 'block_welcomearea'));                         // if it doesn't work, give an error message

    }

    redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);                       // and send them back to the course

}

if ($set) {

    if ($remove) {

        if (!welcomearea_rule_remove($courseid)) {                                  // remove the old rule

            notify(get_string('removeerror', 'block_welcomearea'));                     // if it doesn't work, give an error message

            redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);               // and send them back to the course

        }
    }

    if (welcomearea_rule($courseid, $ownerid, $nodisplay)) {

        notify(get_string('rulesuccess', 'block_welcomearea'), 'notifysuccess');    // if it works, give a confirmation

    } else {

        notify(get_string('ruleerror', 'block_welcomearea'));                       // if it doesn't work, give an error message

    }

    redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);                       // and send them back to the course

}

welcomearea_links('selector', $courseid);                                               // get our header/links

$select_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/select.php");
$select_url->param('courseid', $COURSE->id);

if ($current_rule = get_record('block_welcomearearules', 'courseid', $courseid)) {        // do we already have a rule for this course?

    $select_url->param('remove', 1);                                                    // if so, we'll have to remove the old one

    if ($current_rule->nodisplay) {

        echo(get_string('ruledisabled', 'block_welcomearea'));

    } elseif ($current_rule->ownerid == welcomearea_default()) {

        echo(get_string('ruledefault', 'block_welcomearea'));

    } else {

        $current_owner = get_record('user', 'id', $current_rule->ownerid);

        echo(get_string('ruleuser', 'block_welcomearea') . " " . $current_owner->firstname . " " . $current_owner->lastname);

    }

    echo("<br />");

} else {

    echo(get_string('norule', 'block_welcomearea') . "<br />");

}

if ($teachers = get_role_users($CFG->coursemanager, $context, false, '', 'ra.timemodified ASC')) {

    // show what is auto-selected if there is no rule

    $teacher = reset($teachers);
    $welcomearea = welcomearea_getcontent($teacher->id);

    echo("<br /><a href=\"" . $select_url->out() . "\">" . get_string('defteacher', 'block_welcomearea'));
    echo("</a>");
    echo(" (" . $teacher->firstname . " " . $teacher->lastname . ")");
    echo("<div width=\"100%\" class=\"generalbox\">" . $welcomearea->content . "</div>");

    $select_url->param('set', 1);

    foreach ($teachers as $teacher) {

        // then give an option to select each teacher

        $welcomearea = welcomearea_getcontent($teacher->id);
        $select_url->param('ownerid', $teacher->id);

        echo("<a href=\"" . $select_url->out() . "\">");
        echo($teacher->firstname . " " . $teacher->lastname);
        echo("</a>");
        echo("<div width=\"100%\" class=\"generalbox\">" . $welcomearea->content . "</div>");

    }

} else {

    echo("<br />" . get_string('noteacher', 'block_welcomearea') . "<br />");                                 // if there is no teacher assigned, give a message

    echo("<br /><a href=\"" . $select_url->out() . "\">" . get_string('defteacher', 'block_welcomearea'));    // auto link
    echo("</a>");
    echo(" (" . get_string('selectdefault', 'block_welcomearea') . ")<br /><br />");
}

// and an option to set to the admin defined welcome area

$select_url->param('set', 1);
$select_url->param('ownerid', welcomearea_default());
$welcomearea = welcomearea_getcontent(welcomearea_default());

echo("<a href=\"" . $select_url->out() . "\">");
echo(get_string('selectdefault', 'block_welcomearea'));
echo("</a>");
echo("<div width=\"100%\" class=\"generalbox\">" . $welcomearea->content . "</div>");

// and an option to turn of the welcome area for this course

$select_url->param('nodisplay', 1);

echo("<a href=\"" . $select_url->out() . "\">");
echo(get_string('selectnodisplay', 'block_welcomearea'));
echo("</a>");

print_footer();

?>
