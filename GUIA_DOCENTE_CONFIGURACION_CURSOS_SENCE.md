# 📘 Guía Docente: Creación y Configuración Óptima de Cursos SENCE en Moodle

Esta guía está diseñada para **profesores, diseñadores instruccionales y administradores** de la OTEC. Explica paso a paso cómo estructurar un curso en Moodle para que el **cómputo de avance académico** y el **registro de asistencia SENCE** funcionen de manera 100% automática, transparente y sin fricción para el estudiante.

---

## 🎯 1. Principio Fundamental: Asistencia SENCE vs. Avance Moodle

En capacitaciones e-learning bajo franquicia tributaria SENCE existen dos requisitos que corren en paralelo:

1. **Asistencia Cronológica SENCE (Fiscalizable por ClaveÚnica):**  
   - Medida automáticamente por el plugin **Conector SENCE Keystone Advisors**.
   - Exige que el alumno esté conectado con sesión activa al menos el **75% de las horas totales** del curso (ej: 15 horas en un curso de 20h).
2. **Avance y Completitud Académica Moodle:**  
   - Medido por el motor nativo de **Rastreo de Finalización (Completion Tracking)** de Moodle.
   - Demuestra que el participante efectivamente leyó los contenidos, vio las cápsulas y rindió las evaluaciones obligatorias.

---

## ⚙️ 2. Paso 1: Configurar el Curso a Nivel Global

Antes de agregar contenidos, asegúrate de activar el rastreo en los ajustes generales del curso:

1. Ve a **Configuración** del curso (menú superior).
2. Despliega la sección **Rastreo de finalización**:
   - **Habilitar rastreo de finalización:** Seleccionar **SÍ** (*Obligatorio*).
   - **Mostrar condiciones de finalización de la actividad:** Seleccionar **SÍ**.
3. Guarda los cambios.

---

## 📝 3. Paso 2: Configurar Cada Actividad (Evitar el Marcado Manual)

> ⚠️ **Error muy común:** Dejar la finalización en *"Los estudiantes pueden marcar manualmente la actividad como completada"*.  
> **Problema:** Los alumnos leen los documentos o ven los videos pero olvidan presionar el botón "Marcar como hecho", lo que hace que su barra de avance quede estancada en 0%.

### A. Para Archivos PDF, Resúmenes, Guías o Enlaces a Videos (Recursos / URLs / Páginas)
1. Al crear o editar el recurso, ve a la sección **Finalización de actividad**.
2. **Rastreo de finalización:** Elige **"Mostrar la actividad como completada cuando se cumplan las condiciones"**.
3. Marca la casilla:
   - ☑️ **El estudiante debe ver esta actividad para completarla**.
4. *Resultado:* En cuanto el alumno hace clic y abre el contenido, Moodle le suma inmediatamente el porcentaje de avance correspondiente.

### B. Para Cuestionarios, Quizzes y Evaluaciones
1. En los ajustes del Cuestionario, ve a **Finalización de actividad**.
2. **Rastreo de finalización:** Elige **"Mostrar la actividad como completada cuando se cumplan las condiciones"**.
3. Marca las condiciones académicas:
   - ☑️ **Requerir calificación:** *El estudiante debe recibir una calificación para completar esta actividad*.
   - *(Opcional)* ☑️ **Requerir calificación de aprobado:** Si la OTEC exige un puntaje mínimo (ej. nota 4.0 o 70% de aprobación) para dar por superada la evaluación.
4. *Resultado:* Al enviar el intento, la nota se registra en el libro de calificaciones y la evaluación se marca completada automáticamente.

### C. Para Foros de Consultas o Etiquetas Visuales
- En etiquetas, separadores gráficos o foros informativos generales, selecciona **"No indicar finalización de la actividad"** para no inflar artificialmente el número de tareas requeridas.

---

## 🏢 4. Paso 3: Asociar el Curso al Conector SENCE

Una vez montada la estructura académica:

1. Activa el modo de edición y agrega el bloque **"Conector SENCE Keystone Advisors"** en la barra lateral de la portada del curso.
2. Como docente/administrador, haz clic en **"Ajustes Curso SENCE"** en el bloque:
   - **Habilitar SENCE para este curso:** Marcar casilla.
   - **Horas Totales del Curso:** Ingresar las horas cronológicas aprobadas en SENCE (ej. `20`).
   - **Asistencia Mínima Requerida:** `75`%.
   - **Código SENCE:** Código oficial de la actividad (ej. `1234567890` o `-1` para pruebas).
   - **ID Acción / Código de Oferta:** (Ej. `CURSO-25` o por defecto el nombre del Grupo si es multi-empresa).
3. Guarda los cambios.

---

## 👥 5. Paso 4: Cursos Multi-Empresa con Diferentes ID de Acción (Grupos)

Si en un mismo curso conviven participantes de distintas empresas u órdenes SENCE:
1. Ve a **Participantes > Grupos**.
2. Crea un grupo con el identificador exacto de la acción SENCE (ejemplo: `SENCE-6654959` o `ACCION-88412`).
3. Asigna a los participantes de esa empresa a dicho grupo.
4. El plugin reconocerá automáticamente ese código al momento de reportar la asistencia ante el Web Service de SENCE y segmentará la liquidación en el **Centro de Informes y Auditoría SENCE**.

---

## 🔍 6. Lista de Chequeo Rápida (Checklist del Docente)

| Elemento | Configuración Correcta | ¿Por qué? |
| :--- | :--- | :--- |
| **Rastreo de finalización en el curso** | **Activado (SÍ)** | Permite que Moodle mida el progreso. |
| **PDFs, Guías y Videos** | **Completar al ver la actividad** | Se marca automáticamente al abrir el contenido. |
| **Cuestionarios y Evaluaciones** | **Completar al recibir calificación** | Evita que el alumno deba marcar casillas a mano. |
| **Bloque Conector SENCE** | **Insertado en la portada** | Protege todo el curso y activa el dashboard con cronómetro. |
| **Ambiente de Pruebas** | **Modo QA / Sandbox activo** | Permite al docente simular la vista del estudiante antes del inicio formal. |

---

*Manual elaborado y mantenido por **Keystone Advisors** | Especialistas en Plataformas E-Learning & Integraciones SENCE.*
