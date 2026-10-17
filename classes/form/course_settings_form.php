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

namespace block_sence_connector\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Course SENCE configuration form with Multi-Company (Groups) support.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_settings_form extends \moodleform {

    protected function definition() {
        global $DB;
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $groupid = $this->_customdata['groupid'] ?? 0;

        $mform->addElement('header', 'sence_course_hdr', 'Configuración de Acción Formativa SENCE');

        // Group / Enterprise Selector
        $groups = groups_get_all_groups($courseid);
        $group_options = [0 => '— Acción General del Curso (Todos los alumnos sin grupo específico) —'];
        if (!empty($groups)) {
            foreach ($groups as $g) {
                $member_count = $DB->count_records('groups_members', ['groupid' => $g->id]);
                $group_options[$g->id] = "Grupo / Empresa: {$g->name} ({$member_count} alumnos)";
            }
        }
        $mform->addElement('select', 'groupid', 'Empresa / Grupo Asociado (ID Acción)', $group_options);
        $mform->setDefault('groupid', $groupid);
        $mform->addHelpButton('groupid', 'groupid_help', 'block_sence_connector');

        // Enable SENCE checkbox
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'block_sence_connector'));
        $mform->setDefault('enabled', 1);

        // CodSence
        $mform->addElement('text', 'codsence', get_string('codsence', 'block_sence_connector'));
        $mform->setType('codsence', PARAM_TEXT);
        $mform->addHelpButton('codsence', 'codsence', 'block_sence_connector');

        // CodigoOferta (SIC) / ID Acción SENCE
        $mform->addElement('text', 'codigooferta', 'ID Acción / Código de Oferta SIC', ['placeholder' => 'Ej: CAP-15-01-13-0446 o 1234567']);
        $mform->setType('codigooferta', PARAM_TEXT);
        $mform->addHelpButton('codigooferta', 'codigooferta', 'block_sence_connector');

        // CodigoGrupo
        $mform->addElement('text', 'codigogrupo', get_string('codigogrupo', 'block_sence_connector'));
        $mform->setType('codigogrupo', PARAM_TEXT);
        $mform->setDefault('codigogrupo', '1');

        // LineaCapacitacion
        $lineas_options = [
            3 => '3 - Franquicia Tributaria (Impulsa Personas)',
            1 => '1 - Programas Sociales / Becas Laborales',
            2 => '2 - Formación en el Trabajo / Transferencias',
            4 => '4 - Becas Fondo de Cesantía Solidario (FCS)',
            5 => '5 - Convenios Directos y Licitaciones Públicas',
        ];
        $mform->addElement('select', 'lineacapacitacion', get_string('lineacapacitacion', 'block_sence_connector'), $lineas_options);
        $mform->setDefault('lineacapacitacion', 3);

        // HorasTotales
        $mform->addElement('text', 'horastotales', get_string('horastotales', 'block_sence_connector'));
        $mform->setType('horastotales', PARAM_FLOAT);
        $mform->setDefault('horastotales', '20.0');

        // AsistenciaMinima (%)
        $mform->addElement('text', 'asistenciaminima', get_string('asistenciaminima', 'block_sence_connector'));
        $mform->setType('asistenciaminima', PARAM_FLOAT);
        $mform->setDefault('asistenciaminima', '75.0');

        // NotaMinima
        $mform->addElement('text', 'notaminima', get_string('notaminima', 'block_sence_connector'));
        $mform->setType('notaminima', PARAM_FLOAT);
        $mform->setDefault('notaminima', '4.0');

        // Hidden fields
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, 'Guardar Acción Formativa');
    }
}
