<?php

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed");

require_once("$CFG->dirroot/blocks/welcomearea/lib.php");

class block_welcomearea extends block_base {

    function init() {
        $this->title    = get_string('welcomearea', 'block_welcomearea');
    }

    function get_content() {

        global $CFG, $USER, $COURSE, $OUTPUT;

        $this->content          = new stdClass;
        $this->content->text    = '';
        $this->content->footer  = '';

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        $edit_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/edit.php");
        $edit_url->param('courseid', $COURSE->id);
        $edit_url->param('ownerid', $USER->id);

        if (!isset($CFG->block_welcomearea_block_display) or $CFG->block_welcomearea_block_display!=0) {
            if ($welcomearea = welcomearea_display(true)) {
                $this->content->text .= $welcomearea; 
                if (has_capability('moodle/course:update', $context)) {    // is the user a teacher ?
                    $this->content->text .= "<hr />";
                }
            }
        }

        if (has_capability('moodle/course:update', $context)) {    // is the user a teacher ?
            $this->content->text .= "<img src=\"" . $OUTPUT->pix_url('i/edit') . "\" class=\"icon\" alt=\"\" />";
            $this->content->text .= "<a href=\"" . $edit_url->out() . "\">" . get_string('editlink', 'block_welcomearea') . "</a>";
        }

        if (has_capability('block/welcomearea:managedefault', $context)) {       // is the user an admin? 

            $edit_url->param('ownerid', welcomearea_default());
            $edit_url->param('default', 1);

            $select_url = new moodle_url("$CFG->wwwroot/blocks/welcomearea/select.php");
            $select_url->param('courseid', $COURSE->id);

            $this->content->text .= "<br /><img src=\"" . $OUTPUT->pix_url('i/edit') . "\" class=\"icon\" alt=\"\" />";
            $this->content->text .= "<a href=\"" . $edit_url->out() . "\">" . get_string('editlinkadmin', 'block_welcomearea') . "</a>";

            $this->content->text .= "<br /><img src=\"" . $OUTPUT->pix_url('i/settings') . "\" class=\"icon\" alt=\"\" />";
            $this->content->text .= "<a href=\"" . $select_url->out() . "\">" . get_string('displaylink', 'block_welcomearea') . "</a>";

        }

        // When CFG->block_welcomearea_block_display is set to false/zero:
        // $this->content->text should be empty unless the user is an admin or a teacher, the block shouldn't even appear for anyone else

        return $this->content;
    }

}

?>
