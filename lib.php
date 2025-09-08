<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/modlib.php');
if (file_exists($CFG->dirroot.'/vendor/autoload.php')) {
    require_once($CFG->dirroot.'/vendor/autoload.php');
}

function courseworktopics_add_instance($data, $mform = null) {
    global $DB, $CFG;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $id = $DB->insert_record('courseworktopics', $data);

    $cmid    = $data->coursemodule;
    $context = context_module::instance($cmid);

    $draftid = file_get_submitted_draft_itemid('topicsfile');
    file_save_draft_area_files($draftid, $context->id, 'mod_courseworktopics', 'topicsfile', 0, [
        'subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.txt','.csv','.xlsx']
    ]);

    $fs    = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_courseworktopics', 'topicsfile', 0, 'filename', false);
    if (empty($files)) {
        throw new moodle_exception('nofile', 'courseworktopics');
    }
    $file = reset($files);

    list($rows, $fmt) = courseworktopics_read_uploaded_file($file);
    $options = courseworktopics_parse_rows($rows);

    if (empty($options)) {
        foreach ($rows as $r) {
            $line = is_array($r) ? trim(implode(' ', $r)) : trim((string)$r);
            if ($line === '') { continue; }
            $lower = core_text::strtolower($line);
            if ((strpos($lower,'тема')!==false && (strpos($lower,'мест')!==false||strpos($lower,'кол-во')!==false))
               ||(strpos($lower,'topic')!==false && strpos($lower,'slot')!==false)) { continue; }
            $options[] = ['text' => preg_replace('/[\s;,:()\-\d]+$/u','',$line),'limit'=>1];
        }
    }

    require_once($CFG->dirroot.'/mod/choice/lib.php');
    require_once($CFG->dirroot.'/course/lib.php');

    $course = $DB->get_record('course', ['id' => $data->course], '*', MUST_EXIST);
    $sectionnum = isset($data->section) ? (int)$data->section : 0;

    $choice = (object)[
        'course'          => $course->id,
        'name'            => $data->name,
        'intro'           => get_string('introdefault','courseworktopics'),
        'introformat'     => FORMAT_HTML,
        'timeopen'        => 0,
        'timeclose'       => !empty($data->timeclose) ? (int)$data->timeclose : 0,
        'allowmultiple'   => 0,
        'showresults'     => CHOICE_SHOWRESULTS_NOT,
        'allowupdate'     => !empty($data->allowupdate) ? 1 : 0,
        'limitanswers'    => 1,
        'display'         => CHOICE_DISPLAY_VERTICAL,
        'showunanswered'  => 0,
        'showavailable'  => !empty($data->showavailable) ? 1 : 0,
        'includeinactive' => 0,
        'completionsubmit'=> 1
    ];

    foreach ($options as $idx=>$opt) {
        $choice->option[$idx]   = $opt['text'];
        $choice->limit[$idx]    = $opt['limit'];
        $choice->optionid[$idx] = 0;
        $choice->limitid[$idx]  = 0;
    }

    $fromform = (object)[
        'course'        => $course->id,
        'module'        => $DB->get_field('modules', 'id', ['name' => 'choice']),
        'modulename'    => 'choice',
        'section'       => $sectionnum,
        'visible'       => 1,
        'groupmode'     => 0,
        'groupingid'    => 0,
        'completion'    => 0,
        'completionview'=> 0,
        'completionexpected' => 0,
        'showdescription' => 0,
        'intro'         => $choice->intro,
        'introformat'   => $choice->introformat,
    ];

    $fromform->introeditor = [
        'text'   => $choice->intro,
        'format' => FORMAT_HTML,
        'itemid' => 0
    ];

    foreach ((array)$choice as $k => $v) {
        $fromform->$k = $v;
    }

    $created = create_module($fromform);
    if (is_object($created)) {
        if (!empty($created->coursemodule)) {
            $cmid = (int)$created->coursemodule;
        } else if (!empty($created->cmid)) {
            $cmid = (int)$created->cmid;
        } else if (!empty($created->id)) {
            $cmid = (int)$created->id;
        } else {
            $cmid = 0;
        }
    } else {
        $cmid = (int)$created;
    }
    $DB->set_field('courseworktopics', 'choiceid', $cmid, ['id' => $id]);

    return $id;
}

function courseworktopics_delete_instance($id) {
    global $DB;

    if (!$instance = $DB->get_record('courseworktopics', ['id' => $id])) {
        return false;
    }

    if ($cm = get_coursemodule_from_instance('courseworktopics', $id)) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_courseworktopics');
    }

    $DB->delete_records('courseworktopics', ['id' => $id]);
    return true;
}

function courseworktopics_read_uploaded_file($file): array {
    $filename = core_text::strtolower($file->get_filename());
    $ext = pathinfo($filename, PATHINFO_EXTENSION);

    if ($ext === 'xlsx' && class_exists('PhpOffice\PhpSpreadsheet\IOFactory')){
        $tmp=make_temp_directory('courseworktopics').'/import.xlsx';
        $file->copy_content_to($tmp);
        $reader=\PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $ss=$reader->load($tmp);
        $sheet=$ss->getActiveSheet();
        $rows=[];
        foreach($sheet->getRowIterator() as $row){
            $cells=[];
            $ci=$row->getCellIterator();
            $ci->setIterateOnlyExistingCells(false);
            foreach($ci as $cell){ $cells[]=trim((string)$cell->getValue()); }
            while(!empty($cells) && end($cells)===''){ array_pop($cells); }
            if(!empty($cells)){$rows[]=$cells;}
        }
        @unlink($tmp);
        return [$rows,'xlsx'];
    }
    if ($ext === 'xlsx'){
        throw new moodle_exception('missingdep','courseworktopics','', 'PhpSpreadsheet (composer: phpoffice/phpspreadsheet)');
    }
    throw new moodle_exception('invalidfiletype', 'error', '', $ext);
}

function courseworktopics_parse_rows(array $rows): array {
    $out=[];
    foreach($rows as $r){
        if(is_array($r)){
            $cells=array_map(function($v){return trim((string)$v);},$r);
            if(count($cells)>=2){ $topicRaw=$cells[0]; $slotsRaw=$cells[1]; }
            else{ $topicRaw=$cells[0]??''; $slotsRaw=''; }
        } else {
            $line=trim((string)$r);
            if($line===''){continue;}
            $lower=core_text::strtolower($line);
            if((strpos($lower,'тема')!==false && (strpos($lower,'мест')!==false||strpos($lower,'кол-во')!==false))
               ||(strpos($lower,'topic')!==false && strpos($lower,'slot')!==false)){continue;}
            if(preg_match('/^(.*?)[\s;,:\-\t|()]*?(\d+)\s*(?:мест(?:а|о)?|slots?|шт)?\s*$/ui',$line,$m)){
                $topicRaw=trim($m[1]); $slotsRaw=$m[2];
            } else {
                $topicRaw=$line; $slotsRaw='';
            }
        }
        $topic=preg_replace('/^[\s\d\)\.\-]+/u','',(string)$topicRaw);
        $topic=preg_replace('/[\s;,:()\-\d]+$/u','',$topic);
        if($topic===''){continue;}
        $slots=null;
        if($slotsRaw!==''){
            if(preg_match('/(\d+)/u',(string)$slotsRaw,$mm)){$slots=(int)$mm[1];}
        } else {
            if(preg_match('/(\d+)\s*(?:мест(?:а|о)?|slots?|шт)?\s*$/ui',(string)$topicRaw,$mm)){
                $slots=(int)$mm[1];
                $topic=preg_replace('/[\s;,:()\-\d]+$/u','',(string)$topicRaw);
            }
        }
        if(!$slots||$slots<1){$slots=1;}
        $out[]=['text'=>$topic,'limit'=>$slots];
    }
    return $out;
}
