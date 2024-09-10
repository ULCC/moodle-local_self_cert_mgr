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

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$title = get_string('pluginname', 'local_self_cert_mgr');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($CFG->wwwroot . '/viewselfcert.php');

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

}