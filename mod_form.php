<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_courseworktopics_mod_form extends moodleform_mod {
    public function definition() {
        global $PAGE;
        $mform = $this->_form;

        $mform->addElement('header','general',get_string('general','form'));
        $mform->addElement('text','name',get_string('name'),['size'=>'64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('moduleintro'));
        $this->standard_coursemodule_elements();

        $mform->addElement('header','choicesettings', get_string('choicesettings','courseworktopics'));

        $mform->addElement('advcheckbox', 'showavailable', get_string('showavailable','courseworktopics'));
        $mform->setDefault('showavailable', 1);
        $mform->addHelpButton('showavailable','showavailable','courseworktopics');

        $mform->addElement('advcheckbox', 'allowupdate', get_string('allowupdate','courseworktopics'));
        $mform->setDefault('allowupdate', 1);
        $mform->addHelpButton('allowupdate','allowupdate','courseworktopics');

        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopenlbl','courseworktopics'), array('optional'=>true));
        $mform->addElement('date_time_selector', 'timeclose', get_string('timecloselbl','courseworktopics'), array('optional'=>true));

        $mform->addElement('header','importhdr',get_string('importhdr','courseworktopics'));
        $mform->addHelpButton('importhdr','importhdr','courseworktopics');

        $mform->addElement('filepicker','topicsfile',get_string('topicsfile','courseworktopics'), null,
            ['accepted_types'=>['.xlsx']]);
        $mform->addRule('topicsfile', null, 'required', null, 'client');
        $mform->addHelpButton('topicsfile','topicsfile','courseworktopics');

        $previewhtml = html_writer::tag('button', get_string('previewbtn','courseworktopics'),
            ['type'=>'button','id'=>'cwtopics_preview','class'=>'btn btn-secondary']);
        $mform->addElement('static','previewbtn','', $previewhtml);

        $pickfilemsg = json_encode(get_string('topicsfile','courseworktopics'), JSON_UNESCAPED_UNICODE);
        $js = <<<JS
require(['jquery'], function($) {
  $('#cwtopics_preview').on('click', function(e){
    e.preventDefault();
    var draft = $('#id_topicsfile').val();
    if (!draft) { alert({$pickfilemsg}); return; }
    var url = M.cfg.wwwroot + '/mod/courseworktopics/preview.php?draftid=' + encodeURIComponent(draft) + '&sesskey=' + M.cfg.sesskey;
    window.open(url, '_blank');
  });
});
JS;
        $PAGE->requires->js_init_code($js);

        $this->add_action_buttons();
    }
}
