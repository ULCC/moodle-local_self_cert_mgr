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
require_once($CFG->dirroot . '/local/self_cert_mgr/lib.php');

use mod_coursework\models\coursework;
use mod_coursework\models\user;
use mod_coursework\models\submission;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$title = get_string('pluginname', 'local_self_cert_mgr');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($CFG->wwwroot . '/viewselfcert.php');
$PAGE->requires->jquery();
$PAGE->requires->yui_module('moodle-core-notification', 'notification_init');

if(!has_capability('local/self_cert_mgr:edit', $context)) {
    echo $OUTPUT->header();

    echo "<p>You don't have the permission to view this page.</p>";

    echo $OUTPUT->footer();
    exit;
}

global $DB;

$dbman = $DB->get_manager();

$userid = required_param('userid', PARAM_INT);

if (!empty($userid)) {
    $user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
    $maxselfcert = get_config('block_rhb', 'selfcertmax');
    $selfcert_available = local_self_cert_mgr_no_self_cert_available($userid);
    $selfcert_used = (empty($selfcert_available) ? '' : $maxselfcert - $selfcert_available);
    if ($dbman->table_exists('local_user_info_ext')) {
        $candno = $DB->get_field_sql("SELECT value FROM {local_user_info_ext} WHERE type = 'candno' AND userid = :userid LIMIT 1", ['userid' => $userid]);
        $sprno = $DB->get_field_sql("SELECT value FROM {local_user_info_ext} WHERE type = 'spr' AND userid = :userid LIMIT 1", ['userid' => $userid]);
    }
    $html = '
        <div class="container warppercontent">
            <div class="row">
                <div class="col-sm-12">
                    <p><b>Self-Certifications</b></p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4">
                    <span><b>Student Name:</b></span> <span>'. $user->firstname . ' ' . $user->lastname .'</span>
                </div>
                <div class="col-sm-4">
                    <span><b>Candidate Number:</b></span> <span>'. (empty($candno) ? '' : $candno) .'</span>
                </div>
                <div class="col-sm-4">
                    <span><b>SPR Number:</b></span> <span>'. (empty($sprno) ? '' : $sprno) .'</span>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <p><br><span><b>Allocation: </b></span> <span>'. $maxselfcert .'</span></p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <p><span><b>Self-Certifications Used: </b></span> <span>'. $selfcert_used .'</span></p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <p><span><b>Total Left: </b></span> <span>'. (empty($selfcert_available) ? '' : $selfcert_available) .'</span></p>
                </div>
            </div>
            [WRAPPER-RESULT]
            <div class="row">
                <div class="col-sm-12">
                    <p><br><button name="btback" onclick="history.back()">Back to Search</button></p>
                </div>
            </div>
        </div>

        <style type="text/css">
            .warppercontent { min-height: 600px; border: 1px solid; padding-top: 25px; }
            .wrappersearchfield { border: 1px solid #000; padding: 30px 0; }
            .wrappersearchfield + div { margin-top: 40px; border: 1px solid; padding: 30px 0; }
            .tableresult th, .tableresult td { padding: 7px 20px; }
            button[name=btback] { padding: 8px 10px; border-radius: 6px; border: 1px solid #8f959e; }
            i.icon { font-size: 1.4rem; }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $("body").on("click", "a.button_remove", function(e) {
                    var selfcertid = $(this).data("selfcertid");
                    var confirm = new M.core.confirm({
                        title: "Confirm",
                        question: "Are you sure to delete this Self-Cert",
                        yesLabel: "Yes",
                        noLabel: "No"
                    });
                    confirm.on("complete-yes", function () {
                        confirm.hide();
                        confirm.destroy();

                        $.ajax({
                            type: "POST",
                            url: M.cfg.wwwroot + "/local/self_cert_mgr/ajax.php",
                            dataType: "html",
                            data: { action: "removeselfcert", selfcertid: selfcertid, sesskey: M.cfg.sesskey },
                            beforeSend: function () {
                            },
                            success: function (response) {
                                if(response === "success") {
                                    $("a[data-selfcertid=" + selfcertid + "]").closest("tr").remove();
                                }
                            },
                            error: function (data, response) {
                                console.log(response);
                            },
                            complete: function () {
                            }
                        });
                    }, self);

                    confirm.show();
                });
            });
            function notification_init() {
                // Init stuff ...
            }
        </script>
    ';

    if (!empty($selfcert_used)) {

        $sql = "SELECT id, courseworkid, timecreated
                FROM {coursework_mitigations}
                WHERE type = 'extension'
                AND selfcert = 1
                AND allocatableid = :userid";
        $records = $DB->get_records_sql($sql, ['userid' => $userid]);

        if (!empty($records)) {
            $html_result = '
                <div class="row">
                    <div class="col-sm-12">
                        <p>Assessment(s) where a self-certification has been used:</p>
                    </div>
                    <div class="col-sm-12">
                        <table cellpadding="0" cellspacing="0" border="1" rules="All" width="100%" class="tableresult">
                            <thead>
                            <th>Course Name</th>
                            <th>Assessment Name</th>
                            <th>Date Assessment Submitted</th>
                            <th>Date of Self Cert</th>
                            <th>Remove Self-Cert</th>
                            </thead>
                            <tbody>
                            [RECORDS]
                            </tbody>
                        </table>
                    </div>
                </div>
            ';

            $html_records = '';
            foreach ($records as $record) {
                $coursework = coursework::find($record->courseworkid);
                $submission = $coursework->get_user_submission(user::find($userid));
                $html_records .= '<tr>';
                $html_records .= '<td>'. $DB->get_field('course', 'fullname', ['id' => $coursework->course]) .'</td>';
                $html_records .= '<td>'. $coursework->name .'</td>';
                $html_records .= '<td>'. (empty($submission) ? '' : date('d/m/Y', $submission->timesubmitted)) .'</td>';
                $html_records .= '<td>'. date('d/m/Y', $record->timecreated) .'</td>';
                $html_records .= '<td><a href="javascript:;" data-selfcertid="'. $record->id .'" class="button_remove"><i class="icon fa fa-remove fa-fw " aria-hidden="true"></i></a></td>';
                $html_records .= '</tr>';
            }

            $html_result = str_replace('[RECORDS]', $html_records, $html_result);

            $html = str_replace('[WRAPPER-RESULT]', $html_result, $html);
        }
    }
    else {
        $html = str_replace('[WRAPPER-RESULT]', '', $html);
    }

    echo $OUTPUT->header();

    echo $html;

    echo $OUTPUT->footer();
}
