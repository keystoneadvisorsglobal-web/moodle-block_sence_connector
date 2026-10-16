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
 * Site administration settings for block_sence_connector.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'block_sence_connector/header_general',
        get_string('settings_header_general', 'block_sence_connector'),
        get_string('settings_header_general_desc', 'block_sence_connector')
    ));

    // RUT OTEC
    $settings->add(new admin_setting_configtext(
        'block_sence_connector/rutotec',
        get_string('rutotec', 'block_sence_connector'),
        get_string('rutotec_desc', 'block_sence_connector'),
        '',
        PARAM_TEXT
    ));

    // Token OTEC (secret/password field for security)
    $settings->add(new admin_setting_configpasswordunmask(
        'block_sence_connector/token',
        get_string('token', 'block_sence_connector'),
        get_string('token_desc', 'block_sence_connector'),
        ''
    ));

    // ID Sistema
    $settings->add(new admin_setting_configtext(
        'block_sence_connector/idsistema',
        get_string('idsistema', 'block_sence_connector'),
        get_string('idsistema_desc', 'block_sence_connector'),
        '1350',
        PARAM_INT
    ));

    // Entorno de conexión
    $environments = [
        'test' => get_string('env_test', 'block_sence_connector'),
        'prod' => get_string('env_prod', 'block_sence_connector'),
    ];
    $settings->add(new admin_setting_configselect(
        'block_sence_connector/environment',
        get_string('environment', 'block_sence_connector'),
        get_string('environment_desc', 'block_sence_connector'),
        'test',
        $environments
    ));

    // Campo de perfil para el RUN del alumno
    $userfields = [
        'idnumber' => 'idnumber (Número de ID estándar)',
        'username' => 'username (Nombre de usuario)',
    ];
    $settings->add(new admin_setting_configselect(
        'block_sence_connector/runmapping',
        get_string('runmapping', 'block_sence_connector'),
        get_string('runmapping_desc', 'block_sence_connector'),
        'idnumber',
        $userfields
    ));

    // Ventana máxima de inactividad pasiva (minutos)
    $settings->add(new admin_setting_configtext(
        'block_sence_connector/inactivity_timeout',
        get_string('inactivity_timeout', 'block_sence_connector'),
        get_string('inactivity_timeout_desc', 'block_sence_connector'),
        '15',
        PARAM_INT
    ));
}
