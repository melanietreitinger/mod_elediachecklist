<?php
// This file is part of Moodle - http://moodle.org/
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
 * Holds the checkmark information
 *
 * @package   mod_elediachecklist
 * @copyright 2016 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_elediachecklist\local;

use data_object;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/completion/data_object.php');
require_once($CFG->dirroot.'/mod/elediachecklist/lib.php');


$tab = elediachecklist_tab('eledia_adminexamdates_chk'); // elediachecklist__check
define('ELEDIACHECKLIST_CHECKLIST_CHECK_TABLE', $tab);


/**
 * Class checklist_check
 * @package mod_elediachecklist
 */
class checklist_check extends data_object {
    /** @var string */
    public $table = ELEDIACHECKLIST_CHECKLIST_CHECK_TABLE; // elediachecklist__check
    /** @var string[] */
    public $requiredfields = [
        'id', 'item', 'userid', 'usertimestamp', 'teachermark', 'teachertimestamp', 'teacherid'
    ];

    // DB fields.
    /** @var int */
    public $item;
    /** @var int */
    public $userid;
    /** @var int */
    public $usertimestamp = 0;
    /** @var int */
    public $teachermark = ELEDIACHECKLIST_TEACHERMARK_UNDECIDED;
    /** @var int */
    public $teachertimestamp = 0;
    /** @var int|null */
    public $teacherid = null;

    /**
     * checklist_check constructor.
     * @param array|null $params
     * @param bool $fetch
     * @throws \coding_exception
     */
    public function __construct(array $params = null, $fetch = true) {
        // Really ugly hack to stop travis complaining about $required_fields.
        $this->{'required_fields'} = $this->requiredfields;
        parent::__construct($params, $fetch);
    }

    /**
     * Get a single matching record.
     * @param array $params
     * @return data_object|false|object
     */
    public static function fetch($params) {
        $tab = elediachecklist_tab('eledia_adminexamdates_chk'); // elediachecklist__check
        return self::fetch_helper($tab, __CLASS__, $params);
    }

    /**
     * Get all matching records.
     * @param array $params
     * @param false $sort
     * @return array|false|mixed
     */
    public static function fetch_all($params, $sort = false) {
        $tab = elediachecklist_tab('eledia_adminexamdates_chk'); // elediachecklist__check
        $ret = self::fetch_all_helper($tab, __CLASS__, $params);
        if (!$ret) {
            $ret = [];
        }
        return $ret;
    }

    /**
     * Get all checks for the given user matching the given itemids.
     * @param int $userid
     * @param int[] $itemids
     * @return checklist_check[] $itemid => $check
     */
    public static function fetch_by_userid_itemids($userid, $itemids) {
        global $DB;

        $ret = [];
        if (!$itemids) {
            return $ret;
        }

        list($isql, $params) = $DB->get_in_or_equal($itemids, SQL_PARAMS_NAMED);
        $params['userid'] = $userid;
        $tab = elediachecklist_tab('eledia_adminexamdates_chk'); // elediachecklist__check
        $checks = $DB->get_records_select($tab, "userid = :userid AND item $isql", $params);
        foreach ($checks as $check) {
            $ret[$check->item] = new checklist_check();
            self::set_properties($ret[$check->item], $check);
        }
        return $ret;
    }

    /**
     * Is this a valid teacher mark?
     * @param int $teachermark
     * @return bool
     */
    public static function teachermark_valid($teachermark) {
        return in_array($teachermark, [ELEDIACHECKLIST_TEACHERMARK_YES, ELEDIACHECKLIST_TEACHERMARK_NO, ELEDIACHECKLIST_TEACHERMARK_UNDECIDED]);
    }

    /**
     * Debugging check for valid fields.
     */
    protected function check_fields_valid() {
        if (!self::teachermark_valid($this->teachermark)) {
            debugging('Unexpected teachermark value: '.$this->teachermark);
            $this->teachermark = ELEDIACHECKLIST_TEACHERMARK_UNDECIDED;
        }
    }

    /**
     * Insert/update the record, as needed
     */
    public function save() {
        if ($this->id) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    /**
     * Insert a new record
     * @return false|int
     */
    public function insert() {
        $this->check_fields_valid();
        return parent::insert();
    }

    /**
     * Update an existing record
     * @return bool
     */
    public function update() {
        $this->check_fields_valid();
        return parent::update();
    }

    /**
     * Has the item been checked by the student?
     * @return bool
     */
    public function is_checked_student() {
        return $this->usertimestamp > 0;
    }

    /**
     * Has the item been checked by the teacher (and set to 'Yes')?
     * @return bool
     */
    public function is_checked_teacher() {
        return ($this->teachermark == ELEDIACHECKLIST_TEACHERMARK_YES);
    }

    /**
     * Set the teacher mark for this item
     * @param int $teachermark
     * @param int $teacherid
     */
    public function set_teachermark($teachermark, $teacherid) {
        $this->teachermark = $teachermark;
        $this->teacherid = $teacherid;
        $this->teachertimestamp = time();
    }

    /**
     * Set/clear the student mark for this item
     * @param bool $checked
     * @param int|null $timestamp
     */
    public function set_checked_student($checked, $timestamp = null) {
        $timestamp = $timestamp ?: time();
        $this->usertimestamp = $checked ? $timestamp : 0;
    }
}
