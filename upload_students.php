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
 * Carga Rápida y Masiva de Alumnos SENCE con asignación a Grupo / ID de Acción.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/grouplib.php');
require_once($CFG->dirroot . '/user/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$download_template = optional_param('download_template', 0, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('block/sence_connector:viewreports', $context);

// Descarga directa de plantilla CSV de ejemplo
if ($download_template) {
    $filename = "Plantilla_Carga_Alumnos_SENCE.csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $out = fopen('php://output', 'w');
    fputcsv($out, ['RUN', 'Nombres', 'Apellidos', 'Email', 'Telefono'], ';');
    fputcsv($out, ['17297004-4', 'Juan Carlos', 'Perez Gonzalez', 'juan.perez@empresa.cl', '+56912345678'], ';');
    fputcsv($out, ['18345678-9', 'Maria Andrea', 'Soto Tapia', 'maria.soto@empresa.cl', '+56987654321'], ';');
    fputcsv($out, ['9445435-2', 'Carlos Alberto', 'Vasquez Munoz', 'carlos.vasquez@empresa.cl', ''], ';');
    fclose($out);
    exit;
}

$PAGE->set_url(new moodle_url('/blocks/sence_connector/upload_students.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title('Carga Rápida de Alumnos SENCE - ' . $course->fullname);
$PAGE->set_heading($course->fullname);

// Cargar grupos existentes del curso
$groups = groups_get_all_groups($courseid);

$results = null;

// Procesar formulario de carga
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $target_group_id = optional_param('group_id', 0, PARAM_INT);
    $new_group_name = trim(optional_param('new_group_name', '', PARAM_TEXT));

    // Si especificó crear un nuevo grupo / ID de Acción
    if (!empty($new_group_name)) {
        $existing_grp = groups_get_group_by_name($courseid, $new_group_name);
        if ($existing_grp) {
            $target_group_id = $existing_grp;
        } else {
            $new_grp_data = (object)[
                'courseid' => $courseid,
                'name' => $new_group_name,
                'description' => 'Grupo SENCE creado automáticamente desde Carga Rápida Keystone Advisors',
                'descriptionformat' => FORMAT_PLAIN
            ];
            $target_group_id = groups_create_group($new_grp_data);
            $groups = groups_get_all_groups($courseid); // Refrescar lista
        }
    }

    if (empty($_FILES['csvfile']['tmp_name'])) {
        \core\notification::error("Por favor, selecciona un archivo CSV con la nómina de alumnos.");
    } else {
        $tmpfile = $_FILES['csvfile']['tmp_name'];
        $handle = fopen($tmpfile, 'r');
        if ($handle === false) {
            \core\notification::error("No se pudo leer el archivo cargado.");
        } else {
            $results = [
                'created' => 0,
                'enrolled' => 0,
                'assigned_group' => 0,
                'errors' => [],
                'rows' => []
            ];

            // Obtener plugin de matriculación manual del curso
            $enrol_manual = enrol_get_plugin('manual');
            $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual']);
            if (!$instance) {
                // Si no existe instancia manual, crearla
                $instance_id = $enrol_manual->add_instance($course);
                $instance = $DB->get_record('enrol', ['id' => $instance_id]);
            }
            $student_role = $DB->get_record('role', ['shortname' => 'student']);
            $student_role_id = $student_role ? $student_role->id : 5;

            $row_idx = 0;
            $delimiter = ';';

            // Detectar delimitador (coma o punto y coma)
            $first_line = fgets($handle);
            if ($first_line !== false) {
                if (substr_count($first_line, ';') < substr_count($first_line, ',')) {
                    $delimiter = ',';
                }
                rewind($handle);
            }

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $row_idx++;
                if ($row_idx === 1 && (stripos($data[0], 'run') !== false || stripos($data[0], 'rut') !== false)) {
                    // Cabecera omitida
                    continue;
                }

                $raw_run = isset($data[0]) ? trim($data[0]) : '';
                $nombres = isset($data[1]) ? trim($data[1]) : '';
                $apellidos = isset($data[2]) ? trim($data[2]) : '';
                $email = isset($data[3]) ? trim($data[3]) : '';
                $telefono = isset($data[4]) ? trim($data[4]) : '';

                if (empty($raw_run)) {
                    continue; // Línea vacía
                }

                // Normalización de RUN: ej 17297004-4
                $clean_run = strtoupper(str_replace(['.', ' '], '', $raw_run));
                if (strpos($clean_run, '-') === false && strlen($clean_run) >= 2) {
                    $clean_run = substr($clean_run, 0, -1) . '-' . substr($clean_run, -1);
                }

                // Validar formato básico de RUT
                if (!preg_match('/^[0-9]+-[0-9kK]$/', $clean_run)) {
                    $results['errors'][] = "Fila {$row_idx}: RUN inválido ({$raw_run}). Debe tener formato 12345678-K.";
                    continue;
                }

                // Validar email
                if (!validate_email($email)) {
                    // Si viene vacío o inválido, generar un email técnico seguro
                    $username_clean = strtolower(str_replace('-', '', $clean_run));
                    $email = $username_clean . '@capacitacion.sence.local';
                }

                $username = strtolower(str_replace(['.', '-'], '', $clean_run));

                // 1. Buscar si el usuario ya existe por idnumber o username
                $user = $DB->get_record('user', ['idnumber' => $clean_run]);
                if (!$user) {
                    $user = $DB->get_record('user', ['username' => $username]);
                }
                if (!$user) {
                    $user = $DB->get_record('user', ['email' => $email, 'mnethostid' => $CFG->mnet_localhost_id]);
                }

                $is_new_user = false;
                if (!$user) {
                    // Crear nuevo usuario
                    $newuser = (object)[
                        'auth' => 'manual',
                        'confirmed' => 1,
                        'mnethostid' => $CFG->mnet_localhost_id,
                        'username' => $username,
                        'password' => hash_internal_user_password('Sence.' . substr($username, -4) . '*'),
                        'firstname' => !empty($nombres) ? $nombres : 'Participante',
                        'lastname' => !empty($apellidos) ? $apellidos : $clean_run,
                        'email' => $email,
                        'idnumber' => $clean_run,
                        'phone1' => $telefono,
                        'city' => 'Santiago',
                        'country' => 'CL',
                        'lang' => 'es',
                        'timecreated' => time(),
                        'timemodified' => time(),
                    ];
                    $userid = user_create_user($newuser, false, false);
                    $user = $DB->get_record('user', ['id' => $userid]);
                    $results['created']++;
                    $is_new_user = true;
                } else {
                    // Asegurar que tenga el RUN correcto en idnumber
                    if ($user->idnumber !== $clean_run) {
                        $user->idnumber = $clean_run;
                        $DB->update_record('user', $user);
                    }
                }

                // 2. Matricular en el curso si no está matriculado
                if (!is_enrolled($context, $user->id)) {
                    $enrol_manual->enrol_user($instance, $user->id, $student_role_id);
                    $results['enrolled']++;
                }

                // 3. Asignar al Grupo / ID de Acción si se seleccionó uno
                $group_assigned_name = 'Sin Grupo';
                if ($target_group_id > 0) {
                    if (!groups_is_member($target_group_id, $user->id)) {
                        groups_add_member($target_group_id, $user->id);
                        $results['assigned_group']++;
                    }
                    $grp_obj = groups_get_group($target_group_id);
                    $group_assigned_name = $grp_obj ? $grp_obj->name : 'Grupo ' . $target_group_id;
                }

                $results['rows'][] = [
                    'run' => $clean_run,
                    'nombre' => fullname($user),
                    'email' => $user->email,
                    'is_new' => $is_new_user,
                    'group' => $group_assigned_name,
                ];
            }
            fclose($handle);
        }
    }
}

$logo_url = $OUTPUT->image_url('logo_keystone', 'block_sence_connector')->out(false);
$download_url = new moodle_url('/blocks/sence_connector/upload_students.php', ['courseid' => $courseid, 'download_template' => 1]);
$back_url = new moodle_url('/course/view.php', ['id' => $courseid]);

echo $OUTPUT->header();
?>

<div class="container-fluid my-4">
    <div class="row">
        <div class="col-12">
            <!-- Header Institucional -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px; background: linear-gradient(135deg, #1a4253 0%, #0d2833 100%);">
                <div class="card-body p-4 text-white d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-success badge-success text-white me-2 mr-2" style="font-size: 0.75rem;">Módulo Operativo OTEC</span>
                            <span class="badge bg-info badge-info text-white" style="font-size: 0.75rem;">Keystone Advisors</span>
                        </div>
                        <h2 class="h3 font-weight-bold fw-bold mb-1" style="color: #ffffff;">
                            <i class="fa fa-user-plus me-2 mr-2"></i> Carga Rápida y Masiva de Alumnos SENCE
                        </h2>
                        <p class="mb-0 text-white-50" style="font-size: 0.92rem;">
                            Curso: <strong><?php echo htmlspecialchars($course->fullname); ?></strong>
                        </p>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <a href="<?php echo $back_url; ?>" class="btn btn-outline-light btn-sm font-weight-bold">
                            <i class="fa fa-arrow-left me-1 mr-1"></i> Volver al Curso
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($results): ?>
                <!-- Panel de Resultados de la Carga -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px;">
                    <div class="card-header bg-white border-bottom p-3">
                        <h5 class="m-0 font-weight-bold text-success">
                            <i class="fa fa-check-circle me-1 mr-1"></i> Resumen de Procesamiento de la Nómina
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row text-center mb-4">
                            <div class="col-md-3 mb-2">
                                <div class="p-3 bg-light rounded shadow-sm">
                                    <h3 class="font-weight-bold text-primary mb-0"><?php echo count($results['rows']); ?></h3>
                                    <small class="text-muted text-uppercase">Total Procesados</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="p-3 bg-light rounded shadow-sm">
                                    <h3 class="font-weight-bold text-success mb-0"><?php echo $results['created']; ?></h3>
                                    <small class="text-muted text-uppercase">Usuarios Creados</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="p-3 bg-light rounded shadow-sm">
                                    <h3 class="font-weight-bold text-info mb-0"><?php echo $results['enrolled']; ?></h3>
                                    <small class="text-muted text-uppercase">Matriculados al Curso</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="p-3 bg-light rounded shadow-sm">
                                    <h3 class="font-weight-bold text-dark mb-0"><?php echo $results['assigned_group']; ?></h3>
                                    <small class="text-muted text-uppercase">Asignados a Grupo SENCE</small>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($results['errors'])): ?>
                            <div class="alert alert-danger mb-4">
                                <h6 class="font-weight-bold mb-2"><i class="fa fa-exclamation-circle me-1 mr-1"></i> Alertas detectadas durante la importación:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($results['errors'] as $err): ?>
                                        <li><?php echo htmlspecialchars($err); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle" style="font-size: 0.90rem;">
                                <thead class="table-dark" style="background-color: #1a4253;">
                                    <tr>
                                        <th>RUN / RUT</th>
                                        <th>Nombre Completo</th>
                                        <th>Correo Electrónico</th>
                                        <th>Estado Usuario</th>
                                        <th>Grupo / Acción SENCE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results['rows'] as $r): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($r['run']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($r['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($r['email']); ?></td>
                                            <td>
                                                <?php if ($r['is_new']): ?>
                                                    <span class="badge bg-success badge-success text-white">Nuevo Creado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary badge-secondary text-white">Existente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info badge-info text-white">
                                                    <i class="fa fa-users me-1 mr-1"></i> <?php echo htmlspecialchars($r['group']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulario de Carga Masiva Simplificada -->
            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="card shadow-sm border-0 h-100" style="border-radius: 10px;">
                        <div class="card-header bg-white border-bottom p-3">
                            <h5 class="m-0 font-weight-bold text-dark">
                                <i class="fa fa-upload me-1 mr-1 text-primary"></i> Subir Nómina de Alumnos
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                                <!-- Selección o Creación de Grupo / Acción SENCE -->
                                <div class="mb-4 p-3 rounded" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                    <label class="form-label font-weight-bold fw-bold text-dark mb-1">
                                        <i class="fa fa-building me-1 mr-1 text-primary"></i> Asignar a Grupo / ID de Acción SENCE:
                                    </label>
                                    <p class="small text-muted mb-2">
                                        Los alumnos cargados quedarán automáticamente enlazados a este código para la liquidación ante SENCE y la segmentación del curso.
                                    </p>

                                    <div class="mb-3">
                                        <label class="small text-muted mb-1">Elegir un Grupo existente en el curso:</label>
                                        <select name="group_id" class="form-select form-control">
                                            <option value="0">-- Sin Grupo / Acción General del Curso --</option>
                                            <?php foreach ($groups as $g): ?>
                                                <option value="<?php echo $g->id; ?>">
                                                    <?php echo htmlspecialchars($g->name); ?> (<?php echo count(groups_get_members($g->id)); ?> alumnos actuales)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-0">
                                        <label class="small text-muted mb-1">O crear un nuevo ID de Acción SENCE ahora:</label>
                                        <input type="text" name="new_group_name" class="form-control" placeholder="Ej: SENCE-6654959 o EMPRESA-XYZ">
                                        <small class="text-muted">Si escribes un nombre aquí, se creará el grupo automáticamente en Moodle.</small>
                                    </div>
                                </div>

                                <!-- Archivo CSV -->
                                <div class="mb-4">
                                    <label class="form-label font-weight-bold fw-bold text-dark mb-1">
                                        <i class="fa fa-file-text-o me-1 mr-1 text-success"></i> Selecciona el archivo CSV:
                                    </label>
                                    <input type="file" name="csvfile" class="form-control p-2" accept=".csv,text/csv" required>
                                    <small class="text-muted d-block mt-1">Formato admitido: CSV separado por comas o punto y coma.</small>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100 font-weight-bold shadow-sm" style="background-color: #1a4253; border-color: #1a4253;">
                                    <i class="fa fa-cloud-upload me-2 mr-2"></i> Procesar y Matricular Alumnos
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <!-- Instrucciones y Descarga de Plantilla -->
                    <div class="card shadow-sm border-0 h-100" style="border-radius: 10px;">
                        <div class="card-header bg-white border-bottom p-3">
                            <h5 class="m-0 font-weight-bold text-dark">
                                <i class="fa fa-info-circle me-1 mr-1 text-info"></i> Plantilla & Instrucciones
                            </h5>
                        </div>
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <h6 class="font-weight-bold text-dark mb-2">Columnas del archivo simplificado:</h6>
                                <p class="small text-muted mb-3">
                                    A diferencia del cargador genérico de Moodle, aquí solo necesitas 4 columnas básicas:
                                </p>
                                <ol class="small text-muted ps-3 mb-4" style="line-height: 1.6;">
                                    <li><strong>RUN:</strong> RUT del participante con o sin puntos (ej: <code>17297004-4</code>). Vital para validar con ClaveÚnica.</li>
                                    <li><strong>Nombres:</strong> Nombre(s) del participante.</li>
                                    <li><strong>Apellidos:</strong> Apellidos del participante.</li>
                                    <li><strong>Email:</strong> Correo del alumno. Si se deja en blanco, el sistema genera uno técnico automático.</li>
                                    <li><strong>Telefono:</strong> (Opcional) Móvil del alumno.</li>
                                </ol>

                                <div class="alert alert-info py-2 px-3 small mb-4" style="background-color: #eaf6fa; border: 1px solid #bce1ee;">
                                    <i class="fa fa-lightbulb-o me-1 mr-1 text-info"></i>
                                    <strong>Acreditación Automática:</strong> Si el usuario ya existe en Moodle, el sistema no lo duplica; solo lo matricula al curso y lo vincula al grupo SENCE.
                                </div>
                            </div>

                            <div class="pt-3 border-top text-center">
                                <a href="<?php echo $download_url; ?>" class="btn btn-outline-success w-100 py-2 font-weight-bold">
                                    <i class="fa fa-download me-2 mr-2"></i> Descargar Plantilla Oficial CSV
                                </a>
                                <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">
                                    Compatible con Excel y Google Sheets (Codificación UTF-8).
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Institucional Keystone Advisors -->
            <div class="text-center mt-3 pt-3 border-top text-muted small" style="font-size: 0.78rem;">
                Conector Oficial SENCE Keystone Advisors | Soporte: <a href="https://www.keystoneadvisors.cl" target="_blank" rel="noopener" style="color: #1a4253;">www.keystoneadvisors.cl</a> | Las Condes, Santiago.
            </div>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
