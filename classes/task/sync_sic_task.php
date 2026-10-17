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

namespace block_sence_connector\task;

defined('MOODLE_INTERNAL') || die();

use block_sence_connector\util\sence_helper;

/**
 * Scheduled task to synchronize LMS progress with SENCE SIC API via Gestor Intermedio.
 * Group-as-Action-ID Architecture.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_sic_task extends \core\task\scheduled_task {

    public function get_name() {
        return 'Sincronización Automática con SENCE Gestor Intermedio (SIC)';
    }

    public function execute() {
        global $DB;

        $rutotec = get_config('block_sence_connector', 'rutotec');
        $token = get_config('block_sence_connector', 'token');
        $idsistema = (int)(get_config('block_sence_connector', 'idsistema') ?: 1350);

        if (empty($rutotec) || empty($token)) {
            mtrace("SENCE SIC Sync: Credenciales OTEC incompletas. Abortando sincronización.");
            return;
        }

        $courses = $DB->get_records_sql("SELECT DISTINCT courseid FROM {block_sence_courses} WHERE enabled = 1");
        if (empty($courses)) {
            mtrace("SENCE SIC Sync: No hay cursos con SENCE habilitado.");
            return;
        }

        foreach ($courses as $c) {
            self::sync_course_by_groups((int)$c->courseid, 0, $rutotec, $token, $idsistema);
        }
    }

    /**
     * Synchronize students grouped by their Moodle Group (SENCE ID de Acción).
     *
     * @param int $courseid
     * @param int $filter_groupid
     * @param string $rutotec
     * @param string $token
     * @param int $idsistema
     */
    public static function sync_course_by_groups(int $courseid, int $filter_groupid, string $rutotec, string $token, int $idsistema) {
        global $DB;

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return;
        }

        $all_enrolled = get_enrolled_users($context, 'block/sence_connector:view');
        if (empty($all_enrolled)) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $courseid]);
        $start_date = !empty($course->startdate) ? date('Y-m-d H:i:s', $course->startdate) : date('Y-m-d 00:00:00');
        $end_date = !empty($course->enddate) ? date('Y-m-d H:i:s', $course->enddate) : date('Y-m-d 23:59:59', strtotime('+1 year'));

        // Determine targets: specific group or all groups present
        $groups_to_sync = [];
        if ($filter_groupid > 0) {
            $g = $DB->get_record('groups', ['id' => $filter_groupid]);
            if ($g) $groups_to_sync[$g->id] = $g;
        } else {
            $groups_to_sync = groups_get_all_groups($courseid);
        }

        if (empty($groups_to_sync)) {
            // No groups defined, sync whole course as default action
            self::dispatch_action_payload($courseid, $all_enrolled, "CURSO-{$courseid}", $start_date, $end_date, $rutotec, $token, $idsistema);
            return;
        }

        foreach ($groups_to_sync as $grp) {
            $member_ids = $DB->get_fieldset_select('groups_members', 'userid', 'groupid = :gid', ['gid' => $grp->id]);
            $group_students = [];
            foreach ($all_enrolled as $u) {
                if (in_array($u->id, $member_ids)) {
                    $group_students[] = $u;
                }
            }

            if (!empty($group_students)) {
                // The Action ID is the Group Name!
                $action_id = trim($grp->name);
                self::dispatch_action_payload($courseid, $group_students, $action_id, $start_date, $end_date, $rutotec, $token, $idsistema);
            }
        }
    }

    private static function dispatch_action_payload(int $courseid, array $students, string $action_id, string $start_date, string $end_date, string $rutotec, string $token, int $idsistema) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid]);
        $lista_alumnos = [];

        foreach ($students as $student) {
            $raw_run = sence_helper::get_user_run($student);
            list($rut, $dv) = sence_helper::split_run($raw_run);
            if (empty($rut)) {
                continue;
            }

            $total_seconds = (int)$DB->get_field_sql(
                "SELECT SUM(duration) FROM {block_sence_sessions} WHERE courseid = :courseid AND userid = :userid AND status = 'CLOSED'",
                ['courseid' => $courseid, 'userid' => $student->id]
            ) ?: 0;

            $completion = new \completion_info($course);
            $percentage = 0;
            if ($completion->is_enabled()) {
                $activities = $completion->get_activities();
                $total_act = count($activities);
                $completed_act = 0;
                foreach ($activities as $act) {
                    $data = $completion->get_data($act, false, $student->id);
                    if ($data->completionstate == COMPLETION_COMPLETE || $data->completionstate == COMPLETION_COMPLETE_PASS) {
                        $completed_act++;
                    }
                }
                if ($total_act > 0) {
                    $percentage = round(($completed_act / $total_act) * 100);
                }
            }

            $lista_alumnos[] = [
                'rutAlumno' => (string)$rut,
                'dvAlumno' => (string)$dv,
                'tiempoConectividad' => $total_seconds,
                'porcentajeAvance' => (int)$percentage,
                'estado' => 1,
                'fechaInicio' => $start_date,
                'fechaFin' => $end_date,
                'listaModulos' => [
                    [
                        'codigoModulo' => "MOD-1-{$courseid}",
                        'tiempoConectividad' => $total_seconds,
                        'porcentajeAvance' => (int)$percentage,
                        'estado' => 1,
                        'fechaInicio' => date('Y-m-d', strtotime($start_date)),
                        'fechaFin' => date('Y-m-d', strtotime($end_date)),
                        'listaActividades' => [
                            ['codigoActividad' => 'Contenidos y Evaluaciones Plataforma']
                        ]
                    ]
                ]
            ];
        }

        if (empty($lista_alumnos)) {
            return;
        }

        $payload = [
            'rutOtec' => $rutotec,
            'idSistema' => $idsistema,
            'token' => $token,
            'codigoOferta' => $action_id,
            'codigoGrupo' => '1',
            'codigoEnvio' => sprintf('%05d', time() % 100000),
            'listaAlumnos' => $lista_alumnos,
        ];

        $json_payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $url = sence_helper::get_api_avance_endpoint();

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_payload)
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $log = (object)[
            'courseid' => $courseid,
            'statuscode' => $http_code ?: 0,
            'payload' => $json_payload,
            'response' => $response ?: '',
            'success' => ($http_code >= 200 && $http_code < 300) ? 1 : 0,
            'timecreated' => time(),
        ];
        $DB->insert_record('block_sence_synclog', $log);

        mtrace("SENCE SIC Sync: Acción {$action_id} (" . count($lista_alumnos) . " alumnos) enviada. HTTP Code: {$http_code}");
    }
}
