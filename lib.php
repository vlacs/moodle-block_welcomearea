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
 * Library of functions used to get and set content and rules for the�
 * welcomearea
 *
 * @package     block_welcomearea
 * @copyright   2010 VLACS
 * @author      Dave Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

require_once($CFG->libdir . '/dmllib.php');

function welcomearea_getcontent($teacherid) {

    global $CFG, $DB;

    $sql = 'SELECT content FROM {block_welcomearea} WHERE userid=' . $teacherid . ' ORDER BY timemodified DESC';

    if (!$welcomearea = $DB->get_record_sql($sql, null, IGNORE_MULTIPLE)) {   // if we cant get a record

        $teacherid = welcomearea_default();
        $sql = 'SELECT content FROM {block_welcomearea} WHERE userid=' . $teacherid . ' ORDER BY timemodified DESC';

        if (!$welcomearea = $DB->get_record_sql($sql, null, IGNORE_MULTIPLE)) {   // and we cant get the admin-defined default record

            $welcomearea = new StdClass;
            $welcomearea->content = get_string('default', 'block_welcomearea');      // use the default

        }
    }

    return $welcomearea;
}

function welcomearea_setcontent($teacherid, $welcometext) {

    global $CFG, $DB;

    $dataobject = new stdClass;
    $dataobject->userid = $teacherid;
    $dataobject->content = $welcometext;
    $dataobject->timemodified = time();

    return $DB->insert_record('block_welcomearea', $dataobject, false);
}

function welcomearea_display($blockdisplay=false) {
    global $CFG, $COURSE, $DB;

    $context = context_course::instance($COURSE->id);

    $teacherid = welcomearea_default();
    $croles = explode(',', $CFG->coursecontact);

    if ($current_rule = $DB->get_record('block_welcomearearules', array('courseid' =>$COURSE->id))) {
        if ($current_rule->nodisplay) {
            return false;
        } else {
            $teacherid = $current_rule->ownerid;
        }

    } elseif ($teachers = get_role_users($croles, $context, true, '', 'r.sortorder ASC, u.lastname ASC')) {
        $teacherid = reset($teachers)->id;
    }

    $welcomearea = welcomearea_getcontent($teacherid);
    if ($blockdisplay) {
        return $welcomearea->content;
    }

    echo('<div width="100%" class="generalbox">');
    echo($welcomearea->content);
    echo('</div>');
}

// Return the id and name of the user who's welcome area is displayed
// returns the welcomearea_default if nodisplay is set
function welcomearea_displayid() {
    global $CFG, $COURSE, $DB, $USER;
    $context = context_course::instance($COURSE->id);

    $displayid = $USER->id;
    $croles = explode(',', $CFG->coursecontact);

    if ($current_rule = $DB->get_record('block_welcomearearules', array('courseid' =>$COURSE->id))) {
        if (!$current_rule->nodisplay) {
            $displayid = $current_rule->ownerid;
        }

    } elseif ($teachers = get_role_users($croles, $context, true, '', 'r.sortorder ASC, u.lastname ASC')) {
        $displayid = reset($teachers)->id;
    }

    return $displayid;
}

function welcomearea_history($courseid, $teacherid) {

    global $CFG, $DB;

    $sql = 'SELECT timemodified, content FROM {block_welcomearea} WHERE userid=' . $teacherid . ' ORDER BY timemodified DESC';

    $welcomehistory = $DB->get_records_sql($sql);

    $defaultid = welcomearea_default();
    $welcomehistory[] = $DB->get_record_sql("SELECT timemodified, content, 1 AS default FROM {block_welcomearea} WHERE userid = $defaultid ORDER BY timemodified DESC", null, IGNORE_MULTIPLE);

    $history_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/history.php");
    $history_url->param('courseid', $courseid);
    $history_url->param('ownerid', $teacherid);

    foreach ( $welcomehistory as $record ) {
        $history_url->param('set', $record->timemodified);

        echo('<br />');
        if (isset($record->default) and $record->default == 1) {
            $history_url->param('use_default', 1);
            echo get_string('use_default', 'block_welcomearea');
        } else {
            echo(userdate($record->timemodified));
        }
        echo(" | <a href=\"" . $history_url->out() . "\">");
        echo(get_string('historyset', 'block_welcomearea') . "</a>");
        echo('<div widtch="100%" class="generalbox">');
        echo($record->content);
        echo('</div>');
    }
}

function welcomearea_default() {
    global $DB;

    if ($admin = $DB->get_record('user', array('username' => 'admin'))) {
        return $admin->id;
    }

    return 0;
}

function welcomearea_revert($timemodified, $teacherid, $default=0) {

    global $CFG, $DB;

    if ($default == 0) {
        if (!$oldrecord = $DB->get_record('block_welcomearea', array('userid'=>$teacherid, 'timemodified'=>$timemodified))) {
            echo("error opening old record"); 
            return false;
        }
    } else {
        $defaultid = welcomearea_default();
        if (!$oldrecord = $DB->get_record_sql("SELECT timemodified, content, 1 AS default FROM {block_welcomearea} WHERE userid = $defaultid ORDER BY timemodified DESC", true)) {
            echo("error opening old record"); 
        }
    }

    welcomearea_setcontent($teacherid, $oldrecord->content);
    return true;
}

// function to make the links on edit.php and history.php

function welcomearea_links($current, $courseid) {

    global $USER, $CFG, $DB;

    if ($courseid) {
        $context = context_course::instance($courseid);
    } else {
        $context = context_system::instance();
    }

    // Find the ownerid to edit, for teachers it is their own id, for 
    // admins/managers this is the id for the welcome area that is displayed
    if (has_capability('block/welcomearea:managedefault', $context)) {       // admin?
        $displayid = welcomearea_displayid();
    } else {
        $displayid = $USER->id;
    }
    $current_owner = $DB->get_record('user', array('id'=>$displayid));
    $name = $current_owner->firstname . " " . $current_owner->lastname;


    $links = "";

    $edit_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/edit.php");
    $edit_url->param('courseid', $courseid);
    $edit_url->param('ownerid', $displayid);

    $history_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/history.php");
    $history_url->param('courseid', $courseid);
    $history_url->param('ownerid', $displayid);

    $links .= "<a href=\"" . $edit_url->out() . "\">" . get_string('editlink', 'block_welcomearea') . " ($name) </a>";
    $links .= " | <a href=\"" . $history_url->out() . "\">" . get_string('historylink', 'block_welcomearea') . " ($name)</a>";

    if (has_capability('block/welcomearea:managedefault', $context)) {       // admin?
        $edit_url->param('ownerid', welcomearea_default());
        $edit_url->param('default', 1);

        $history_url->param('ownerid', welcomearea_default());
        $history_url->param('default', 1);

        $select_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/select.php");
        $select_url->param('courseid', $courseid);

        $links .= " | <a href=\"" . $edit_url->out() . "\">" . get_string('editlinkadmin', 'block_welcomearea') . "</a>";
        $links .= " | <a href=\"" . $history_url->out() . "\">" . get_string('historylinkadmin', 'block_welcomearea') . "</a>";
        $links .= " | <a href=\"" . $select_url->out() . "\">" . get_string('displaylink', 'block_welcomearea') . "</a>";

    }

    echo("<div class=\"generalbox\" style=\"text-align:center;\"><span style=\"font-size:1.2em;font-weight:bold;\">");

    echo(get_string($current, 'block_welcomearea'));

    echo("</span><br />");

    echo($links);

    echo("</div>");
}

function welcomearea_rule_remove($courseid) {

    global $CFG, $DB;

    return $DB->delete_records('block_welcomearearules', array('courseid'=>$courseid));

}

function welcomearea_rule($courseid, $ownerid, $nodisplay=0) {
    global $CFG, $DB;

    $dataobject = new stdClass;
    $dataobject->courseid = $courseid;
    $dataobject->ownerid = $ownerid;
    $dataobject->nodisplay = $nodisplay;

    return $DB->insert_record('block_welcomearearules', $dataobject, false);
}

?>
