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
 * This page prints a particular instance of checklist
 *
 * @copyright Davo Smith <moodle@davosmith.co.uk>
 * @package mod_elediachecklist
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

global $DB, $PAGE, $CFG, $USER;

$qmName = optional_param('name', 0, PARAM_TEXT);
$checklistId = optional_param('checklistId', 0, PARAM_INT);
$qmId = optional_param('QMId', -1, PARAM_INT);

$tab = elediachecklist_tab('eledia_adminexamdates_my_itm'); // elediachecklist__my_item

if ($qmId == -1) {
    $DB->execute("INSERT INTO {".$tab."} (is_checkbox, displaytext, type) VALUES ('1', '" . $qmName . "', 'qm')");
} else {
    $DB->execute("UPDATE {".$tab."} SET displaytext = ? WHERE id = ?",[$qmName, $qmId]);
}

echo "Item added/updated";