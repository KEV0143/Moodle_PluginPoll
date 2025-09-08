<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_courseworktopics_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025090806) {
        $table = new xmldb_table('courseworktopics');
        $field = new xmldb_field('choiceid', XMLDB_TYPE_INTEGER, '10', null, null, null, 0, 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2025090806, 'courseworktopics');
    }

    return true;
}
