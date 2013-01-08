<?php

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed");

require_once("$CFG->dirroot/blocks/welcomearea/lib.php");

class block_welcomearea extends block_base {

    function init() {
        $this->title    = get_string('welcomearea', 'block_welcomearea');
        $this->version  = 2013010700;
    }

    function get_content() {

        global $CFG, $USER, $COURSE;

        $this->content          = new stdClass;
        $this->content->text    = '';
        $this->content->footer  = '';

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        // Find the ownerid to edit, for teachers it is their own id, for
        // admins/managers this is the id for the welcome area that is displayed
        if (has_capability('moodle/site:doanything', get_context_instance(CONTEXT_SYSTEM, SITEID))) {       // is the user an admin?
            $displayid = welcomearea_displayid();
            $current_owner = get_record('user', 'id', $displayid);
            $name = $current_owner->firstname . " " . $current_owner->lastname;
        } else {
            $displayid = $USER->id;
            $name = "";
        }

        $edit_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/edit.php");
        $edit_url->param('courseid', $COURSE->id);
        $edit_url->param('ownerid', $displayid);

        if (isset($CFG->block_welcomearea_block_display) and $CFG->block_welcomearea_block_display) {
            if ($welcomearea = welcomearea_display(true)) {
                $this->content->text .= $welcomearea;
                $this->content->text .= "<hr />";
            }
        }

        if (has_capability('moodle/course:update', $context)) {                                   // is the user a teacher ?
            $this->content->text .= "<img src=\"" . $CFG->pixpath . "/i/edit.gif\" class=\"icon\" alt=\"\" />";
            $this->content->text .= "<a href=\"" . $edit_url->out() . "\">" . get_string('editlink', 'block_welcomearea') . " $name</a>";
        }

        if (has_capability('moodle/site:doanything', get_context_instance(CONTEXT_SYSTEM, SITEID))) {       // is the user an admin?

            $edit_url->param('ownerid', welcomearea_default());
            $edit_url->param('default', 1);

            $select_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/select.php");
            $select_url->param('courseid', $COURSE->id);

            $this->content->text .= "<br /><img src=\"" . $CFG->pixpath . "/i/edit.gif\" class=\"icon\" alt=\"\" />";
            $this->content->text .= "<a href=\"" . $edit_url->out() . "\">" . get_string('editlinkadmin', 'block_welcomearea') . "</a>";

            $this->content->text .= "<br /><img src=\"" . $CFG->pixpath . "/i/settings.gif\" class=\"icon\" alt=\"\" />";
            $this->content->text .= "<a href=\"" . $select_url->out() . "\">" . get_string('displaylink', 'block_welcomearea') . "</a>";

        }

        // As the $this->content->text should be empty unless the user is an admin or a teacher, the block shouldn't even appear for anyone else

        return $this->content;
    }

    function has_config() {
        return true;
    }

    function config_save($data) {

        foreach ($data as $name => $value) {
            set_config($name, $value);
        }

        return true;
    }
}

?>
