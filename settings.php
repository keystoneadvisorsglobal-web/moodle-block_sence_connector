<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    block_sence_connector
// @copyright  2026 Keystone Advisors SpA (https://www.keystoneadvisors.cl)
// @license    Proprietary / Keystone Advisors Freeware License (All Rights Reserved)
//
// Queda prohibida la reproducción, modificación, copia o comercialización total
// o parcial de este software sin el consentimiento expreso y por escrito de Keystone Advisors.

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Banner Legal de Propiedad Intelectual y Derechos Reservados
    $legal_notice = html_writer::tag('div',
        html_writer::tag('div',
            html_writer::tag('strong', '© 2026 Keystone Advisors SpA. Todos los derechos reservados.') . '<br>' .
            html_writer::tag('span', 'Este plugin es una solución oficial y gratuita de Keystone Advisors para Organismos Técnicos de Capacitación (OTEC). El uso de este software está sujeto a los términos de la Licencia Propietaria de Keystone Advisors. ') .
            html_writer::tag('strong', 'Queda estrictamente prohibida la copia, clonación, ingeniería inversa, reutilización no autorizada del código o redistribución comercial sin autorización expresa por escrito.') . '<br>' .
            html_writer::tag('small', 'Contacto legal, licencias y solicitudes de desarrollo a medida: ') .
            html_writer::link('mailto:contacto@keystoneadvisors.cl', 'contacto@keystoneadvisors.cl', ['style' => 'font-weight: bold; color: #1a4253; text-decoration: underline;']) . ' | Av. Apoquindo 6410, Of. 605, Las Condes, Santiago.',
            ['class' => 'alert alert-info py-3 px-4 shadow-sm mb-4', 'style' => 'background-color: #f0f7fa; border-left: 5px solid #1a4253; color: #1e293b; font-size: 0.90rem; border-radius: 8px; line-height: 1.5;']
        )
    );

    $settings->add(new admin_setting_heading(
        'block_sence_connector/legal_notice_header',
        'Propiedad Intelectual y Derechos Reservados - Keystone Advisors',
        $legal_notice
    ));

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

    // Habilitar sandbox de simulación para pruebas QA
    $settings->add(new admin_setting_configcheckbox(
        'block_sence_connector/enable_simulation_mode',
        'Habilitar Sandbox de Simulación (Modo QA / Pruebas)',
        'Permite a los usuarios y administradores simular un inicio o cierre de sesión SENCE exitoso sin pasar por ClaveÚnica real. Ideal para realizar auditorías internas y verificar la experiencia del alumno.',
        1
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

    // Mostrar ficha de datos del alumno en pantalla de login / ClaveÚnica
    $settings->add(new admin_setting_configcheckbox(
        'block_sence_connector/show_student_details',
        'Mostrar Ficha del Participante en Pantalla de Acceso',
        'Si está desactivado (recomendado), la pantalla de acceso a ClaveÚnica se mostrará limpia, sin la caja de datos personales del alumno (RUN, nombre y curso).',
        0
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
