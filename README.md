# Conector SENCE Keystone Advisors (block_sence_connector)

**Plugin Oficial Gratuito** desarrollado y mantenido por **Keystone Advisors** para plataformas de aprendizaje **Moodle** (versiones 4.1 LTS, 4.4, 4.5 LTS y 5.x).

Permite a los Organismos Técnicos de Capacitación (OTEC) e instituciones ejecutoras cumplir de forma automatizada y transparente con todas las exigencias normativas del **Servicio Nacional de Capacitación y Empleo (SENCE)** de Chile para cursos e-learning impartidos mediante **Plataforma Propia**.

---

## 🏛️ Datos de la Consultora y Soporte

* **Desarrollador:** Keystone Advisors
* **Sitio Web Oficial:** [https://www.keystoneadvisors.cl](https://www.keystoneadvisors.cl)
* **Dirección Corporativa:** Avenida Apoquindo 6410, Oficina 605, Las Condes, Santiago, Chile.
* **Licencia:** Software Libre y Gratuito bajo licencia GNU General Public License v3 (GPLv3).

---

## 🚀 Funcionalidades Principales

1. **Autenticación e Identidad RCE (ClaveÚnica):**
   - Integración nativa con la pasarela oficial SENCE (`IniciarSesion` y `CerrarSesion`).
   - Retornos seguros vía `UrlRetoma` y control amigable de errores mediante `UrlError` según el catálogo oficial SENCE.
2. **Sincronización Automática con la API Gestor Intermedio (SIC):**
   - Payload JSON estructurado bajo las especificaciones vigentes del **Instructivo Técnico v2.0** (`idSistema: 1350`, `rutOtec`, `token`, `codigoOferta`, `listaAlumnos`, `listaModulos`).
   - Envío desatendido mediante Tarea Programada de Moodle (`\core\task\scheduled_task`).
   - Cálculo automático de horas de conexión efectivas, porcentajes de avance y estados (1: Cursando, 2: Aprobado, 3: Reprobado).
3. **Libertad Total de Ubicación y Diseño:**
   - Bloque modular multi-instancia adaptable a cualquier región de la página (barra lateral, encabezado, pie de página o área personal).
4. **Libro de Asistencia y Panel de Auditoría:**
   - Tablero de control con métricas en tiempo real, trazabilidad de conexiones por estudiante y botón de sincronización inmediata con SENCE.
5. **Cumplimiento GDPR / Privacy API:**
   - Cumple con los estándares más estrictos exigidos por el Directorio de Plugins de Moodle (`moodle.org/plugins`).

---

## 📦 Instalación

1. Descarga el paquete `block_sence_connector.zip`.
2. En tu Moodle, accede como Administrador a:
   `Administración del sitio > Extensiones > Instalar extensiones`.
3. Sube el archivo ZIP y sigue los pasos del asistente de actualización.
4. Configura el RUT de tu OTEC y tu Token generado en [https://sistemas.sence.cl/rts](https://sistemas.sence.cl/rts) en:
   `Administración del sitio > Extensiones > Bloques > Conector SENCE Keystone Advisors`.
