<?php

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

require_once($CFG->libdir . '/dmllib.php');

function welcomearea_getcontent($teacherid) {

    global $CFG;

    $sql = 'SELECT content FROM ' . $CFG->prefix . 'welcomearea WHERE userid=' . $teacherid . ' ORDER BY timemodified DESC';

    if (!$welcomearea = get_record_sql($sql, true)) {   // if we cant get a record

        $teacherid = welcomearea_default();
        $sql = 'SELECT content FROM ' . $CFG->prefix . 'welcomearea WHERE userid=' . $teacherid . ' ORDER BY timemodified DESC';

        if (!$welcomearea = get_record_sql($sql, true)) {   // and we cant get the admin-defined default record

            $welcomearea = new StdClass;
            $welcomearea->content = get_string('default', 'block_welcomearea');      // use the default

        }
    }

    return $welcomearea;
}

function welcomearea_setcontent($teacherid, $welcometext) {

    global $CFG;

    $dataobject = new stdClass;
    $dataobject->userid = $teacherid;
    $dataobject->content = $welcometext;
    $dataobject->timemodified = time();

    return insert_record('welcomearea', $dataobject, false);
}

function welcomearea_display($blockdisplay=false) {
    global $CFG, $COURSE;

    $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

    $teacherid = welcomearea_default();

    if ($current_rule = get_record('welcomearearules', 'courseid', $COURSE->id)) {
        if ($current_rule->nodisplay) {
            return false;
        } else {
            $teacherid = $current_rule->ownerid;
        }

    } elseif ($teachers = get_role_users($CFG->coursemanager, $context, false, 'u.id', 'ra.timemodified ASC')) {
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

function welcomearea_history($courseid, $teacherid) {

    global $CFG;

    $sql = 'SELECT timemodified, content FROM ' . $CFG->prefix . 'welcomearea WHERE userid=' . $teacherid . ' ORDER BY timemodified DESC';

    if(!$welcomehistory = get_records_sql($sql)) {
        $welcomehistory = array();
    }

    $defaultid = welcomearea_default();
    $welcomehistory[] = get_record_sql("SELECT timemodified, content, 1 AS default FROM {$CFG->prefix}welcomearea WHERE userid = $defaultid ORDER BY timemodified DESC", true);

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

    if ($admin = get_record('user', 'username', 'admin')) {
        return $admin->id;
    }

    return 0;
}

function welcomearea_revert($timemodified, $teacherid, $default=0) {

    global $CFG;

    if ($default == 0) {
        if (!$oldrecord = get_record('welcomearea', 'userid', $teacherid, 'timemodified', $timemodified)) {
            echo("error opening old record"); 
            return false;
        }
    } else {
        $defaultid = welcomearea_default();
        if (!$oldrecord = get_record_sql("SELECT timemodified, content, 1 AS default FROM {$CFG->prefix}welcomearea WHERE userid = $defaultid ORDER BY timemodified DESC", true)) {
            echo("error opening old record"); 
        }
    }

    if (welcomearea_setcontent($teacherid, addslashes($oldrecord->content))) {
        return true;
    }

    echo("error setting new record");

    return false;
}

// function to make the links on edit.php and history.php

function welcomearea_links($current, $courseid) {

    global $USER, $CFG;

    $links = "";

    $edit_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/edit.php");
    $edit_url->param('courseid', $courseid);
    $edit_url->param('ownerid', $USER->id);

    $history_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/history.php");
    $history_url->param('courseid', $courseid);
    $history_url->param('ownerid', $USER->id);

    $links .= "<a href=\"" . $edit_url->out() . "\">" . get_string('editlink', 'block_welcomearea') . "</a>";
    $links .= " | <a href=\"" . $history_url->out() . "\">" . get_string('historylink', 'block_welcomearea') . "</a>";

    if (has_capability('moodle/site:doanything', get_context_instance(CONTEXT_SYSTEM, SITEID))) {       // admin?
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

    global $CFG;

    return delete_records('welcomearearules', 'courseid', $courseid);

}

function welcomearea_rule($courseid, $ownerid, $nodisplay=0) {

    global $CFG;

    $dataobject = new stdClass;
    $dataobject->courseid = $courseid;
    $dataobject->ownerid = $ownerid;
    $dataobject->nodisplay = $nodisplay;

    return insert_record('welcomearearules', $dataobject, false);

}

?>
