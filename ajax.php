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
 * Version details.
 *
 * @package    local_self_cert_mgr
 * @author     Hieu Van <hieu.van@cosector.com>
 * @copyright  ULCC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once((__DIR__).'/../../config.php');

global $CFG, $DB, $USER, $PAGE;

$context = context_system::instance();
$PAGE->set_context($context);

require_login();

if(!has_capability('local/self_cert_mgr:edit', $context)) {
    echo $OUTPUT->header();

    echo "<p>You don't have the permission to view this page.</p>";

    echo $OUTPUT->footer();
    exit;
}

$action = required_param('action', PARAM_TEXT);

if (confirm_sesskey()) {

    switch ($action) {

        case 'removeselfcert':
            $selfcertid = required_param('selfcertid', PARAM_INT);
            $DB->delete_records('coursework_mitigations', ['id' => $selfcertid]);
            echo 'success';
            break;
    }
    exit;
}