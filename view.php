<?php
require_once('../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('courseworktopics', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('courseworktopics', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url(new moodle_url('/mod/courseworktopics/view.php', ['id' => $cm->id]));
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($instance->name));

if (!empty($instance->choiceid)) {
    $choicecmid = (int)$instance->choiceid;
    $choiceurl = new moodle_url('/mod/choice/view.php', ['id' => $choicecmid]);

    $choicecontext = context_module::instance($choicecmid);
    if (has_capability('mod/choice:readresponses', $choicecontext)) {
        $answer = $DB->get_record('choice_answers', ['choiceid' => $choicecmid, 'userid' => $USER->id]);
        if ($answer) {
            // Получим текст варианта.
            $opt = $DB->get_record('choice_options', ['id' => $answer->optionid], 'text');
            if ($opt) {
                echo $OUTPUT->box(get_string('yourchoice_admin','courseworktopics', format_string($opt->text)), 'generalbox');
            }
        } else {
            echo $OUTPUT->notification(get_string('nochosen_admin','courseworktopics'), 'info');
        }
        echo $OUTPUT->single_button($choiceurl, get_string('gotochoice','courseworktopics'), 'get');
    } else {
        redirect($choiceurl);
    }

    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->box(format_module_intro('courseworktopics', $instance, $cm->id), 'generalbox mod_introbox');

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_courseworktopics', 'topicsfile', 0, 'filename', false);

if (!empty($files)) {
    $file = reset($files);
    require_once($CFG->dirroot.'/mod/courseworktopics/lib.php');
    list($rows, $fmt) = courseworktopics_read_uploaded_file($file);
    $options = courseworktopics_parse_rows($rows);

    if (!empty($options)) {
        $table = new html_table();
        $table->head = [get_string('name'), get_string('slots','courseworktopics')];
        foreach ($options as $o) {
            $table->data[] = [s($o['text']), (int)$o['limit']];
        }
        echo html_writer::table($table);
    } else {
        echo $OUTPUT->notification(get_string('notopics','courseworktopics'), 'notifyproblem');
    }
} else {
    echo $OUTPUT->notification(get_string('nofile','courseworktopics'), 'notifyproblem');
}

echo $OUTPUT->footer();
