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
 * Endpoint Callback for SENCE RCE UrlError.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use block_sence_connector\util\sence_helper;

$glosaerror = optional_param('GlosaError', 0, PARAM_INT);
$idsesionalumno = optional_param('IdSesionAlumno', '', PARAM_TEXT);
$codigocurso = optional_param('CodigoCurso', '', PARAM_TEXT);

$err_msg = sence_helper::translate_error_code($glosaerror);

// Locate course to redirect cleanly
$courseid = 0;
if (!empty($codigocurso)) {
    $sc = $DB->get_record('block_sence_courses', ['codigooferta' => $codigocurso]);
    if ($sc) {
        $courseid = (int)$sc->courseid;
    }
}
if (!$courseid && !empty($idsesionalumno)) {
    $sess = $DB->get_record('block_sence_sessions', ['idsesionalumno' => $idsesionalumno]);
    if ($sess) {
        $courseid = (int)$sess->courseid;
    }
}

$redirect_url = $courseid ? new moodle_url('/blocks/sence_connector/gatekeeper.php', ['courseid' => $courseid]) : new moodle_url('/');

// If user session is still alive in Moodle, show standard flash notification
if (isloggedin() && !isguestuser()) {
    \core\notification::error("Error en validación SENCE: " . $err_msg);
    redirect($redirect_url);
}

// If Cross-site POST dropped session cookie, display clean intermediate landing and redirect without losing auth
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/sence_connector/error.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Error en Validación SENCE');

echo $OUTPUT->header();
?>
<div class="container my-5 text-center">
    <div class="card shadow-sm border-0 p-4 mx-auto" style="max-width: 600px; border-radius: 10px;">
        <div class="text-danger mb-3">
            <i class="fa fa-times-circle fa-4x"></i>
        </div>
        <h4 class="font-weight-bold text-dark">No fue posible validar tu Asistencia en SENCE</h4>
        <p class="text-muted mt-2">
            <?php echo htmlspecialchars($err_msg); ?>
        </p>
        <div class="alert alert-info small text-start text-left mt-3">
            <strong>Recomendación:</strong> Verifica que tu ClaveÚnica esté activa y que hayas ingresado tu RUN y contraseña correctamente.
        </div>
        <div class="mt-4">
            <a href="<?php echo $redirect_url->out(false); ?>" class="btn btn-primary px-4 py-2" style="background-color: #225268; border-color: #225268;">
                <i class="fa fa-arrow-left me-1 mr-1"></i> Volver a Intentar
            </a>
        </div>
    </div>
</div>
<?php
echo $OUTPUT->footer();
