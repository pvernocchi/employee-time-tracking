# ⏱️ Employee Time Tracking

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-0.0.2-brightgreen.svg)](VERSION)

Una plataforma ligera de control horario en **PHP puro** para equipos que necesitan gestión de asistencia, flujos de solicitud de ausencias y generación de informes de cumplimiento alineados con la legislación laboral española.

> 🇬🇧 [English version](README.md)

---

## ¿Por qué este proyecto?

La mayoría de herramientas de control horario son plataformas SaaS sobredimensionadas o aplicaciones autoalojadas complejas que requieren infraestructura con contenedores. Este proyecto adopta un enfoque diferente:

- **Sin dependencia de frameworks** — PHP 8.1+ puro con autoloading PSR-4, sin frameworks pesados que aprender
- **Compatible con hosting compartido** — funciona en cualquier stack LAMP con Apache, PHP y MySQL
- **Preparado para cumplimiento normativo** — diseñado en torno al **Real Decreto-ley 8/2019 / Art. 34.9 ET** con controles de horas diarios/semanales, monitorización de horas extra, alertas de descanso, registros de auditoría y exportaciones CSV para inspecciones
- **Control de acceso por roles** — cuatro roles diferenciados (Admin, Manager, Empleado, Inspector) con permisos apropiados
- **Interfaz multilingüe** — compatible con español, inglés, catalán, euskera y gallego
- **Soporte MFA** — TOTP y WebAuthn/FIDO2 (YubiKey, Windows Hello) para autenticación segura

---

## Funcionalidades

| Área | Descripción |
| --- | --- |
| **Panel de control** | Estadísticas personales, estado activo, ausencias pendientes, vista general del manager |
| **Control horario** | Fichaje de entrada/salida, registro de pausas, notas de turno |
| **Hojas de tiempo** | Desglose semanal y resúmenes mensuales |
| **Gestión de ausencias** | Solicitar, revisar, aprobar/rechazar y cancelar solicitudes |
| **Informes** | Informes filtrados con exportación CSV |
| **Cumplimiento** | Panel de alertas, registro de auditoría, exportaciones para inspección |
| **Portal del inspector** | Consulta de registros de empleados en modo lectura y exportaciones oficiales |
| **Seguridad** | Protección CSRF, MFA (TOTP + WebAuthn), gestión de sesiones |
| **Configuración admin** | Gestión de empleados, configuración SMTP, políticas de seguridad |

---

## Inicio rápido

### Requisitos previos

- PHP 8.1+
- MySQL 5.7+
- Apache con `mod_rewrite` habilitado
- [Composer](https://getcomposer.org/)

### Instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/pvernocchi/employee-time-tracking.git
cd employee-time-tracking

# 2. Instalar dependencias PHP
composer install --no-dev --optimize-autoloader

# 3. Apuntar el document root del servidor web al directorio public/
#    ej. /ruta/al/employee-time-tracking/public

# 4. Abrir la URL de la aplicación en el navegador
#    El instalador web se lanzará automáticamente en la primera visita
```

El instalador web integrado en `/install` te guiará a través de:

1. Configuración de la conexión a base de datos
2. Configuración de la aplicación (nombre, URL, zona horaria)
3. Creación de la cuenta de administrador
4. Migración del esquema de base de datos

Para instrucciones detalladas de despliegue, consulta la [Guía de instalación](install.md).
Para actualizar despliegues existentes, consulta la [Guía de actualización](update.md).

### Configuración manual (alternativa)

Si prefieres configurar manualmente en lugar de usar el instalador web:

```bash
cp config/config.example.php config/config.php
```

Edita `config/config.php` para establecer las credenciales de base de datos, URL de la aplicación, zona horaria y umbrales de cumplimiento. Luego importa el esquema:

```bash
mysql -u tu_usuario -p tu_base_de_datos < database/schema.sql
```

---

## Roles de usuario

| Rol | Acceso |
| --- | --- |
| **Admin** | Acceso completo — gestión de empleados, panel de cumplimiento, configuración de seguridad |
| **Manager** | Informes de equipo, aprobación de ausencias, vista general de la plantilla |
| **Empleado** | Fichaje entrada/salida, hojas de tiempo, solicitudes de ausencia, autoexportación |
| **Inspector** | Vistas de inspección en modo lectura y exportaciones oficiales de asistencia |

---

## Estructura del proyecto

```text
employee-time-tracking/
├── config/                  # Configuración de la app (config.example.php)
│
├── database/
│   ├── schema.sql           # Esquema completo de base de datos
│   └── migrations/          # Migraciones incrementales del esquema
│
├── public/                  # Raíz web (apuntar el servidor aquí)
│   ├── index.php            # Controlador frontal
│   └── assets/              # CSS, JS e iconos de banderas
│
├── src/
│   ├── Controllers/         # Controladores de peticiones
│   ├── Core/                # Router, Database, Auth, I18n, Servicios
│   ├── Lang/                # Archivos de traducción (es, en, ca, eu, gl)
│   └── Views/               # Plantillas PHP renderizadas en servidor
│
├── .github/workflows/       # CI/CD (despliegue FTP)
├── composer.json
├── install.md               # Guía de instalación
└── update.md                # Guía de actualización
```

---

## Despliegue

El repositorio incluye un workflow de GitHub Actions que ejecuta `composer install` y despliega vía **FTPS**, lo que lo hace adecuado para entornos de hosting compartido. Consulta `.github/workflows/` para la configuración del workflow.

---

## Seguridad

- **Tokens CSRF** en todos los formularios
- **Hashing de contraseñas** con `PASSWORD_DEFAULT` (bcrypt)
- **Consultas preparadas** para todas las operaciones de base de datos
- **Timeout de sesión** con tiempo de vida configurable
- **Escapado de salida** en todas las vistas
- **Autenticación multifactor** — apps de autenticación TOTP y llaves hardware WebAuthn/FIDO2
- **Políticas de seguridad configurables por el admin** — forzar MFA, gestionar credenciales de usuarios

---

## Obtener ayuda

- **Issues** — [Abre un issue](https://github.com/pvernocchi/employee-time-tracking/issues) para reportar errores o solicitar funcionalidades
- **Problemas de instalación** — Revisa la sección de resolución de problemas en la [Guía de instalación](install.md)
- **Referencia de configuración** — Consulta [`config/config.example.php`](config/config.example.php) para ver todos los ajustes disponibles y sus valores por defecto

---

## Contribuir

¡Las contribuciones son bienvenidas! Lee [CONTRIBUTING.es.md](CONTRIBUTING.es.md) para conocer las directrices sobre cómo enviar cambios, reportar errores y sugerir mejoras.

---

## Licencia

Este proyecto está licenciado bajo la **Licencia Pública General de GNU v3.0** — consulta el archivo [LICENSE](LICENSE) para más detalles.

---

## Mantenedores

Este proyecto está mantenido por [@pvernocchi](https://github.com/pvernocchi).
