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
                        var cards = document.querySelectorAll(".course-card, .dashboard-card, [data-region=\"course-content\"], .coursebox");

                        cards.forEach(function(card) {
                            var cid = 0;
                            var attrId = card.getAttribute("data-course-id");
                            if (attrId) {
                                cid = parseInt(attrId, 10);
                            } else {
                                var link = card.querySelector("a[href*=\"/course/view.php?id=\"]");
                                if (link) {
                                    var match = link.href.match(/id=(\\d+)/);
                                    if (match) cid = parseInt(match[1], 10);
                                }
                            }

                            if (!cid || senceCourseIds.indexOf(cid) === -1) {
                                return;
                            }

                            var imgTop = card.querySelector(".card-img-top, .courseimage, .dashboard-card-img");
                            var imgWrapper = imgTop ? (imgTop.closest("a") || imgTop) : null;

                            if (!imgWrapper) {
                                return;
                            }

                            if (card.querySelector(".sence-card-badge") || imgWrapper.querySelector(".sence-card-badge")) {
                                return;
                            }

                            if (window.getComputedStyle(imgWrapper).position === "static") {
                                imgWrapper.style.position = "relative";
                            }

                            var badge = document.createElement("div");
                            badge.className = "sence-card-badge";
                            badge.title = "Curso Oficial SENCE (ClaveÚnica)";
                            badge.style.cssText = "position: absolute; top: 10px; right: 10px; z-index: 10; background: rgba(255, 255, 255, 0.95); padding: 3px 8px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 5px; border: 1.5px solid #005a9c; pointer-events: none;";

                            var img = document.createElement("img");
                            img.src = senceLogoUrl;
                            img.alt = "SENCE";
                            img.style.cssText = "height: 18px; width: auto; object-fit: contain; display: block;";

                            var text = document.createElement("span");
                            text.textContent = "SENCE";
                            text.style.cssText = "font-weight: 800; font-size: 0.70rem; color: #005a9c; letter-spacing: 0.5px;";

                            badge.appendChild(img);
                            badge.appendChild(text);
                            imgWrapper.appendChild(badge);
                        });
                    }

                    document.addEventListener("DOMContentLoaded", injectSenceBadges);
                    setInterval(injectSenceBadges, 1000);
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
