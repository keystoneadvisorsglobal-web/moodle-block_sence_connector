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
 * Course SENCE Settings - Group as SENCE Action ID Configuration.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('block/sence_connector:manage', $context);

$PAGE->set_url(new moodle_url('/blocks/sence_connector/course_settings.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title('Ajustes SENCE del Curso - Keystone Advisors');
$PAGE->set_heading($course->fullname . ' - Configuración SENCE');

// Ensure base course record exists
$base = $DB->get_record('block_sence_courses', ['courseid' => $courseid, 'groupid' => 0]);
if (!$base) {
    $base = (object)[
        'courseid' => $courseid,
        'groupid' => 0,
        'enabled' => 1,
        'codsence' => '-1',
        'codigooferta' => 'CAP-15-01-13-0446',
        'codigogrupo' => '1',
        'lineacapacitacion' => 3,
        'horastotales' => 20.0,
        'asistenciaminima' => 75.0,
        'notaminima' => 4.0,
        'timecreated' => time(),
        'timemodified' => time(),
    ];
    $base->id = $DB->insert_record('block_sence_courses', $base);
}

// Handle Form Submit
if (data_submitted() && confirm_sesskey()) {
    $base->enabled = optional_param('enabled', 0, PARAM_INT);
    $base->codsence = trim(optional_param('codsence', '-1', PARAM_TEXT));
    $base->lineacapacitacion = optional_param('lineacapacitacion', 3, PARAM_INT);
    $base->horastotales = optional_param('horastotales', 20.0, PARAM_FLOAT);
    $base->asistenciaminima = optional_param('asistenciaminima', 75.0, PARAM_FLOAT);
    $base->notaminima = optional_param('notaminima', 4.0, PARAM_FLOAT);
    $base->timemodified = time();
    $DB->update_record('block_sence_courses', $base);

    \core\notification::success("Parámetros generales de la acción formativa guardados exitosamente.");
    redirect(new moodle_url('/blocks/sence_connector/course_settings.php', ['courseid' => $courseid]));
}

echo $OUTPUT->header();

$groups = groups_get_all_groups($courseid);
$enrolled = get_enrolled_users($context, 'block/sence_connector:view');

?>
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <div>
        <h2 class="font-weight-bold m-0" style="color: #225268;">
            <i class="fa fa-cogs me-2 mr-2"></i> Configuración SENCE del Curso
        </h2>
        <small class="text-muted">
            En este modelo, <strong>el Grupo de Moodle es el ID de Acción SENCE</strong> asignado al alumno (ej: <code>SENCE-6654959</code>). Cada alumno pertenece a su grupo/empresa y SENCE reporta sus horas a ese código específico.
        </small>
    </div>
    <div>
        <a href="<?php echo (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left me-1 mr-1"></i> Volver al Curso
        </a>
    </div>
</div>

<!-- Grupos / IDs de Acción Detectados en el Curso -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="font-weight-bold text-dark">
            <i class="fa fa-users me-1 mr-1"></i> Grupos de Moodle (IDs de Acción SENCE) Activos en este Curso
        </span>
        <a href="<?php echo (new moodle_url('/group/index.php', ['id' => $courseid]))->out(false); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="fa fa-plus me-1 mr-1"></i> Crear / Administrar Grupos en Moodle
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle m-0">
                <thead class="thead-light bg-light">
                    <tr>
                        <th>Grupo Moodle (ID Acción SENCE)</th>
                        <th>Alumnos Asignados</th>
                        <th>Estado en SENCE</th>
                        <th class="text-center">Libro de Asistencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($groups)): ?>
                        <tr><td colspan="4" class="text-center p-4 text-muted">No existen grupos creados en este curso todavía.</td></tr>
                    <?php else: ?>
                        <?php foreach ($groups as $g): 
                            $count_m = $DB->count_records('groups_members', ['groupid' => $g->id]);
                            $report_group_url = new moodle_url('/blocks/sence_connector/report.php', ['courseid' => $courseid, 'groupid' => $g->id]);
                        ?>
                        <tr>
                            <td>
                                <strong class="text-primary" style="font-size: 1.05rem;">
                                    <code><?php echo htmlspecialchars($g->name); ?></code>
                                </strong>
                            </td>
                            <td>
                                <span class="badge bg-info text-white" style="font-size: 0.85rem;">
                                    <?php echo $count_m; ?> alumnos inscritos
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success badge-success">Operativo (Auto-detectado)</span>
                            </td>
                            <td class="text-center">
                                <a href="<?php echo $report_group_url->out(false); ?>" class="btn btn-sm btn-outline-dark">
                                    <i class="fa fa-book me-1 mr-1"></i> Ver Informe de esta Acción
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Parámetros Generales de la Capacitación -->
<div class="card shadow-sm border-0 mb-4 p-4">
    <h4 class="font-weight-bold mb-3" style="color: #225268;">
        <i class="fa fa-sliders me-1 mr-1"></i> Parámetros Normativos de la Capacitación
    </h4>
    <form method="POST" action="<?php echo $PAGE->url; ?>">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabledCheck" <?php echo $base->enabled ? 'checked' : ''; ?>>
            <label class="form-check-label font-weight-bold" for="enabledCheck">
                Habilitar Asistencia SENCE Obligatoria en este curso
            </label>
        </div>

        <div class="row g-3">
            <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Código SENCE (SOLICURSO)</label>
                <input type="text" name="codsence" class="form-control" value="<?php echo htmlspecialchars($base->codsence); ?>" required>
                <small class="text-muted">Ingresa el código oficial de SENCE o <code>-1</code> para ambiente de homologación/pruebas.</small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Línea de Capacitación</label>
                <select name="lineacapacitacion" class="form-select form-control">
                    <option value="3" <?php echo ($base->lineacapacitacion == 3) ? 'selected' : ''; ?>>3 - Franquicia Tributaria (Impulsa Personas)</option>
                    <option value="1" <?php echo ($base->lineacapacitacion == 1) ? 'selected' : ''; ?>>1 - Programas Sociales / Becas Laborales</option>
                    <option value="2" <?php echo ($base->lineacapacitacion == 2) ? 'selected' : ''; ?>>2 - Formación en el Trabajo / Transferencias</option>
                    <option value="4" <?php echo ($base->lineacapacitacion == 4) ? 'selected' : ''; ?>>4 - Becas Fondo de Cesantía Solidario (FCS)</option>
                    <option value="5" <?php echo ($base->lineacapacitacion == 5) ? 'selected' : ''; ?>>5 - Convenios Directos y Licitaciones</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Horas Cronológicas Totales</label>
                <input type="number" step="0.5" name="horastotales" class="form-control" value="<?php echo htmlspecialchars($base->horastotales); ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Asistencia Mínima Requerida (%)</label>
                <input type="number" step="1" name="asistenciaminima" class="form-control" value="<?php echo htmlspecialchars($base->asistenciaminima); ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Nota Mínima de Aprobación</label>
                <input type="number" step="0.1" name="notaminima" class="form-control" value="<?php echo htmlspecialchars($base->notaminima); ?>" required>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold" style="background-color: #225268; border-color: #225268;">
                <i class="fa fa-save me-1 mr-1"></i> Guardar Parámetros
            </button>
        </div>
    </form>
</div>

<div class="text-center my-4 py-3 text-muted small border-top">
    Desarrollado y respaldado de forma oficial y gratuita por<br>
    <a href="https://www.keystoneadvisors.cl" target="_blank" rel="noopener" class="font-weight-bold text-decoration-none" style="color: #225268;">
        Keystone Advisors
    </a> | Soluciones Tecnológicas de Cumplimiento Normativo SENCE | Av. Apoquindo 6410, Of. 605, Las Condes.
</div>

<?php
echo $OUTPUT->footer();
