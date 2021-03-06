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
 * This script extends a moodle block_base and is the entry point for the·
 * welcome area.
 *
 * @package     block_welcomearea
 * @copyright   2010 VLACS
 * @author      Dave Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed");

require_once("$CFG->dirroot/blocks/welcomearea/lib.php");

class block_welcomearea extends block_base {

    function init() {
        $this->title    = get_string('welcomearea', 'block_welcomearea');

        # Get version from M&P-compatible version.php
        $plugin = new object;
        require(dirname(__FILE__) . "/version.php");
        $this->version = $plugin->version;
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
