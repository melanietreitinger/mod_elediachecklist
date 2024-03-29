<?php
// This file is part of the Checklist plugin for Moodle - http://moodle.org/
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
 * Export the checklist items.
 *
 * @copyright Davo Smith <moodle@davosmith.co.uk>
 * @package mod_elediachecklist
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/importexportfields.php');
global $DB, $PAGE, $CFG;
require_once($CFG->libdir.'/csvlib.class.php');
$id = required_param('id', PARAM_INT); // Course module id.

$cm = get_coursemodule_from_id('elediachecklist', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$checklist = $DB->get_record('elediachecklist', array('id' => $cm->instance), '*', MUST_EXIST);

$url = new moodle_url('/mod/elediachecklist/export.php', array('id' => $cm->id));
$PAGE->set_url($url);
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/elediachecklist:edit', $context);

$tab = elediachecklist_tab('eledia_adminexamdates_itm'); // elediachecklist__item
$items = $DB->get_records_select($tab, "checklist = ? AND userid = 0", array($checklist->id), 'position');
if (!$items) {
    throw new moodle_exception('noitems', 'mod_elediachecklist');
}

$csv = new csv_export_writer();
$strchecklist = get_string('eledia', 'elediachecklist');
//$filename = $course->shortname.'_'.$strchecklist.'_'.$checklist->name;
$filename = $course->shortname.'_'.$checklist->name;
$filename = str_replace(' ', '', $filename);
$csv->filename = clean_filename($filename).'.csv';

// Output the headings.
$csv->add_data($fields);

foreach ($items as $item) {
    $output = array();
    foreach ($fields as $field => $unused) {
        $output[] = $item->$field;
    }
    $csv->add_data($output);
}

$csv->download_file();
