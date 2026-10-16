# Manual de Instalación y Configuración
## Conector SENCE Keystone Advisors (v1.0.0)
**Para Plataformas Moodle 4.1 LTS, 4.4, 4.5 LTS y 5.x**

---

### Desarrollado por:
* **Entidad:** Keystone Advisors
* **Sitio Web:** [www.keystoneadvisors.cl](https://www.keystoneadvisors.cl)
* **Dirección:** Avenida Apoquindo 6410, Oficina 605, Las Condes, Santiago, Chile.
* **Licencia:** Gratuita (GPL v3)

---

## 1. Instalación del Plugin en Moodle

Existen dos alternativas sencillas para instalar el plugin:

### Opción A: Desde el Panel de Administración de Moodle (Recomendada)
1. Inicia sesión como **Administrador** en tu plataforma Moodle.
2. Ve a: **Administración del sitio > Extensiones (Plugins) > Instalar extensiones**.
3. Arrastra y suelta el archivo comprimido **`block_sence_connector.zip`** en la caja de carga.
4. Haz clic en **Instalar extensión desde archivo ZIP**.
5. Moodle realizará la comprobación de compatibilidad. Haz clic en **Continuar** y luego en **Actualizar base de datos de Moodle ahora**.

### Opción B: Vía Servidor / SSH
Si prefieres subirlo directamente a la carpeta de tu servidor:
```bash
# Descomprimir en la carpeta de bloques de Moodle
unzip block_sence_connector.zip -d /ruta-a-tu-moodle/blocks/
```
Luego entra al navegador a `https://tu-moodle.com/admin` para completar la instalación.

---

## 2. Paso 1: Configuración Global del OTEC (Nivel de Sitio)

Una vez instalado, debes parametrizar los datos de tu institución autorizada por SENCE:

1. Ve a: **Administración del sitio > Extensiones > Bloques > Conector SENCE Keystone Advisors**.
2. Completa los siguientes campos:

| Parámetro | Valor Requerido | Descripción / Dónde obtenerlo |
| :--- | :--- | :--- |
| **RUT del OTEC** | `xxxxxxxx-x` | RUT de tu institución, sin puntos y con guión (ej: `76111222-3`). |
| **Token OTEC SENCE** | Clave de 36 caracteres | Ingresa con el RUT de empresa, RUT del Representante Legal (SII) y Clave SENCE a: 👉 [https://sistemas.sence.cl/rts](https://sistemas.sence.cl/rts). Allí generas tu Token alfanumérico. |
| **Identificador de Sistema (idSistema)** | `1350` | Código estándar del sistema Gestor Intermedio fijado por SENCE en el instructivo v2.0. |
| **Ambiente de Conexión** | `Pruebas (rcetest)` o `Producción` | Usa **Pruebas** para homologar la conexión con SENCE o **Producción** cuando el curso esté en ejecución oficial. |
| **Campo de Perfil para RUN** | `idnumber` | Campo de Moodle donde almacenas el RUT del alumno (por defecto `idnumber` / Número de ID). |
| **Ventana Máxima de Inactividad** | `15` | Minutos de inactividad pasiva permitidos antes de detener el cómputo de asistencia. |

3. Haz clic en **Guardar cambios**.

---

## 3. Paso 2: Habilitación y Configuración de un Curso SENCE

No todos los cursos de Moodle son necesariamente acciones formativas SENCE. Debes activar la sincronización en los cursos específicos:

1. Ingresa al curso en Moodle como **Profesor con permiso de edición** o **Administrador**.
2. **Activa el modo de edición** (botón superior derecho).
3. Haz clic en **Añadir un bloque** (en el panel lateral o en la región que prefieras de tu tema).
4. Selecciona de la lista: **Asistencia SENCE (Conector SENCE Keystone Advisors)**.
5. Dentro del bloque recién creado, haz clic en el botón: **"Ajustes Curso SENCE"** (o ve a Administración del curso > Configuración SENCE).
6. Completa la ficha de la acción formativa:
   - **Habilitar SENCE en este curso:** Marca la casilla (On).
   - **Código SENCE de la Acción:** Código asignado por SOLICURSO (si estás en pruebas de homologación, usa `-1`).
   - **Código de Oferta (SIC):** Código de la oferta en el SIC de SENCE (ej: `CAP-15-01-13-0446`).
   - **Código de Grupo (SIC):** Identificador del grupo (por defecto `1`).
   - **Línea de Capacitación:** `3` (Impulsa Personas / Franquicia Tributaria) o la que corresponda.
   - **Horas Cronológicas Totales:** Total de horas aprobadas del curso (ej: `20.0` o `40.0`).
   - **Asistencia Mínima Requerida (%):** Porcentaje exigido por norma (ej: `75.0%` u `80.0%`).
   - **Calificación Mínima:** Nota mínima de aprobación (ej: `4.0`).
7. Haz clic en **Guardar cambios**.

---

## 4. Paso 3: Experiencia del Participante (Alumnos)

Cuando un participante ingresa al curso:
1. En el bloque de SENCE verá una alerta que le recuerda registrar su asistencia.
2. Al pulsar **"Iniciar Asistencia ClaveÚnica"**:
   - Moodle lo redirige de forma cifrada al portal oficial de SENCE.
   - El alumno inicia sesión con su **ClaveÚnica** oficial del Estado chileno.
   - SENCE valida su identidad y lo retorna automáticamente a Moodle.
3. El bloque cambiará a estado **🟢 Asistencia SENCE Activa**, comenzando a registrar el tiempo efectivo de estudio.
4. Al finalizar su sesión de estudio, el alumno puede hacer clic en **"Cerrar Asistencia SENCE"** para consolidar sus horas formalmente.

---

## 5. Paso 4: Monitoreo, Informes y Libro de Asistencia

Para supervisores, relatores y coordinadores del OTEC:

1. En el bloque del curso, haz clic en el botón: **"Libro de Asistencia SENCE"**.
2. Tendrás acceso inmediato a:
   - **Tarjetas KPI:** Alumnos inscritos, conectados hoy, horas acumuladas y estado de la última sincronización con la API de SENCE.
   - **Tabla Detallada:** RUN de cada alumno, estado de su sesión actual (Activo/Desconectado), horas efectivas acumuladas, % de cumplimiento y estado ante SENCE (`Cursando`, `Aprobado`, `Reprobado`).
   - **Sincronización Manual Inmediata:** Si necesitas reportar avances en el momento a los servidores de SENCE sin esperar la tarea programada automática nocturna, simplemente pulsa el botón **"Sincronizar Ahora con SIC"**.

---

## 6. Sincronización Automática en Segundo Plano (Cron de Moodle)

El plugin registra automáticamente una tarea programada oficial en el núcleo de Moodle:
* **Tarea:** `\block_sence_connector\task\sync_sic_task`
* **Frecuencia:** Cada 30 minutos (configurable en *Administración del sitio > Servidor > Tareas programadas*).
* **Función:** Recopila el avance de los alumnos y lo envía vía HTTPS POST directamente al Gestor Intermedio de SENCE (`https://auladigital.sence.cl/gestor/API/avance-sic/enviarAvance`).

---

## 7. Preguntas Frecuentes y Soporte

* **¿Qué ocurre si un participante no tiene RUN registrado?**  
  El sistema ignora a usuarios que no posean un RUN válido configurado (por ejemplo, cuentas de soporte técnico o administradores), evitando enviar registros erróneos a SENCE.
* **¿Dónde obtengo soporte técnico adicional?**  
  Puedes contactar directamente al equipo de consultoría e ingeniería de **Keystone Advisors** a través de [www.keystoneadvisors.cl](https://www.keystoneadvisors.cl) o en nuestras oficinas en Avenida Apoquindo 6410, Oficina 605, Las Condes, Santiago.
