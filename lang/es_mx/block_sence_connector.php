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
 * Spanish language strings for block_sence_connector.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors (https://www.keystoneadvisors.cl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Conector SENCE Keystone Advisors';
$string['sence_connector:addinstance'] = 'Añadir un nuevo bloque Conector SENCE Keystone Advisors';
$string['sence_connector:myaddinstance'] = 'Añadir un nuevo bloque Conector SENCE Keystone Advisors al Área personal';
$string['sence_connector:view'] = 'Ver bloque Conector SENCE Keystone Advisors';
$string['sence_connector:viewreports'] = 'Ver informes de asistencia y sincronización SENCE';
$string['sence_connector:manage'] = 'Gestionar configuración SENCE del curso';

// Información institucional de Keystone Advisors
$string['keystone_footer_info'] = 'Plugin Gratuito desarrollado y mantenido por <a href="https://www.keystoneadvisors.cl" target="_blank" rel="noopener"><strong>Keystone Advisors</strong></a> | Avenida Apoquindo 6410, Oficina 605, Las Condes, Santiago.';
$string['keystone_badge_free'] = 'Plugin Oficial Gratuito';

// Ajustes del Sitio
$string['settings_header_general'] = 'Configuración General del OTEC';
$string['settings_header_general_desc'] = 'Credenciales oficiales de la entidad de capacitación autorizada ante SENCE.<br><small class="text-muted">Desarrollado de forma gratuita por <strong>Keystone Advisors</strong> (<a href="https://www.keystoneadvisors.cl" target="_blank">www.keystoneadvisors.cl</a> - Av. Apoquindo 6410, Of. 605).</small>';
$string['rutotec'] = 'RUT del OTEC';
$string['rutotec_desc'] = 'RUT de la empresa capacitadora con guión y dígito verificador (ej: 76111222-3).';
$string['token'] = 'Token OTEC SENCE';
$string['token_desc'] = 'Token alfanumérico de 36 caracteres generado en el portal emisor RTS: <a href="https://sistemas.sence.cl/rts" target="_blank" rel="noopener">https://sistemas.sence.cl/rts</a>.';
$string['idsistema'] = 'Identificador de Sistema (idSistema)';
$string['idsistema_desc'] = 'ID de sistema del Gestor Intermedio. Por defecto 1350 según instructivo técnico v2.0.';
$string['environment'] = 'Ambiente de Conexión';
$string['environment_desc'] = 'Selecciona Pruebas (rcetest) para homologación o Producción para reporte oficial.';
$string['env_test'] = 'Pruebas / Sandbox (rcetest)';
$string['env_prod'] = 'Producción (SENCE Oficial)';
$string['runmapping'] = 'Campo de Perfil para RUN';
$string['runmapping_desc'] = 'Campo de perfil de Moodle donde se registra el RUN del participante (por defecto Número de ID).';
$string['inactivity_timeout'] = 'Ventana Máxima de Inactividad (minutos)';
$string['inactivity_timeout_desc'] = 'Tiempo de inactividad a partir del cual se pausa el cómputo de asistencia (política antifraude).';

// Ajustes del Curso
$string['course_settings_title'] = 'Configuración SENCE del Curso - Keystone Advisors';
$string['enabled'] = 'Habilitar SENCE en este curso';
$string['codsence'] = 'Código SENCE de la Acción';
$string['codsence_desc'] = 'Código oficial del curso en SOLICURSO (usa -1 para pruebas de homologación).';
$string['codigooferta'] = 'Código de Oferta (SIC)';
$string['codigooferta_desc'] = 'Código de oferta asignado en el SIC (ej: CAP-15-01-13-0446).';
$string['codigogrupo'] = 'Código de Grupo (SIC)';
$string['lineacapacitacion'] = 'Línea de Capacitación';
$string['horastotales'] = 'Horas Cronológicas Totales';
$string['asistenciaminima'] = 'Asistencia Mínima Requerida (%)';
$string['notaminima'] = 'Calificación Mínima de Aprobación';

// Vistas del Bloque
$string['block_title'] = 'Asistencia SENCE';
$string['btn_login_sence'] = 'Registrar Asistencia con ClaveÚnica';
$string['btn_logout_sence'] = 'Cerrar Sesión de Asistencia SENCE';
$string['session_active'] = 'Sesión de Asistencia ACTIVA';
$string['session_inactive'] = 'Sesión de Asistencia No Iniciada';
$string['session_started_at'] = 'Asistencia validada a las: {$a}';
$string['hours_connected'] = 'Tiempo computado: {$a->hours}h / {$a->total}h';
$string['progress_percent'] = 'Avance Contenidos: {$a}%';
$string['attendance_percent'] = 'Avance Asistencia: {$a}%';
$string['view_reports'] = 'Ver Libro de Asistencia SENCE';
$string['sync_now'] = 'Sincronizar ahora con SIC';

// Informes
$string['report_title'] = 'Libro de Asistencia y Bitácora SENCE - Keystone Advisors';
$string['kpi_enrolled'] = 'Alumnos Inscritos';
$string['kpi_active_today'] = 'Conectados Hoy';
$string['kpi_avg_progress'] = 'Promedio de Avance';
$string['kpi_last_sync'] = 'Última Sincronización SIC';
$string['col_student'] = 'Participante';
$string['col_run'] = 'RUN / RUT';
$string['col_status'] = 'Estado Sesión';
$string['col_hours'] = 'Horas Efectivas';
$string['col_progress'] = '% Avance';
$string['col_grade'] = 'Calificación';
$string['col_sence_status'] = 'Estado SENCE';
$string['status_enrolled'] = 'Cursando';
$string['status_approved'] = 'Aprobado';
$string['status_failed'] = 'Reprobado';
$string['export_excel'] = 'Descargar Planilla Excel';

// Privacidad GDPR
$string['privacy:metadata:sessions'] = 'Almacena registros de sesiones de asistencia validadas ante SENCE ClaveÚnica.';
$string['privacy:metadata:sessions:userid'] = 'ID del usuario en Moodle.';
$string['privacy:metadata:sessions:runalumno'] = 'RUN chileno del participante.';
$string['privacy:metadata:sessions:idsesionsence'] = 'Identificador único de sesión otorgado por SENCE.';
$string['privacy:metadata:sessions:timestart'] = 'Marca temporal de inicio de sesión.';
$string['privacy:metadata:sessions:timeend'] = 'Marca temporal de cierre de sesión.';
$string['privacy:metadata:sessions:duration'] = 'Segundos efectivos de asistencia computados.';
$string['privacy:metadata:sessions:ipaddress'] = 'Dirección IP del participante en la validación.';
