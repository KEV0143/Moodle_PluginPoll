<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class courseworktopics_upload_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        
        $mform->addElement('header', 'uploadheader', get_string('uploadfile', 'courseworktopics'));
        
        $mform->addElement('filepicker', 'topicsfile', get_string('file'), null, 
            array('accepted_types' => '.txt'));
        $mform->addRule('topicsfile', null, 'required');
        
        $mform->addElement('submit', 'submitbutton', get_string('upload'));
    }
}