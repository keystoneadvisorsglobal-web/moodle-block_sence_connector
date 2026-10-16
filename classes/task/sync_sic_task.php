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
 * Scheduled task to synchronize student attendance and progress to SENCE SIC via API Gestor Intermedio.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_sic_task extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('pluginname', 'block_sence_connector') . ' - Sincronización API Gestor Intermedio';
    }

    public function execute() {
        global $DB;

        $rutotec = get_config('block_sence_connector', 'rutotec');
        $token = get_config('block_sence_connector', 'token');
        $idsistema = (int)(get_config('block_sence_connector', 'idsistema') ?: 1350);

        if (empty($rutotec) || empty($token)) {
            mtrace("SENCE Task: Configuración OTEC incompleta (RUT o Token faltante). Abortando.");
            return;
        }

        $courses = $DB->get_records('block_sence_courses', ['enabled' => 1]);
        if (empty($courses)) {
            mtrace("SENCE Task: No hay cursos SENCE activos.");
            return;
        }

        foreach ($courses as $scourse) {
            mtrace("SENCE Task: Procesando Curso ID {$scourse->courseid} (Oferta: {$scourse->codigooferta})...");
            self::sync_course($scourse, $rutotec, $token, $idsistema);
        }
    }

    /**
     * Build and send payload for one course.
     *
     * @param object $scourse
     * @param string $rutotec
     * @param string $token
     * @param int $idsistema
     */
    public static function sync_course(object $scourse, string $rutotec, string $token, int $idsistema) {
        global $DB;

        $context = \context_course::instance($scourse->courseid);
        $enrolled_users = get_enrolled_users($context, 'block/sence_connector:view');

        $lista_alumnos = [];

        foreach ($enrolled_users as $user) {
            $run = sence_helper::get_user_run($user);
            if (empty($run)) {
                continue;
            }
            list($rut, $dv) = sence_helper::split_run($run);

            // Compute connection time in seconds from validated SENCE sessions
            $total_seconds = (int)$DB->get_field_sql(
                "SELECT SUM(duration) FROM {block_sence_sessions} WHERE courseid = :courseid AND userid = :userid AND status = 'CLOSED'",
                ['courseid' => $scourse->courseid, 'userid' => $user->id]
            ) ?: 0;

            // Compute course completion percentage
            $cinfo = new \completion_info(get_course($scourse->courseid));
            $is_complete = $cinfo->is_course_complete($user->id);
            $progress_percent = $is_complete ? 100.0 : 0.0;

            // State calculation: 1: Cursando, 2: Aprobado, 3: Reprobado
            $estado = 1;
            $horas_requeridas_seg = ($scourse->horastotales * 3600) * ($scourse->asistenciaminima / 100);
            if ($total_seconds >= $horas_requeridas_seg && $progress_percent >= 75.0) {
                $estado = 2; // Aprobado
            }

            // Módulos
            $modulos = [
                [
                    'codigoModulo' => "MOD-{$scourse->codigooferta}-1",
                    'tiempoConectividad' => $total_seconds,
                    'porcentajeAvance' => $progress_percent,
                    'estado' => $estado,
                    'fechaInicio' => date('Y-m-d', $scourse->timecreated ?: time()),
                    'fechaFin' => date('Y-m-d', strtotime('+3 months', $scourse->timecreated ?: time())),
                    'listaActividades' => [
                        ['codigoActividad' => 'Actividades Generales del Curso']
                    ]
                ]
            ];

            $lista_alumnos[] = [
                'rutAlumno' => $rut,
                'dvAlumno' => $dv,
                'tiempoConectividad' => $total_seconds,
                'porcentajeAvance' => $progress_percent,
                'estado' => $estado,
                'fechaInicio' => date('Y-m-d 00:00:00', $scourse->timecreated ?: time()),
                'fechaFin' => date('Y-m-d 23:59:59', strtotime('+3 months', $scourse->timecreated ?: time())),
                'listaModulos' => $modulos
            ];
        }

        if (empty($lista_alumnos)) {
            mtrace("SENCE Task: Sin alumnos con RUN válido en el curso {$scourse->courseid}.");
            return;
        }

        $payload = [
            'rutOtec' => $rutotec,
            'idSistema' => $idsistema,
            'token' => $token,
            'codigoOferta' => $scourse->codigooferta,
            'codigoGrupo' => $scourse->codigogrupo,
            'codigoEnvio' => sprintf("%05d", time() % 100000),
            'listaAlumnos' => $lista_alumnos
        ];

        // Send payload to Gestor Intermedio via cURL
        $url = sence_helper::get_api_avance_endpoint();
        $json_payload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $curl = new \curl();
        $options = [
            'CURLOPT_HTTPHEADER' => ['Content-Type: application/json'],
            'CURLOPT_TIMEOUT' => 60,
        ];
        $response = $curl->post($url, $json_payload, $options);
        $statuscode = $curl->get_info()['http_code'] ?? 0;

        // Save into block_sence_synclog
        $log = (object) [
            'courseid' => $scourse->courseid,
            'statuscode' => (int)$statuscode,
            'payload' => $json_payload,
            'response' => (string)$response,
            'success' => ($statuscode >= 200 && $statuscode < 300) ? 1 : 0,
            'timecreated' => time(),
        ];
        $DB->insert_record('block_sence_synclog', $log);

        mtrace("SENCE Task: Envío finalizado para Curso {$scourse->courseid}. HTTP Status: {$statuscode}");
    }
}
