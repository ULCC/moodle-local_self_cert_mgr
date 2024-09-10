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
 *
 * @package    local_self_cert_mgr
 * @author     Hieu Van <hieu.van@cosector.com>
 * @copyright  ULCC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_self_cert_mgr_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course){

    if(!$iscurrentuser){
        return false;
    }

    $context = context_system::instance();
    if(!has_capability('local/self_cert_mgr:edit', $context)){
        return false;
    }

    //add category for this module
    $title = 'Self Cert Management';

    if (!array_key_exists('selfcertmanagement', $tree->__get('categories'))) {
        $category = new core_user\output\myprofile\category('selfcertmanagement', $title);
        $tree->add_category($category);
    }

    //add links to category
    $url = new moodle_url('/local/self_cert_mgr/scm.php');
    $linktext = 'Search for Self Cert Information';
    $node = new core_user\output\myprofile\node('selfcertmanagement', 'selfcertmanagementlink', $linktext, null, $url);
    $tree->add_node($node);

    return true;
}

/**
 * Function to calculate number of the current self certifications within specified period
 *
 * @param $enrol_date
 * @return int
 * @throws dml_exception
 */
function local_self_cert_mgr_current_self_cert_count($userid) {
    global $DB;

    $enrol_date = local_self_cert_mgr_get_user_custom_profile_field_value('enrol_date', $userid);

    // translate date into timestamp
    $enrol_date = strtotime($enrol_date);

    $params = array('userid'=>$userid,
                    'enroldate'=>$enrol_date);

    $sql = "SELECT count(id)
            FROM {coursework_mitigations}
            WHERE type = 'extension'
            AND selfcert = 1
            AND allocatableid = :userid";
            #AND timecreated > :enroldate";

    $count = $DB->count_records_sql($sql, $params);


    return $count;
}


/**
 * Function to calculate number of available self certifications within the specified period
 *
 * @return false|int|mixed|object|string
 * @throws dml_exception
 */
function local_self_cert_mgr_no_self_cert_available($userid) {

    // get enrol date from user's custom profile field
    #$enrol_date = local_self_cert_mgr_get_user_custom_profile_field_value('enrol_date', $userid);
    $maxselfcert = get_config('block_rhb', 'selfcertmax');

    //has user unlimited extensions? skip
    $unlimitedextensions = local_self_cert_mgr_get_user_custom_profile_field_value('exte', $userid);

    if (!($unlimitedextensions && $unlimitedextensions == 'EXTE')  && $maxselfcert) {
        // get a number of self cert left to use
       return $noleft = $maxselfcert - local_self_cert_mgr_current_self_cert_count($userid);
    }

    return false;

}

/**
 * Function to retrieve the value of the custom profile field
 *
 * @param $fieldname
 * @param $userid
 * @return mixed
 * @throws dml_exception
 */
function local_self_cert_mgr_get_user_custom_profile_field_value($fieldname, $userid) {
    global $DB;

    $params = array('fieldname' => $fieldname,
                    'userid' => $userid);

    $sql = "SELECT * FROM {user_info_field} f
            JOIN {user_info_data} d ON f.id = d.fieldid
            WHERE f.shortname = :fieldname AND d.userid = :userid";

    $field = $DB->get_record_sql($sql, $params);
    $value = ($field)?  $field->data : '';

    return $value;
}
