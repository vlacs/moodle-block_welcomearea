<?php

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
