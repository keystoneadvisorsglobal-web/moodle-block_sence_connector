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

require_once(__DIR__ . '/../../config.php');

use block_sence_connector\util\sence_helper;

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

// Resolve specific action config directly from the student's Moodle Group
$scourse = sence_helper::get_user_action_config($courseid, $USER->id);

// If user already has an active session, let them proceed
$activesession = $DB->get_record('block_sence_sessions', [
    'courseid' => $courseid,
    'userid' => $USER->id,
    'status' => 'ACTIVE'
]);
if ($activesession) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$PAGE->set_url(new moodle_url('/blocks/sence_connector/gatekeeper.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Validación de Asistencia SENCE Requerida');
$PAGE->set_heading($course->fullname);

$rutotec = get_config('block_sence_connector', 'rutotec');
$token = get_config('block_sence_connector', 'token');
$run = sence_helper::get_user_run($USER);

$rce_iniciar_url = sence_helper::get_rce_base_url() . 'IniciarSesion';
$cod_sence = $scourse->codsence ?: '-1';
$codigo_curso = $scourse->codigooferta ?: "CURSO-{$courseid}";
$id_sesion_alumno = 'SES-' . $USER->id . '-' . time();
$url_retoma = $CFG->wwwroot . '/blocks/sence_connector/retoma.php';
$url_error = $CFG->wwwroot . '/blocks/sence_connector/error.php';
$logo_url = $OUTPUT->image_url('logo_keystone', 'block_sence_connector')->out(false);

echo $OUTPUT->header();
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg border-0 text-center" style="border-radius: 14px; overflow: hidden; background-color: #ffffff;">
                <!-- Header con logo nítido y título en alto contraste blanco puro -->
                <div class="card-header p-4" style="background-color: #1a4253; border-bottom: none;">
                    <div class="mb-3 py-2 px-3 d-inline-block rounded shadow-sm" style="background-color: #ffffff;">
                        <img src="<?php echo $logo_url; ?>" alt="Keystone Advisors" style="height: 52px; width: auto; max-width: 260px; object-fit: contain; display: block;">
                    </div>
                    <h3 class="m-0 font-weight-bold" style="color: #ffffff !important; font-size: 1.45rem; letter-spacing: 0.5px; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                        Registro Obligatorio de Asistencia SENCE
                    </h3>
                </div>

                <div class="card-body p-4 p-md-5" style="background-color: #ffffff;">
                    <!-- Caja de advertencia normativa -->
                    <div class="alert alert-warning text-start text-left d-flex align-items-center mb-4 p-3 border-0 shadow-sm" style="background-color: #fff9e6; border-left: 5px solid #e5a50a !important; border-radius: 8px;" role="alert">
                        <i class="fa fa-lock fa-2x me-3 mr-3" style="color: #d98200;"></i>
                        <div style="color: #2b2b2b; font-size: 0.95rem; line-height: 1.4;">
                            <strong style="color: #1a1a1a; font-size: 1rem;">Acceso a contenidos restringido</strong><br>
                            De acuerdo con la normativa oficial de SENCE para cursos e-learning, debes validar tu identidad mediante <strong>ClaveÚnica</strong> para iniciar el cómputo de tus horas de capacitación.
                        </div>
                    </div>

                    <!-- Ficha del Alumno (Fondo blanco puro, borde suave y tipografía nítida en alto contraste) -->
                    <div class="p-4 mb-4 text-start text-left rounded shadow-sm" style="background-color: #ffffff; border: 1.5px solid #e1e7ec; border-radius: 10px;">
                        <p class="mb-2" style="color: #212529; font-size: 0.98rem;">
                            <strong style="color: #0b1f28;">Participante:</strong> 
                            <span class="fw-bold font-weight-bold" style="color: #1a4253;"><?php echo fullname($USER); ?></span>
                        </p>
                        <p class="mb-2" style="color: #212529; font-size: 0.98rem;">
                            <strong style="color: #0b1f28;">RUN / RUT:</strong> 
                            <span class="badge px-2 py-1 font-weight-bold" style="background-color: #e9ecef; color: #1a4253; font-size: 0.95rem; border: 1px solid #ced4da;">
                                <?php echo $run ?: 'No registrado en perfil'; ?>
                            </span>
                        </p>
                        <p class="mb-2" style="color: #212529; font-size: 0.98rem;">
                            <strong style="color: #0b1f28;">Curso:</strong> 
                            <span style="color: #2b2b2b;"><?php echo htmlspecialchars($course->fullname); ?></span>
                        </p>
                        <p class="mb-0" style="color: #212529; font-size: 0.98rem;">
                            <strong style="color: #0b1f28;">ID Acción SENCE:</strong> 
                            <span class="badge px-2 py-1 font-weight-bold" style="background-color: #d1ecf1; color: #0c5460; font-size: 0.95rem; border: 1px solid #bee5eb;">
                                <?php echo htmlspecialchars($scourse->codigooferta); ?>
                            </span>
                        </p>
                    </div>

                    <!-- Botón de acción con ClaveÚnica -->
                    <form action="<?php echo $rce_iniciar_url; ?>" method="POST">
                        <input type="hidden" name="RutOtec" value="<?php echo htmlspecialchars($rutotec); ?>">
                        <input type="hidden" name="Token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="CodSence" value="<?php echo htmlspecialchars($cod_sence); ?>">
                        <input type="hidden" name="CodigoCurso" value="<?php echo htmlspecialchars($codigo_curso); ?>">
                        <input type="hidden" name="LineaCapacitacion" value="<?php echo (int)$scourse->lineacapacitacion; ?>">
                        <input type="hidden" name="RunAlumno" value="<?php echo htmlspecialchars($run); ?>">
                        <input type="hidden" name="IdSesionAlumno" value="<?php echo htmlspecialchars($id_sesion_alumno); ?>">
                        <input type="hidden" name="UrlRetoma" value="<?php echo htmlspecialchars($url_retoma); ?>">
                        <input type="hidden" name="UrlError" value="<?php echo htmlspecialchars($url_error); ?>">
                        <button type="submit" class="btn btn-lg w-100 py-3 font-weight-bold shadow" style="background-color: #1a4253; border-color: #1a4253; color: #ffffff !important; font-size: 1.15rem; border-radius: 8px;">
                            <i class="fa fa-key me-2 mr-2"></i> Iniciar Asistencia con ClaveÚnica
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-top small" style="color: #6c757d;">
                        Desarrollado y respaldado de forma gratuita por<br>
                        <a href="https://www.keystoneadvisors.cl" target="_blank" rel="noopener" class="font-weight-bold text-decoration-none" style="color: #1a4253; font-size: 0.88rem;">
                            Keystone Advisors
                        </a> | Avenida Apoquindo 6410, Of. 605, Las Condes.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
