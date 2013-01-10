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
 * This script extends a moodleform and allows the content of a welcome area to 
 * be modified.
 *
 * @package     block_welcomearea
 * @copyright   2010 VLACS
 * @author      Dave Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

require_once($CFG->libdir.'/formslib.php');

class welcomearea_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('htmleditor', 'text', 'Welcome Area', array(
                    'canUseHtmlEditor'=>'detect',
                    'rows'  => 25, 
                    'cols'  => 65, 
                    'width' => 0,
                    'height'=> 0, 
                    'course'=> 0,));
        $mform->setType('text', PARAM_RAW);
        $mform->addRule('text', null, 'required', null, 'client');

        $mform->addElement('hidden', 'courseid', 0);
        $mform->addElement('hidden', 'ownerid', 0);
        $mform->addElement('format', 'format', get_string('format'));

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

    }
}

?>
