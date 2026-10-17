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

namespace block_sence_connector\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper utility class for SENCE integration.
 * Group-as-Action-ID Architecture:
 * The student's assigned Moodle Group represents the SENCE ID de Acción.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sence_helper {

    public static function get_rce_base_url(): string {
        $env = get_config('block_sence_connector', 'environment');
        if ($env === 'prod') {
            return 'https://sistemas.sence.cl/rce/Registro/';
        }
        return 'https://sistemas.sence.cl/rcetest/Registro/';
    }

    public static function get_api_avance_endpoint(): string {
        return 'https://auladigital.sence.cl/gestor/API/avance-sic/enviarAvance';
    }

    public static function get_api_historial_endpoint(): string {
        return 'https://auladigital.sence.cl/gestor/API/avance-sic/historialEnvios';
    }

    public static function clean_run(string $raw): string {
        $clean = preg_replace('/[^0-9kK]/', '', $raw);
        if (strlen($clean) < 2) {
            return $clean;
        }
        $dv = strtoupper(substr($clean, -1));
        $rut = substr($clean, 0, -1);
        return $rut . '-' . $dv;
    }

    public static function split_run(string $run): array {
        $formatted = self::clean_run($run);
        $parts = explode('-', $formatted);
        if (count($parts) === 2) {
            return [$parts[0], $parts[1]];
        }
        return [$run, ''];
    }

    public static function get_user_run(object $user): string {
        $mapping = get_config('block_sence_connector', 'runmapping') ?: 'idnumber';
        $val = $user->$mapping ?? '';
        return self::clean_run((string)$val);
    }

    /**
     * Resolve the Action ID (CodigoOferta) and parameters for a student in a course
     * directly from their assigned Moodle Group(s).
     *
     * @param int $courseid
     * @param int $userid
     * @return object
     */
    public static function get_user_action_config(int $courseid, int $userid) {
        global $DB;

        // Base course settings defaults
        $base = $DB->get_record('block_sence_courses', ['courseid' => $courseid, 'groupid' => 0]);
        if (!$base) {
            $base = $DB->get_record('block_sence_courses', ['courseid' => $courseid], '*', IGNORE_MULTIPLE);
        }

        $linea = $base ? (int)$base->lineacapacitacion : 3;
        $horas = $base ? (float)$base->horastotales : 20.0;
        $asistencia = $base ? (float)$base->asistenciaminima : 75.0;
        $codsence = $base ? $base->codsence : '-1';

        // 1. Check user groups in this course
        $user_groups = groups_get_user_groups($courseid, $userid);
        if (!empty($user_groups[0])) {
            foreach ($user_groups[0] as $gid) {
                // Check if this group has custom parameters configured
                $g_config = $DB->get_record('block_sence_courses', ['courseid' => $courseid, 'groupid' => $gid]);
                $grp = $DB->get_record('groups', ['id' => $gid]);

                // The Action ID is the group's configured codigooferta OR the Group Name itself!
                $oferta = '';
                if ($g_config && !empty($g_config->codigooferta)) {
                    $oferta = $g_config->codigooferta;
                } else if ($grp && !empty($grp->name)) {
                    $oferta = $grp->name;
                }

                if (!empty($oferta)) {
                    return (object)[
                        'courseid' => $courseid,
                        'groupid' => $gid,
                        'groupname' => $grp ? $grp->name : '',
                        'codigooferta' => $oferta,
                        'codsence' => ($g_config && !empty($g_config->codsence)) ? $g_config->codsence : $codsence,
                        'codigogrupo' => ($g_config && !empty($g_config->codigogrupo)) ? $g_config->codigogrupo : '1',
                        'lineacapacitacion' => $g_config ? (int)$g_config->lineacapacitacion : $linea,
                        'horastotales' => $g_config ? (float)$g_config->horastotales : $horas,
                        'asistenciaminima' => $g_config ? (float)$g_config->asistenciaminima : $asistencia,
                        'enabled' => 1
                    ];
                }
            }
        }

        // 2. Default fallback if student is not assigned to any group
        return (object)[
            'courseid' => $courseid,
            'groupid' => 0,
            'groupname' => 'Sin Grupo (General)',
            'codigooferta' => $base ? $base->codigooferta : "CURSO-{$courseid}",
            'codsence' => $codsence,
            'codigogrupo' => '1',
            'lineacapacitacion' => $linea,
            'horastotales' => $horas,
            'asistenciaminima' => $asistencia,
            'enabled' => $base ? $base->enabled : 1
        ];
    }

    public static function translate_error_code(int $code): string {
        $errors = [
            200 => 'El POST tiene uno o más parámetros obligatorios sin información o mal escritos.',
            201 => 'La URL de Retoma y/o URL de Error no tienen información.',
            202 => 'La URL de Retoma tiene un formato incorrecto.',
            203 => 'La URL de Error tiene un formato incorrecto.',
            204 => 'El Código SENCE tiene menos de 10 caracteres o no es un código válido.',
            205 => 'El Código Curso tiene menos de 7 caracteres o no es un código válido.',
            206 => 'La Línea de Capacitación es incorrecta.',
            207 => 'El RUN Alumno tiene formato incorrecto o dígito verificador inválido.',
            208 => 'El RUN Alumno no está autorizado para realizar este curso en SENCE.',
            209 => 'El RUT OTEC tiene formato incorrecto o dígito verificador incorrecto (debe incluir guión y dígito verificador, ej: 12345678-9).',
            211 => 'El Token no pertenece al OTEC.',
            212 => 'El Token no está vigente.',
            300 => 'Error interno de SENCE no clasificado.',
            301 => 'No se pudo registrar el ingreso o cierre de sesión (Línea o Código de curso incorrecto).',
            302 => 'No se pudo validar la información del Organismo en SENCE.',
            303 => 'El Token no existe o su formato es incorrecto.',
            304 => 'No se pudieron verificar los datos enviados en SENCE.',
            305 => 'No se pudo registrar la información en los servidores de SENCE.',
            306 => 'El Código Curso no corresponde al Código SENCE informado.',
            307 => 'El Código Curso no tiene modalidad E-learning.',
            308 => 'El Código Curso no corresponde al RUT OTEC.',
            309 => 'Las fechas de ejecución comunicadas para el curso no corresponden a la fecha actual.',
            310 => 'El Código Curso está en estado Terminado o Anulado en SENCE.',
            311 => 'El RUN ingresado en el login no corresponde con el RUN del alumno informado.',
            312 => 'No se pudo completar la autenticación con ClaveÚnica.',
        ];
        return $errors[$code] ?? "Código de error SENCE {$code}.";
    }
}
