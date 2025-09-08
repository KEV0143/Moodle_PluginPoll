<?php
require('../../config.php');
require_login();
require_sesskey();

$draftid = required_param('draftid', PARAM_INT);

$context = context_user::instance($USER->id);
$PAGE->set_url(new moodle_url('/mod/courseworktopics/preview.php', ['draftid' => $draftid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('previewtitle','courseworktopics'));
$PAGE->set_heading(get_string('previewtitle','courseworktopics'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('previewtitle','courseworktopics'));

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'filename', false);
if (empty($files)) {
    echo $OUTPUT->notification(get_string('nofile','courseworktopics'), 'notifyproblem');
    echo $OUTPUT->footer();
    exit;
}
$file = reset($files);

$filename = core_text::strtolower($file->get_filename());
$ext = pathinfo($filename, PATHINFO_EXTENSION);

if ($ext === 'xlsx' && class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    $tmp = make_temp_directory('courseworktopics').'/preview.xlsx';
    $file->copy_content_to($tmp);

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true);
    $ss = $reader->load($tmp);
    $sheet = $ss->getActiveSheet();

    $rows = [];
    foreach ($sheet->getRowIterator() as $row) {
        $cells = [];
        $ci = $row->getCellIterator();
        $ci->setIterateOnlyExistingCells(false);
        foreach ($ci as $cell) {
            $cells[] = trim((string)$cell->getValue());
        }
        // обрезаем хвостовые пустые
        while (!empty($cells) && end($cells) === '') { array_pop($cells); }
        if (!empty($cells)) { $rows[] = $cells; }
    }
    @unlink($tmp);
} else {
    echo $OUTPUT->notification(get_string('xlsxonly','courseworktopics'), 'notifyproblem');
    echo $OUTPUT->footer();
    exit;
}

require_once($CFG->dirroot.'/mod/courseworktopics/lib.php');
$options = courseworktopics_parse_rows($rows);

if (empty($options)) {
    echo $OUTPUT->notification(get_string('notopics','courseworktopics'), 'notifyproblem');
} else {
    $table = new html_table();
    $table->head = [get_string('name'), get_string('slots','courseworktopics')];
    foreach ($options as $o) {
        $table->data[] = [s($o['text']), (int)$o['limit']];
    }
    echo html_writer::table($table);
}

echo html_writer::tag('p', get_string('previewhint','courseworktopics'));
echo $OUTPUT->footer();
