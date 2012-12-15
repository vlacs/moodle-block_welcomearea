<?php

// Let's get some Moodle up in here.

require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/weblib.php');

// and our form & function library

require_once("$CFG->dirroot/blocks/welcomearea/form.php");
require_once("$CFG->dirroot/blocks/welcomearea/lib.php");

//global $CFG, $USER, $DB;

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
    $context = get_context_instance(CONTEXT_SYSTEM);
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
    echo $OUTPUT->continue_button(new moodle_url());
    echo $OUTPUT->footer();
    die;

} else {

    // this section handles the printing the form (i.e. it hasn't been cancelled and hasn't been submitted)

    if ($default && has_capability('block/welcomearea:managedefault', $context)) {

        welcomearea_links('editingdefault', $courseid);         // print our links for editing the default

    } elseif (has_capability('moodle/course:update', $context) and ($ownerid == $USER->id)) {

        welcomearea_links('editingmessage', $courseid);         // print our links for editing personal welcome area

    } else {

        echo $OUTPUT->notification(get_string('error', 'block_welcomearea'));       // if the user is neither a teacher or admin, give them and error
        echo $OUTPUT->continue_button(new moodle_url());
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
