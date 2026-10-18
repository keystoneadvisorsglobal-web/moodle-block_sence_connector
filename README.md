# Conector SENCE Keystone Advisors (block_sence_connector)

**Plugin Oficial y Gratuito** desarrollado y mantenido por **Keystone Advisors SpA** para plataformas de aprendizaje **Moodle** (versiones 4.1 LTS, 4.4, 4.5 LTS y 5.x).

Permite a los Organismos Técnicos de Capacitación (OTEC), instituciones y empresas cumplir de forma automatizada, transparente y robusta con todas las exigencias normativas del **Servicio Nacional de Capacitación y Empleo (SENCE)** de Chile para cursos e-learning impartidos mediante **Plataforma Propia**.

---

## ⚖️ Propiedad Intelectual, Derechos de Autor y Licencia de Uso

* **Titular de Derechos:** © 2026 Keystone Advisors SpA. Todos los derechos reservados.
* **Modelo de Licencia:** Software Propietario Gratuito (*Freeware with Reserved Intellectual Property Rights*).
* **Sitio Web Oficial:** [https://www.keystoneadvisors.cl](https://www.keystoneadvisors.cl)
* **Contacto Legal & Comercial:** [contacto@keystoneadvisors.cl](mailto:contacto@keystoneadvisors.cl)
* **Oficina Corporativa:** Av. Apoquindo 6410, Of. 605, Las Condes, Santiago, Chile.

### 🛡️ Términos de Protección y Restricciones:
1. **Uso Permitido:** Se autoriza la descarga, instalación y ejecución **100% gratuita** del plugin en cualquier plataforma Moodle para la gestión operativa y de fiscalización de cursos SENCE.
2. **Prohibición Estricta de Copia y Modificación:** Queda terminantemente prohibida la copia no autorizada, clonación, ingeniería inversa, alteración del código fuente, derivación de versiones no autorizadas, o la extracción de funciones o módulos para ser incorporados en otros plugins o sistemas sin el consentimiento previo, expreso y por escrito de Keystone Advisors SpA.
3. **Prohibición de Comercialización:** Ninguna persona natural o jurídica está autorizada a revender, sublicenciar, arrendar o lucrar con la distribución de este plugin.
4. **Desarrollos a Medida y Nuevas Funcionalidades:** Keystone Advisors ofrece servicios profesionales de personalización, integración con ERPs, reportería a medida y desarrollos específicos para OTEC. Solicite cotización formal en `contacto@keystoneadvisors.cl`.

---

## 🚀 Capacidades y Módulos Incluidos

1. **Autenticación e Identidad RCE (ClaveÚnica):**
   - Integración nativa con la pasarela oficial SENCE (`IniciarSesion` y `CerrarSesion`).
   - Retornos seguros vía `UrlRetoma` y control amigable de errores mediante `UrlError`.
   - Sandbox de pruebas y simulación QA para verificar cursos sin ClaveÚnica real.
2. **Medición de Actividad Pedagógica Real (Anti-Inactividad):**
   - Detección activa en el navegador (movimiento, lectura, eventos de teclado y foco).
   - Pausa automática tras 3 minutos de inactividad para garantizar horas efectivas auditables ante SENCE.
   - Pings periódicos cada 30 segundos (`ping.php`) para blindar el registro ante cortes de conexión.
3. **Arquitectura Multi-Empresa (Grupo = ID de Acción SENCE):**
   - Soporte para que participantes de distintas empresas compartan un mismo curso Moodle manteniendo ID de acciones SENCE totalmente independientes.
4. **Módulo de Carga Rápida y Masiva de Alumnos:**
   - Interfaz simplificada con plantilla CSV de solo 4 columnas (RUN, Nombres, Apellidos, Email).
   - Validación de formato y creación/matriculación con un clic.
5. **Centro de Informes y Auditoría SENCE:**
   - Libro de asistencia con indicación explícita del curso y grupo.
   - Bitácora forense de conexiones RCE con IP y marcas temporales.
   - Descarga de liquidaciones en formato CSV estructurado para presentación directa ante la OTIC.
6. **Sincronización Automática con la API Gestor Intermedio (SIC):**
   - Payload JSON estructurado bajo las especificaciones vigentes del **Instructivo Técnico v2.0** (`idSistema: 1350`).
   - Envío desatendido mediante Cron programado de Moodle.

---

## 📦 Instalación

1. Descarga el paquete oficial `block_sence_connector.zip`.
2. En tu plataforma Moodle, accede como Administrador a:
   `Administración del sitio > Extensiones > Instalar extensiones`.
3. Sube el archivo ZIP y confirma la instalación.
4. Configura el RUT de tu OTEC y tu Token generado en [https://sistemas.sence.cl/rts](https://sistemas.sence.cl/rts) en:
   `Administración del sitio > Extensiones > Bloques > Conector SENCE Keystone Advisors`.
