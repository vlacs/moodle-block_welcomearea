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
 * This script allows admins to change a rule for a welcome area. The rules�
 * dictate which users's welcome area is shown for a course.
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

require_login();

$courseid   = optional_param('courseid', 0, PARAM_INT);     // Block passes the course id so we can get some context.
$remove     = optional_param('remove', 0, PARAM_BOOL);      // Flag to indicate we want to remove an override.
$set        = optional_param('set', 0, PARAM_BOOL);         // Flag to indicate we're adding a rule
$ownerid    = optional_param('ownerid', 0, PARAM_INT);      // id of user we'd like to set the overide too. 
$nodisplay  = optional_param('nodisplay', 0, PARAM_BOOL);   // flag to indicate we don't want to display a welcome area for this page

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
if ($remove) {
    $urlparams['remove'] = $remove;
}
if ($set) {
    $urlparams['set'] = $set;
}
if ($ownerid) {
    $urlparams['ownerid'] = $ownerid;
}
if ($nodisplay) {
    $urlplarams['nodisplay'] = $nodisplay;
}
$url = new moodle_url('/block/welcomarea/select.php', $urlparams);
$PAGE->set_url($url);

$title = get_string('historytitle', 'block_welcomearea');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_welcomearea'));
$PAGE->navbar->add($title);

echo $OUTPUT->header();

require_capability('block/welcomearea:managedefault', $context);

if ($remove and !$set) {                                                            // if remove is true and ownerid is 0, we're simply removing a rule 
    if (welcomearea_rule_remove($courseid)) {
        echo $OUTPUT->notification(get_string('removesuccess', 'block_welcomearea'), 'notifysuccess');      // if it works, give a confirmation
    } else {
        echo $OUTPUT->notification(get_string('removeerror', 'block_welcomearea'));                         // if it doesn't work, give an error message
    }
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id'=>$courseid)));
    echo $OUTPUT->footer();
    die;
}

if ($set) {
    if ($remove) {
        if (!welcomearea_rule_remove($courseid)) {                                  // remove the old rule
            echo $OUTPUT->notification(get_string('removeerror', 'block_welcomearea'));                     // if it doesn't work, give an error message
            echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id'=>$courseid)));
            echo $OUTPUT->footer();
            die;
        }
    }

    welcomearea_rule($courseid, $ownerid, $nodisplay);
    echo $OUTPUT->notification(get_string('rulesuccess', 'block_welcomearea'), 'notifysuccess');    // if it works, give a confirmation
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id'=>$courseid)));
    echo $OUTPUT->footer();
die;
}

welcomearea_links('selector', $courseid);                                               // get our header/links

$select_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/select.php");
$select_url->param('courseid', $course->id);

if ($current_rule = $DB->get_record('block_welcomearearules', array('courseid'=>$courseid))) {        // do we already have a rule for this course
    $select_url->param('remove', 1);                                                    // if so, we'll have to remove the old one

    if ($current_rule->nodisplay) {
        echo(get_string('ruledisabled', 'block_welcomearea'));
    } elseif ($current_rule->ownerid == welcomearea_default()) {
        echo(get_string('ruledefault', 'block_welcomearea'));
    } else {
        $current_owner = $DB->get_record('user', array('id'=>$current_rule->ownerid));
        echo(get_string('ruleuser', 'block_welcomearea') . " " . $current_owner->firstname . " " . $current_owner->lastname);
    }
    echo("<br />");
} else {
    echo(get_string('norule', 'block_welcomearea') . "<br />");
}

$croles = explode(',', $CFG->coursecontact);
if ($teachers = get_role_users($croles, $context, true, '', 'r.sortorder ASC, u.lastname ASC')) {

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

$OUTPUT->footer()

?>
