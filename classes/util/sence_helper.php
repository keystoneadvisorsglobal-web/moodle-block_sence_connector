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
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sence_helper {

    /**
     * Get RCE base URL according to current environment setting.
     *
     * @return string
     */
    public static function get_rce_base_url(): string {
        $env = get_config('block_sence_connector', 'environment');
        if ($env === 'prod') {
            return 'https://sistemas.sence.cl/rce/Registro/';
        }
        return 'https://sistemas.sence.cl/rcetest/Registro/';
    }

    /**
     * Get API Gestor Intermedio endpoint.
     *
     * @return string
     */
    public static function get_api_avance_endpoint(): string {
        return 'https://auladigital.sence.cl/gestor/API/avance-sic/enviarAvance';
    }

    /**
     * Get API Gestor Intermedio historial endpoint.
     *
     * @return string
     */
    public static function get_api_historial_endpoint(): string {
        return 'https://auladigital.sence.cl/gestor/API/avance-sic/historialEnvios';
    }

    /**
     * Clean and format Chilean RUN (e.g. 12345678-9).
     *
     * @param string $raw
     * @return string
     */
    public static function clean_run(string $raw): string {
        $clean = preg_replace('/[^0-9kK]/', '', $raw);
        if (strlen($clean) < 2) {
            return $clean;
        }
        $dv = strtoupper(substr($clean, -1));
        $rut = substr($clean, 0, -1);
        return $rut . '-' . $dv;
    }

    /**
     * Separate Chilean RUN into body and verification digit.
     *
     * @param string $run
     * @return array [rut, dv]
     */
    public static function split_run(string $run): array {
        $formatted = self::clean_run($run);
        $parts = explode('-', $formatted);
        if (count($parts) === 2) {
            return [$parts[0], $parts[1]];
        }
        return [$run, ''];
    }

    /**
     * Get RUN for a given Moodle user according to configuration.
     *
     * @param object $user
     * @return string
     */
    public static function get_user_run(object $user): string {
        $mapping = get_config('block_sence_connector', 'runmapping') ?: 'idnumber';
        $val = $user->$mapping ?? '';
        return self::clean_run((string)$val);
    }

    /**
     * Translate SENCE GlosaError code into human readable description.
     *
     * @param int $code
     * @return string
     */
    public static function translate_error_code(int $code): string {
        $errors = [
            1 => 'Error de autenticación: Token OTEC no coincide o no está vigente.',
            2 => 'Datos incompletos o parámetros requeridos faltantes en la solicitud.',
            3 => 'El Código SENCE o Código de Curso no es válido para este OTEC.',
            4 => 'El RUN del participante no se encuentra registrado en el curso SENCE.',
            5 => 'Sesión expirada o fuera del rango horario establecido.',
            6 => 'Error en la validación de ClaveÚnica.',
            7 => 'El participante ya cuenta con una sesión activa en otro dispositivo.',
        ];
        return $errors[$code] ?? "Código de error SENCE no clasificado ({$code}).";
    }
}
