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
 * English language strings for block_sence_connector.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors (https://www.keystoneadvisors.cl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'SENCE Connector by Keystone Advisors';
$string['sence_connector:addinstance'] = 'Add a new SENCE Connector by Keystone Advisors block';
$string['sence_connector:myaddinstance'] = 'Add a new SENCE Connector by Keystone Advisors block to Dashboard';
$string['sence_connector:view'] = 'View SENCE Connector block';
$string['sence_connector:viewreports'] = 'View SENCE attendance and sync reports';
$string['sence_connector:manage'] = 'Manage SENCE course configuration';

// Institutional Keystone Advisors info
$string['keystone_footer_info'] = 'Free plugin developed and maintained by <a href="https://www.keystoneadvisors.cl" target="_blank" rel="noopener"><strong>Keystone Advisors</strong></a> | Avenida Apoquindo 6410, Office 605, Las Condes, Santiago, Chile.';
$string['keystone_badge_free'] = 'Official Free Plugin';

// Site Settings
$string['settings_header_general'] = 'General OTEC Configuration';
$string['settings_header_general_desc'] = 'Official credentials for the SENCE accredited training organization (OTEC).<br><small class="text-muted">Developed free of charge by <strong>Keystone Advisors</strong> (<a href="https://www.keystoneadvisors.cl" target="_blank">www.keystoneadvisors.cl</a> - Av. Apoquindo 6410, Of. 605).</small>';
$string['rutotec'] = 'OTEC RUT';
$string['rutotec_desc'] = 'Official institution RUT with dash and verification digit (e.g., 76111222-3).';
$string['token'] = 'SENCE OTEC Token';
$string['token_desc'] = '36-character alphanumeric token obtained via the official SENCE RTS portal: <a href="https://sistemas.sence.cl/rts" target="_blank" rel="noopener">https://sistemas.sence.cl/rts</a>.';
$string['idsistema'] = 'System Identifier (idSistema)';
$string['idsistema_desc'] = 'SENCE Gestor Intermedio System ID. Default is 1350 according to technical guidelines v2.0.';
$string['environment'] = 'Connection Environment';
$string['environment_desc'] = 'Select Testing (Sandbox) for qualification and staging, or Production for live SENCE reporting.';
$string['env_test'] = 'Testing / Sandbox (rcetest)';
$string['env_prod'] = 'Production (Official SENCE)';
$string['runmapping'] = 'User RUT Mapping Field';
$string['runmapping_desc'] = 'Select the user profile field where the Chilean RUN is stored (usually ID number).';
$string['inactivity_timeout'] = 'Max Inactivity Window (minutes)';
$string['inactivity_timeout_desc'] = 'Idle time threshold before pausing connectivity tracking (Anti-fraud policy).';

// Course Settings
$string['course_settings_title'] = 'SENCE Course Configuration - Keystone Advisors';
$string['enabled'] = 'Enable SENCE for this course';
$string['codsence'] = 'SENCE Action Code';
$string['codsence_desc'] = 'Official course code from SOLICURSO (use -1 for sandbox testing).';
$string['codigooferta'] = 'Offer Code (SIC)';
$string['codigooferta_desc'] = 'Course offering identifier registered in SENCE SIC (e.g., CAP-15-01-13-0446).';
$string['codigogrupo'] = 'Group Code (SIC)';
$string['lineacapacitacion'] = 'Training Line Code';
$string['horastotales'] = 'Total Chronological Hours';
$string['asistenciaminima'] = 'Minimum Attendance Requirement (%)';
$string['notaminima'] = 'Minimum Passing Grade';

// Block View
$string['block_title'] = 'SENCE Attendance';
$string['btn_login_sence'] = 'Register Attendance via ClaveÚnica';
$string['btn_logout_sence'] = 'Close SENCE Attendance Session';
$string['session_active'] = 'Attendance Session Active';
$string['session_inactive'] = 'Attendance Session Not Initiated';
$string['session_started_at'] = 'Session started at: {$a}';
$string['hours_connected'] = 'Tracked Connection: {$a->hours}h / {$a->total}h';
$string['progress_percent'] = 'Content Progress: {$a}%';
$string['attendance_percent'] = 'Attendance Progress: {$a}%';
$string['view_reports'] = 'View SENCE Attendance Book';
$string['sync_now'] = 'Sync with SIC Now';

// Reports
$string['report_title'] = 'SENCE Attendance Book & Audit Log - Keystone Advisors';
$string['kpi_enrolled'] = 'Enrolled Participants';
$string['kpi_active_today'] = 'Active Today';
$string['kpi_avg_progress'] = 'Average Progress';
$string['kpi_last_sync'] = 'Last SIC Synchronization';
$string['col_student'] = 'Student';
$string['col_run'] = 'RUN / RUT';
$string['col_status'] = 'Current Session';
$string['col_hours'] = 'Effective Hours';
$string['col_progress'] = '% Progress';
$string['col_grade'] = 'Grade';
$string['col_sence_status'] = 'SENCE Status';
$string['status_enrolled'] = 'Enrolled / In Progress';
$string['status_approved'] = 'Approved';
$string['status_failed'] = 'Failed';
$string['export_excel'] = 'Export to Excel';

// Privacy
$string['privacy:metadata:sessions'] = 'Stores participant attendance sessions validated through SENCE ClaveÚnica.';
$string['privacy:metadata:sessions:userid'] = 'The user ID.';
$string['privacy:metadata:sessions:runalumno'] = 'The Chilean RUN of the student.';
$string['privacy:metadata:sessions:idsesionsence'] = 'The official session ID issued by SENCE.';
$string['privacy:metadata:sessions:timestart'] = 'Session start timestamp.';
$string['privacy:metadata:sessions:timeend'] = 'Session end timestamp.';
$string['privacy:metadata:sessions:duration'] = 'Computed effective attendance seconds.';
$string['privacy:metadata:sessions:ipaddress'] = 'User IP address during SENCE validation.';
