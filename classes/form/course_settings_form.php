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
 * Course SENCE configuration form.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_settings_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        $mform->addElement('header', 'sence_course_hdr', get_string('course_settings_title', 'block_sence_connector'));

        // Enable SENCE checkbox
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'block_sence_connector'));
        $mform->setDefault('enabled', 0);

        // CodSence
        $mform->addElement('text', 'codsence', get_string('codsence', 'block_sence_connector'));
        $mform->setType('codsence', PARAM_TEXT);
        $mform->addHelpButton('codsence', 'codsence', 'block_sence_connector');
        $mform->hideIf('codsence', 'enabled', 'notchecked');

        // CodigoOferta (SIC)
        $mform->addElement('text', 'codigooferta', get_string('codigooferta', 'block_sence_connector'));
        $mform->setType('codigooferta', PARAM_TEXT);
        $mform->addHelpButton('codigooferta', 'codigooferta', 'block_sence_connector');
        $mform->hideIf('codigooferta', 'enabled', 'notchecked');

        // CodigoGrupo
        $mform->addElement('text', 'codigogrupo', get_string('codigogrupo', 'block_sence_connector'));
        $mform->setType('codigogrupo', PARAM_TEXT);
        $mform->setDefault('codigogrupo', '1');
        $mform->hideIf('codigogrupo', 'enabled', 'notchecked');

        // LineaCapacitacion
        $mform->addElement('text', 'lineacapacitacion', get_string('lineacapacitacion', 'block_sence_connector'));
        $mform->setType('lineacapacitacion', PARAM_INT);
        $mform->setDefault('lineacapacitacion', 3);
        $mform->hideIf('lineacapacitacion', 'enabled', 'notchecked');

        // HorasTotales
        $mform->addElement('text', 'horastotales', get_string('horastotales', 'block_sence_connector'));
        $mform->setType('horastotales', PARAM_FLOAT);
        $mform->setDefault('horastotales', '20.0');
        $mform->hideIf('horastotales', 'enabled', 'notchecked');

        // AsistenciaMinima (%)
        $mform->addElement('text', 'asistenciaminima', get_string('asistenciaminima', 'block_sence_connector'));
        $mform->setType('asistenciaminima', PARAM_FLOAT);
        $mform->setDefault('asistenciaminima', '75.0');
        $mform->hideIf('asistenciaminima', 'enabled', 'notchecked');

        // NotaMinima
        $mform->addElement('text', 'notaminima', get_string('notaminima', 'block_sence_connector'));
        $mform->setType('notaminima', PARAM_FLOAT);
        $mform->setDefault('notaminima', '4.0');
        $mform->hideIf('notaminima', 'enabled', 'notchecked');

        // Hidden fields
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
