# Contribuir a Employee Time Tracking

¡Gracias por tu interés en contribuir! Este documento proporciona directrices para ayudarte a empezar.

## Cómo contribuir

### Reportar errores

1. Revisa los [issues existentes](https://github.com/pvernocchi/employee-time-tracking/issues) para evitar duplicados
2. Abre un nuevo issue con un título y descripción claros
3. Incluye pasos para reproducir el error, comportamiento esperado y comportamiento real
4. Indica tu versión de PHP, versión de MySQL y entorno del servidor

### Sugerir funcionalidades

Abre un issue con la etiqueta **feature request**. Describe el caso de uso y cómo la funcionalidad beneficiaría a los usuarios.

### Enviar cambios

1. Haz un fork del repositorio
2. Crea una rama de funcionalidad desde `main`:
   ```bash
   git checkout -b feature/nombre-de-tu-funcionalidad
   ```
3. Realiza tus cambios siguiendo los estándares de código descritos a continuación
4. Prueba tus cambios localmente
5. Haz commit con mensajes claros y descriptivos
6. Haz push a tu fork y abre un pull request

## Configuración del entorno de desarrollo

### Requisitos previos

- PHP 8.1+
- MySQL 5.7+
- Apache con `mod_rewrite`
- Composer

### Entorno local

```bash
git clone https://github.com/tu-fork/employee-time-tracking.git
cd employee-time-tracking
composer install
cp config/config.example.php config/config.php
# Edita config/config.php con tus credenciales de base de datos local
# Importa database/schema.sql en tu instancia de MySQL
# Apunta tu servidor web local al directorio public/
```

### Validación

Ejecuta comprobaciones de sintaxis PHP en todo el código:

```bash
find src/ public/ config/ -name "*.php" -exec php -l {} \;
```

## Estándares de código

- **PHP 8.1+** — usa funcionalidades modernas de PHP (propiedades tipadas, argumentos con nombre, expresiones match, etc.)
- **Autoloading PSR-4** — todas las clases viven bajo el namespace `App\` en `src/`
- **Sin frameworks externos** — mantén el enfoque de PHP puro
- **Consultas preparadas** — usa siempre consultas parametrizadas para el acceso a base de datos
- **Escapado de salida** — escapa todo el contenido generado por usuarios en las vistas
- **Protección CSRF** — incluye tokens CSRF en todos los formularios

## Estructura del proyecto

- `src/Controllers/` — controladores de peticiones
- `src/Core/` — servicios a nivel de framework (Router, Database, Auth, I18n, etc.)
- `src/Views/` — plantillas PHP renderizadas en servidor
- `src/Lang/` — archivos de traducción
- `database/migrations/` — migraciones incrementales del esquema
- `public/` — raíz web con controlador frontal y recursos estáticos

## Directrices para Pull Requests

- Mantén los PRs enfocados en un solo tema
- Asegúrate de que la sintaxis PHP es válida (`php -l`) para todos los archivos modificados
- Actualiza las traducciones en `src/Lang/` si añades o cambias cadenas visibles para el usuario
- Añade migraciones de base de datos en `database/migrations/` para cambios de esquema — no modifiques `schema.sql` directamente
- Prueba con múltiples roles de usuario (admin, manager, empleado, inspector) si tus cambios afectan al control de acceso

## Licencia

Al contribuir, aceptas que tus contribuciones serán licenciadas bajo la [Licencia Pública General de GNU v3.0](LICENSE).
