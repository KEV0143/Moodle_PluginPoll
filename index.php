<?php
require_once('../../config.php');
$id=required_param('id',PARAM_INT);
$course=get_course($id);
require_login($course);
$PAGE->set_url(new moodle_url('/mod/courseworktopics/index.php',['id'=>$id]));
$PAGE->set_title(get_string('modulenameplural','courseworktopics'));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural','courseworktopics'));
$instances = get_all_instances_in_course('courseworktopics',$course);
if(empty($instances)){
    notice(get_string('thereareno','moodle',get_string('modulenameplural','courseworktopics')),
        new moodle_url('/course/view.php',['id'=>$course->id]));
}
$table=new html_table();
$table->head=[get_string('name'), get_string('viewhelp','courseworktopics')];
foreach($instances as $inst){
    $url=new moodle_url('/mod/courseworktopics/view.php',['id'=>$inst->coursemodule]);
    $link=html_writer::link($url,format_string($inst->name));
    $table->data[]=[$link, get_string('viewhelp','courseworktopics')];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
