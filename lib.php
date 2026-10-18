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

use block_sence_connector\observer\course_observer;

/**
 * Global hook executed before HTTP headers on EVERY Moodle page.
 * 1. Intercepts internal activities (grades, quizzes, assignments, resources)
 *    and enforces mandatory SENCE ClaveÚnica authentication before rendering content.
 * 2. Injects official SENCE badge with Logo on course cards in "Mis Cursos" (/my/courses.php).
 */
function block_sence_connector_before_http_headers() {
    global $PAGE, $COURSE, $DB, $OUTPUT;

    $courseid = 0;
    if (!empty($PAGE->course) && !empty($PAGE->course->id) && $PAGE->course->id > 1) {
        $courseid = (int)$PAGE->course->id;
    } else if (!empty($COURSE) && !empty($COURSE->id) && $COURSE->id > 1) {
        $courseid = (int)$COURSE->id;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '';

    // Si está dentro de un curso, verificar seguridad de actividades
    if ($courseid > 1) {
        $is_activity = (strpos($uri, '/mod/') !== false || strpos($uri, '/grade/') !== false);
        course_observer::check_sence_access($courseid, $is_activity);
    }

    // Si está en la vista de "Mis Cursos" (/my/courses.php o /my/), inyectar badges en las tarjetas
    if (strpos($uri, '/my/courses.php') !== false || strpos($uri, '/my/') !== false || ($PAGE->pagelayout ?? '') === 'mydashboard') {
        try {
            $sence_courses = $DB->get_records_menu('block_sence_courses', ['enabled' => 1], '', 'courseid, codsence');
            if (!empty($sence_courses)) {
                $sence_ids_json = json_encode(array_map('intval', array_keys($sence_courses)));
                $logo_url = $OUTPUT->image_url('logo_sence', 'block_sence_connector')->out(false);

                $js = <<<JS
                (function() {
                    var senceCourseIds = {$sence_ids_json};
                    var senceLogoUrl = "{$logo_url}";

                    function injectSenceBadges() {
                        var cards = document.querySelectorAll('.dashboard-card, [data-region="course-content"], .course-info-container');
                        if (!cards.length) {
                            cards = document.querySelectorAll('.card.dashboard-card, .coursebox');
                        }

                        cards.forEach(function(card) {
                            if (card.getAttribute('data-sence-checked')) return;
                            card.setAttribute('data-sence-checked', '1');

                            // Buscar el ID del curso en el enlace
                            var link = card.querySelector('a[href*="/course/view.php?id="]');
                            if (!link) return;

                            var match = link.href.match(/id=(\d+)/);
                            if (!match) return;

                            var cid = parseInt(match[1], 10);
                            if (senceCourseIds.indexOf(cid) !== -1) {
                                // Encontrado curso SENCE: inyectar distintivo en la imagen o cabecera de la tarjeta
                                var imgContainer = card.querySelector('.card-img-top, .courseimage, .dashboard-card-img');
                                var target = imgContainer || card;

                                if (target && !card.querySelector('.sence-card-badge')) {
                                    if (window.getComputedStyle(target).position === 'static') {
                                        target.style.position = 'relative';
                                    }

                                    var badge = document.createElement('div');
                                    badge.className = 'sence-card-badge';
                                    badge.title = 'Curso Oficial con Sincronización SENCE (ClaveÚnica)';
                                    badge.style.cssText = 'position: absolute; top: 8px; right: 8px; z-index: 5; background: rgba(255, 255, 255, 0.95); padding: 3px 8px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.25); display: flex; align-items: center; gap: 5px; border: 1.5px solid #005a9c;';

                                    var img = document.createElement('img');
                                    img.src = senceLogoUrl;
                                    img.alt = 'SENCE';
                                    img.style.cssText = 'height: 18px; width: auto; object-fit: contain; display: block;';

                                    var text = document.createElement('span');
                                    text.textContent = 'SENCE';
                                    text.style.cssText = 'font-weight: 800; font-size: 0.70rem; color: #005a9c; letter-spacing: 0.5px;';

                                    badge.appendChild(img);
                                    badge.appendChild(text);
                                    target.appendChild(badge);
                                }
                            }
                        });
                    }

                    document.addEventListener('DOMContentLoaded', injectSenceBadges);
                    setInterval(injectSenceBadges, 1500); // Para tarjetas cargadas vía AJAX
                })();
JS;
                $PAGE->requires->js_init_code($js, true);
            }
        } catch (\Throwable $e) {
            // Ignorar en caso de que la tabla aún no exista
        }
    }
}

/**
 * Extend navigation hook.
 *
 * @param global_navigation $navigation
 */
function block_sence_connector_extend_navigation(global_navigation $navigation) {
    global $PAGE, $COURSE;

    $courseid = 0;
    if (!empty($PAGE->course) && !empty($PAGE->course->id) && $PAGE->course->id > 1) {
        $courseid = (int)$PAGE->course->id;
    } else if (!empty($COURSE) && !empty($COURSE->id) && $COURSE->id > 1) {
        $courseid = (int)$COURSE->id;
    }

    if ($courseid > 1) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $is_activity = (strpos($uri, '/mod/') !== false || strpos($uri, '/grade/') !== false);
        course_observer::check_sence_access($courseid, $is_activity);
    }
}
