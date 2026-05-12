# ⏱️ Employee Time Tracking

[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL 5.7+](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Licencia GPLv3](https://img.shields.io/badge/Licencia-GPLv3-blue.svg)](LICENSE)
[![Versión 0.2.2](https://img.shields.io/badge/Versi%C3%B3n-0.2.2-brightgreen.svg)](VERSION)
[![Despliegue por FTP](https://github.com/pvernocchi/employee-time-tracking/actions/workflows/ftp-deploy.yml/badge.svg)](.github/workflows/ftp-deploy.yml)

**Employee Time Tracking** es una aplicación web ligera de fichaje y gestión de jornada para equipos que necesitan registrar horas, administrar ausencias y preparar informes de cumplimiento laboral en España.

Está desarrollada en **PHP 8.1+ sin framework**, usa **MySQL**, se instala mediante Composer y está pensada para entornos LAMP o alojamientos compartidos donde el directorio público del sitio apunta a `public/`.

---

## ✨ Qué hace el proyecto

La aplicación centraliza el registro horario y la gestión operativa de empleados:

- 🕒 **Fichaje de entrada y salida** con seguimiento de pausas y notas de jornada.
- 📅 **Hojas de horas** semanales y mensuales para empleados y responsables.
- 🏖️ **Solicitudes de permisos y vacaciones** con revisión, aprobación, rechazo y cancelación.
- 📊 **Informes y exportaciones CSV** para registros de jornada, horas extra e incidencias.
- ⚖️ **Cumplimiento laboral** con límites diarios, semanales, descansos, auditoría y exportación para inspecciones.
- 🔐 **Seguridad integrada** con sesiones, CSRF, contraseñas cifradas, TOTP y WebAuthn/FIDO2.
- 🌍 **Interfaz multidioma** con soporte para español, inglés, catalán, euskera y gallego.

---

## 🚀 Por qué es útil

Employee Time Tracking está diseñado para organizaciones que quieren una solución autocontenida y fácil de mantener:

- **Sin dependencia de un SaaS externo**: los datos permanecen en la infraestructura de la organización.
- **Compatible con hosting compartido**: no requiere contenedores ni un framework pesado.
- **Preparado para el contexto español**: incluye valores por defecto alineados con el registro obligatorio de jornada y conservación de datos.
- **Roles claros**: administrador, responsable, empleado e inspector tienen permisos diferenciados.
- **Instalación guiada**: el instalador web crea la configuración, ejecuta migraciones y permite crear el primer administrador.

---

## ⚖️ Normativas españolas cubiertas

El proyecto incluye funcionalidades orientadas al cumplimiento de las siguientes obligaciones laborales y de protección de datos aplicables en España. La adecuación legal final depende de la configuración, las políticas internas y el uso que haga cada organización.

| Normativa o referencia | Soporte incluido en la aplicación |
| --- | --- |
| **Real Decreto-ley 8/2019**, que introduce el registro obligatorio de jornada | Registro diario de entrada/salida, auditoría de cambios, conservación configurable y exportaciones preparadas para inspección. |
| **Estatuto de los Trabajadores, art. 34.9** | Registro de jornada por persona trabajadora, disponibilidad de registros, portal de inspector y exportación individual de horas. |
| **Estatuto de los Trabajadores, art. 34** | Alertas para jornada diaria máxima de 9 horas, jornada semanal de 40 horas, descanso mínimo entre jornadas de 12 horas y pausa mínima de 15 minutos cuando la jornada supera 6 horas. |
| **Estatuto de los Trabajadores, art. 35.2** | Control del límite anual de 80 horas extraordinarias y avisos cuando se aproxima o se supera. |
| **Estatuto de los Trabajadores, art. 37.3** | Catálogo de permisos retribuidos estatutarios, como matrimonio, fallecimiento de familiar, mudanza y cumplimiento de deber público. |
| **Estatuto de los Trabajadores, art. 38** | Gestión de vacaciones con mínimo estatutario configurable de 22 días laborables. |
| **Estatuto de los Trabajadores, art. 48 y 48.4** | Categorías de permisos de maternidad y paternidad/nacimiento y cuidado de menor con mínimos estatutarios. |
| **RGPD, art. 6.1.b y 6.1.c** | Aviso de protección de datos con base jurídica por relación laboral y cumplimiento de obligación legal. |
| **Ley Orgánica 3/2018 (LOPDGDD)** | Registro de consentimiento/conocimiento del aviso de privacidad, derechos de las personas trabajadoras y trazabilidad con dirección IP. |
| **Criterios de conservación para inspección laboral** | Retención de registros de jornada durante 4 años y exportaciones para Inspección de Trabajo y Seguridad Social. |

---

## 📦 Requisitos

Antes de empezar, asegúrate de tener:

- PHP **8.1 o superior**.
- MySQL **5.7 o superior**.
- Apache con `mod_rewrite` habilitado.
- [Composer](https://getcomposer.org/).
- Acceso para apuntar la raíz web del sitio al directorio `public/`.

---

## 🛠️ Primeros pasos

### 1. Obtener el código

```bash
git clone https://github.com/pvernocchi/employee-time-tracking.git
cd employee-time-tracking
```

### 2. Instalar dependencias

Para producción:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

Para desarrollo local:

```bash
composer install
```

### 3. Preparar el servidor web

Configura Apache para que la raíz del sitio apunte a:

```text
/path/to/employee-time-tracking/public
```

El archivo `public/index.php` actúa como controlador frontal y redirige al instalador cuando la aplicación aún no está configurada.

### 4. Ejecutar el instalador web

Abre la URL de la aplicación en el navegador. Si no existe `config/config.php` o la base de datos no está instalada, la aplicación redirige automáticamente a `/install`.

El instalador solicita:

1. Datos de la aplicación: nombre, URL y zona horaria.
2. Credenciales de MySQL.
3. Datos del primer usuario administrador.
4. Confirmación para crear la configuración y aplicar migraciones.

Para instrucciones más detalladas, consulta la [guía de instalación](install.md). Para actualizar una instalación existente, consulta la [guía de actualización](update.md).

---

## 💡 Ejemplos de uso

Después de iniciar sesión:

- Un **empleado** puede fichar desde `/clock`, revisar su jornada en `/timesheet` y solicitar permisos en `/leave/request`.
- Un **responsable** puede revisar solicitudes en `/admin/leave` y consultar informes de equipo en `/admin/reports`.
- Un **administrador** puede gestionar empleados en `/admin/employees`, configurar seguridad en `/admin/security` y revisar cumplimiento en `/compliance`.
- Un **inspector** puede acceder a vistas de solo lectura y exportaciones desde `/inspector`.

---

## 🧭 Estructura del proyecto

```text
employee-time-tracking/
├── config/                  # Configuración de ejemplo y configuración local
├── cron/                    # Tareas programadas de notificaciones
├── database/
│   ├── schema.sql           # Esquema base
│   └── migrations/          # Migraciones incrementales
├── public/                  # Raíz web del servidor
│   ├── index.php            # Controlador frontal
│   └── assets/              # Recursos estáticos
├── src/
│   ├── Controllers/         # Controladores HTTP
│   ├── Core/                # Autenticación, base de datos, router y servicios
│   ├── Lang/                # Traducciones
│   └── Views/               # Plantillas PHP renderizadas en servidor
├── .github/workflows/       # Automatización de despliegue
├── composer.json            # Dependencias y autoload PSR-4
├── install.md               # Guía de instalación
└── update.md                # Guía de actualización
```

---

## ⚙️ Configuración y despliegue

La configuración base se encuentra en [`config/config.example.php`](config/config.example.php). El instalador crea `config/config.php`; si necesitas configurar manualmente, copia el ejemplo y ajusta valores de aplicación, base de datos, sesión, cumplimiento y SMTP.

```bash
cp config/config.example.php config/config.php
```

También puedes importar el esquema base si decides no usar el instalador:

```bash
mysql -u usuario -p nombre_base_datos < database/schema.sql
```

El repositorio incluye un flujo de GitHub Actions en [`.github/workflows/ftp-deploy.yml`](.github/workflows/ftp-deploy.yml) que instala dependencias de producción y despliega por FTPS usando secretos del repositorio.

---

## ✅ Validación en desarrollo

No hay scripts dedicados de prueba en Composer. Para validar cambios PHP, ejecuta comprobaciones de sintaxis:

```bash
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

Si modificas vistas o flujos de usuario, prueba manualmente los roles afectados antes de abrir un pull request.

---

## 🆘 Dónde obtener ayuda

- 📖 Revisa la [guía de instalación](install.md) y la [guía de actualización](update.md).
- 🧩 Consulta [`config/config.example.php`](config/config.example.php) para conocer las opciones disponibles.
- 🐞 Para errores o solicitudes de mejora, abre un issue en el repositorio del proyecto.
- 🔐 Para dudas de seguridad o autenticación multifactor, revisa las pantallas de administración y las opciones de `/admin/security`.

---

## 🤝 Mantenimiento y contribuciones

El proyecto es mantenido por [@pvernocchi](https://github.com/pvernocchi).

Las contribuciones son bienvenidas. Antes de enviar cambios:

1. Lee [`CONTRIBUTING.md`](CONTRIBUTING.md).
2. Mantén los pull requests enfocados en un único objetivo.
3. Valida la sintaxis PHP de los archivos modificados.
4. Añade migraciones en `database/migrations/` cuando cambies el esquema.
5. Actualiza traducciones en `src/Lang/` si añades texto visible para usuarios.

---

## 📄 Licencia

Este proyecto se distribuye bajo la **Licencia Pública General de GNU v3.0**. Consulta [`LICENSE`](LICENSE) para más información.
