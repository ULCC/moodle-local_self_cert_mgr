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

require_once((__DIR__).'/../../config.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$title = get_string('pluginname', 'local_self_cert_mgr');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($CFG->wwwroot . '/scm.php');

if(!has_capability('local/self_cert_mgr:edit', $context)) {
    echo $OUTPUT->header();

    echo "<p>You don't have the permission to view this page.</p>";

    echo $OUTPUT->footer();
    exit;
}

global $DB;

$dbman = $DB->get_manager();

$selected_extensiontype = optional_param('sl_extensiontype', '', PARAM_TEXT);

$searchfields = [
                    'firstname' => get_string('firstname'),
                    'lastname' => get_string('lastname'),
                    'idnumber' => get_string('idnumber'),
                ];

$extensiontypes = [];
if ($dbman->table_exists('local_user_info_ext')) {
    $sql = 'SELECT DISTINCT type FROM {local_user_info_ext}';
    $extensiontypes = $DB->get_records_sql($sql);
    $extensiontypes = array_column($extensiontypes, 'type');

    foreach($extensiontypes as $extensiontype) {
        $selected = '';
        if ($selected_extensiontype == $extensiontype) {
            $selected = 'selected';
        }
        if (get_string_manager()->string_exists($extensiontype, 'local_self_cert_mgr')) {
            $value = get_string($extensiontype, 'local_self_cert_mgr');
            $tmp = '<option value="'. $extensiontype .'" '. $selected .'>'. $value .'</option>';
            $option_html = empty($option_html) ? $tmp : $option_html . $tmp;
        }
        else {
            $tmp = '<option value="'. $extensiontype .'" '. $selected .'>'. $extensiontype .'</option>';
            $option_html = empty($option_html) ? $tmp : $option_html . $tmp;
        }
    }
}

foreach($searchfields as $key => $value) {
    $selected = '';
    if ($selected_extensiontype == $key) {
        $selected = 'selected';
    }
    $tmp = '<option value="'. $key .'" '. $selected .'>'. $value .'</option>';
    $option_html = empty($option_html) ? $tmp : $option_html . $tmp;
}


$html = '
    <div class="container warppercontent">
        <div class="row wrappersearchfield">
            <div class="col-sm-12">
                <form name="formsearch" action="" method="get">
                    <p>'. $title .'</p>
                    <span>Search</span>
                    <input type="text" name="txtsearch" value="[SEARCH-VALUE]" />
                    <select class="custom-select" name="sl_extensiontype">
                    '. $option_html .'
                    </select>
                    <input type="submit" name="sbsearch" value="Search" />
                </form>
            </div>
        </div>
        [WRAPPER-RESULT]
    </div>
    <style type="text/css">
        .warppercontent { min-height: 600px; }
        .wrappersearchfield { border: 1px solid #000; padding: 30px 0; }
        .wrappersearchfield + div { margin-top: 40px; border: 1px solid; padding: 30px 0; }
        .tableresult th, .tableresult td { padding: 7px 20px; }
        input[name=sbsearch] { padding: 4px 8px; border-radius: 6px; border: 1px solid #8f959e; }
        input[name=txtsearch] { padding: 5px 3px; border-radius: 6px; border: 1px solid #8f959e; }
    </style>
';

$txtsearch = optional_param('txtsearch', '', PARAM_TEXT);
if(!empty($txtsearch)) {
    $html = str_replace('[SEARCH-VALUE]', $txtsearch, $html);

    $html_wrapper_result =
        '<div class="row">
            <div class="col-sm-12">
                <div class="wrappertable">
                    [SEARCH-RESULT]
                </div>
            </div>
        </div>';
    $html = str_replace('[WRAPPER-RESULT]', $html_wrapper_result, $html);

    if (in_array($selected_extensiontype, $extensiontypes)) {
        $sql = "SELECT userid FROM {local_user_info_ext}
                    WHERE type = $selected_extensiontype
                        AND value LIKE '%$txtsearch%'
                ";
        $userids = $DB->get_records_sql($sql);
    }
    else {
        $sql = "SELECT id FROM {user}
                    WHERE $selected_extensiontype LIKE '%$txtsearch%'
                ";
        $userids = $DB->get_records_sql($sql);
    }

    if (empty($userids)) {
        $html = str_replace('[SEARCH-RESULT]', '<p>No records found</p>', $html);
    }
    else {
        $html_results = '<table cellpadding="0" cellspacing="0" border="1" rules="All" width="100%" class="tableresult">
                            <thead>
                            <th>Student Name</th>
                            <th>SPR</th>
                            <th>Candidate Number</th>
                            <th>Action</th>
                            </thead>
                            <tbody>';
        foreach($userids as $userid => $value) {
            $user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
            if (!empty($user)) {
                $html_results .= '<tr>';
                $html_results .= '<td data-userid="'. $user->id .'">'. $user->firstname . ' ' . $user->lastname .'</td>';

                if ($dbman->table_exists('local_user_info_ext')) {
                    $sql = 'SELECT spr, candno FROM {local_user_info_ext}
                            WHERE userid = :userid
                            LIMIT 1';
                    $record = $DB->get_record_sql($sql);
                    if (!empty($record)) {
                        $html_results .= '<td>'. $record->spr .'</td>';
                        $html_results .= '<td>'. $record->candno .'</td>';
                    }
                    else {
                        $html_results .= '<td></td>';
                        $html_results .= '<td></td>';
                    }
                }
                else {
                    $html_results .= '<td></td>';
                    $html_results .= '<td></td>';
                }

                $url = new \moodle_url('/local/self_cert_mgr/viewselfcert.php', ['userid' => $userid]);
                $html_results .= '<td><a href="'. $url->out(false) .'">View</a></td>';

                $html_results .= '</tr>';
            }
        }
        $html_results .= '</tbody></table>';
        $html = str_replace('[SEARCH-RESULT]', $html_results, $html);
    }
}

echo $OUTPUT->header();

echo $html;

echo $OUTPUT->footer();