<?php

namespace App\Core;

class I18n
{
    private static string $locale = 'es';

    private static array $translations = [];

    private static array $supportedLocales = ['es', 'en', 'ca', 'eu', 'gl'];

    private static array $localeMeta = [
        'es' => ['abbr' => 'ES', 'flag' => '/assets/flags/es.svg'],
        'en' => ['abbr' => 'EN', 'flag' => '/assets/flags/en.svg'],
        'ca' => ['abbr' => 'CA', 'flag' => '/assets/flags/ca.svg'],
        'eu' => ['abbr' => 'EU', 'flag' => '/assets/flags/eu.svg'],
        'gl' => ['abbr' => 'GL', 'flag' => '/assets/flags/gl.svg'],
    ];

    private static array $contentTranslations = [
        'es' => [
            'Dashboard' => 'Panel',
            'Welcome back,' => 'Bienvenido de nuevo,',
            'Today' => 'Hoy',
            'This Week' => 'Esta semana',
            'Status' => 'Estado',
            'Clocked In' => 'Fichado',
            'Clocked Out' => 'Sin fichar',
            'Pending Leave' => 'Permisos pendientes',
            'Currently Clocked In' => 'Actualmente fichado',
            'Since:' => 'Desde:',
            'Duration:' => 'Duración:',
            'Break (min)' => 'Pausa (min)',
            'Notes' => 'Notas',
            'Optional notes...' => 'Notas opcionales...',
            'Clock Out' => 'Fichar salida',
            'Ready to Work?' => '¿Listo para trabajar?',
            'Clock In' => 'Fichar entrada',
            'Admin Overview' => 'Resumen de administración',
            'Active Employees' => 'Empleados activos',
            'Pending Leave Requests' => 'Solicitudes pendientes',
            'Clock In / Out' => 'Fichar entrada/salida',
            '⚠️ Compliance Alerts:' => '⚠️ Alertas de cumplimiento:',
            '🟢 You are clocked in' => '🟢 Has fichado entrada',
            'Started:' => 'Inicio:',
            'Break Duration (minutes)' => 'Duración de pausa (minutos)',
            'Notes (optional)' => 'Notas (opcional)',
            'What did you work on?' => '¿En qué has trabajado?',
            '🔴 You are not clocked in' => '🔴 No has fichado entrada',
            'Click the button below to start tracking your time.' => 'Pulsa el botón para empezar a registrar tu jornada.',
            "Today's Entries" => 'Registros de hoy',
            'Break' => 'Pausa',
            'Hours' => 'Horas',
            'Active' => 'Activo',
            'Weekly Timesheet' => 'Jornada semanal',
            '← Previous' => '← Anterior',
            'Next →' => 'Siguiente →',
            'Weekly' => 'Semanal',
            'Monthly' => 'Mensual',
            '📤 Export My Records' => '📤 Exportar mis registros',
            '⚠️ Weekly hours exceeded:' => '⚠️ Horas semanales excedidas:',
            'You have worked' => 'Has trabajado',
            'h this week. The legal maximum is 40h/week (Art. 34 ET).' => 'h esta semana. El máximo legal es 40h/semana (Art. 34 ET).',
            '⚠️ Annual overtime:' => '⚠️ Horas extra anuales:',
            'h of 80h maximum (Art. 35.2 ET).' => 'h de un máximo de 80h (Art. 35.2 ET).',
            'LIMIT EXCEEDED.' => 'LÍMITE SUPERADO.',
            'Day' => 'Día',
            'Date' => 'Fecha',
            'Entries' => 'Registros',
            'Total' => 'Total',
            'hrs' => 'h',
            'Exceeds 40h' => 'Supera 40h',
            'Monthly Timesheet' => 'Jornada mensual',
            'Current' => 'Actual',
            'Total Hours:' => 'Horas totales:',
            'Days Worked:' => 'Días trabajados:',
            'Avg Hours/Day:' => 'Media horas/día:',
            'Leave Management' => 'Gestión de permisos',
            '+ New Request' => '+ Nueva solicitud',
            'days remaining' => 'días restantes',
            'My Leave Requests' => 'Mis solicitudes de permiso',
            'Type' => 'Tipo',
            'From' => 'Desde',
            'To' => 'Hasta',
            'Days' => 'Días',
            'Reviewed By' => 'Revisado por',
            'Actions' => 'Acciones',
            'Cancel this request?' => '¿Cancelar esta solicitud?',
            'Cancel' => 'Cancelar',
            'No leave requests found.' => 'No se encontraron solicitudes de permiso.',
            'Request Leave' => 'Solicitar permiso',
            '← Back to Leave' => '← Volver a permisos',
            'Leave Type' => 'Tipo de permiso',
            'Select type...' => 'Selecciona tipo...',
            'Start Date' => 'Fecha de inicio',
            'End Date' => 'Fecha de fin',
            'Reason (optional)' => 'Motivo (opcional)',
            'Submit Request' => 'Enviar solicitud',
            'Manage Employees' => 'Gestionar empleados',
            'Employees' => 'Empleados',
            '+ Add Employee' => '+ Añadir empleado',
            'Name' => 'Nombre',
            'Email' => 'Correo',
            'Role' => 'Rol',
            'Department' => 'Departamento',
            'Rate' => 'Tarifa',
            'Inactive' => 'Inactivo',
            'Edit' => 'Editar',
            'Add Employee' => 'Añadir empleado',
            'Edit Employee' => 'Editar empleado',
            '← Back' => '← Volver',
            'First Name *' => 'Nombre *',
            'Last Name *' => 'Apellidos *',
            'Email Address *' => 'Correo electrónico *',
            'Password *' => 'Contraseña *',
            'Hourly Rate ($)' => 'Tarifa por hora (€)',
            'Create Employee' => 'Crear empleado',
            'Update Employee' => 'Actualizar empleado',
            'Reports & Export' => 'Informes y exportación',
            'Generate Timesheet Report' => 'Generar informe de jornada',
            'All Employees' => 'Todos los empleados',
            'Format' => 'Formato',
            'View in Browser' => 'Ver en navegador',
            'Generate Report' => 'Generar informe',
            'Report Results' => 'Resultados del informe',
            'Showing entries from' => 'Mostrando registros desde',
            'No entries found for this period.' => 'No se encontraron registros para este período.',
            'Overtime Report' => 'Informe de horas extra',
            '← Back to Reports' => '← Volver a informes',
            'Annual overtime limit: 80 hours (Art. 35.2 Estatuto de los Trabajadores)' => 'Límite anual de horas extra: 80 horas (Art. 35.2 ET)',
            'Overtime Hours' => 'Horas extra',
            'Remaining' => 'Restantes',
            '⚠️ Exceeded' => '⚠️ Excedido',
            'Approaching' => 'Acercándose',
            'OK' => 'OK',
            'Compliance Dashboard' => 'Panel de cumplimiento',
            'Spanish Labor Law Compliance (Real Decreto-ley 8/2019)' => 'Cumplimiento laboral español (Real Decreto-ley 8/2019)',
            'Current Violations' => 'Incumplimientos actuales',
            '⚠️ Active Compliance Alerts' => '⚠️ Alertas activas de cumplimiento',
            'Alert' => 'Alerta',
            'Severity' => 'Severidad',
            '✅ No compliance violations detected.' => '✅ No se detectaron incumplimientos.',
            '📝 Recent Audit Activity' => '📝 Actividad reciente de auditoría',
            'User' => 'Usuario',
            'Action' => 'Acción',
            'Entry Date' => 'Fecha de registro',
            'View Full Audit Log →' => 'Ver auditoría completa →',
            '📤 Export for Labor Inspection' => '📤 Exportar para inspección de trabajo',
            'Export CSV for Inspection' => 'Exportar CSV para inspección',
            'Audit Log' => 'Registro de auditoría',
            '← Back to Compliance' => '← Volver a cumplimiento',
            'Total records:' => 'Registros totales:',
            'Date/Time' => 'Fecha/Hora',
            'Performed By' => 'Realizado por',
            'Entry' => 'Registro',
            'IP Address' => 'Dirección IP',
            'Details' => 'Detalles',
            '📋 Changes' => '📋 Cambios',
            'Page' => 'Página',
            'Labor Inspector - Overview' => 'Inspector laboral - Resumen',
            '📤 Export All Records' => '📤 Exportar todos los registros',
            'Employee Directory' => 'Directorio de empleados',
            'Total Entries' => 'Registros totales',
            'Last Activity' => 'Última actividad',
            'View Records' => 'Ver registros',
            '← Back to Overview' => '← Volver al resumen',
            'Employed Since' => 'Empleado desde',
            'Filter Records' => 'Filtrar registros',
            'Filter' => 'Filtrar',
            'Time Records (' => 'Registros de jornada (',
            'entries)' => 'registros)',
            'Locked' => 'Bloqueado',
            '📝 Audit Trail' => '📝 Historial de auditoría',
        ],
        'ca' => [
            'Dashboard' => 'Tauler',
            'Today' => 'Avui',
            'This Week' => 'Aquesta setmana',
            'Status' => 'Estat',
            'Clocked In' => 'Fitxat',
            'Clocked Out' => 'Sense fitxar',
            'Pending Leave' => 'Permisos pendents',
            'Clock In' => 'Fitxar entrada',
            'Clock Out' => 'Fitxar sortida',
            'Weekly Timesheet' => 'Jornada setmanal',
            'Monthly Timesheet' => 'Jornada mensual',
            'Weekly' => 'Setmanal',
            'Monthly' => 'Mensual',
            'Leave Management' => 'Gestió de permisos',
            'Request Leave' => 'Sol·licitar permís',
            'Employees' => 'Empleats',
            'Reports & Export' => 'Informes i exportació',
            'Compliance Dashboard' => 'Tauler de compliment',
            'Audit Log' => 'Registre d’auditoria',
            'Active' => 'Actiu',
            'Inactive' => 'Inactiu',
            'Cancel' => 'Cancel·lar',
            'Status' => 'Estat',
            'Department' => 'Departament',
        ],
        'eu' => [
            'Dashboard' => 'Panela',
            'Today' => 'Gaur',
            'This Week' => 'Aste hau',
            'Status' => 'Egoera',
            'Clocked In' => 'Sarrera markatuta',
            'Clocked Out' => 'Irteera markatuta',
            'Pending Leave' => 'Baimen zainak',
            'Clock In' => 'Sarrera markatu',
            'Clock Out' => 'Irteera markatu',
            'Weekly Timesheet' => 'Asteko lanaldia',
            'Monthly Timesheet' => 'Hileko lanaldia',
            'Weekly' => 'Astekoa',
            'Monthly' => 'Hilekoa',
            'Leave Management' => 'Baimenen kudeaketa',
            'Request Leave' => 'Baimena eskatu',
            'Employees' => 'Langileak',
            'Reports & Export' => 'Txostenak eta esportazioa',
            'Compliance Dashboard' => 'Betetze panela',
            'Audit Log' => 'Auditoria erregistroa',
            'Active' => 'Aktibo',
            'Inactive' => 'Inaktibo',
            'Cancel' => 'Ezeztatu',
            'Department' => 'Saila',
        ],
        'gl' => [
            'Dashboard' => 'Panel',
            'Today' => 'Hoxe',
            'This Week' => 'Esta semana',
            'Status' => 'Estado',
            'Clocked In' => 'Fichado',
            'Clocked Out' => 'Sen fichar',
            'Pending Leave' => 'Permisos pendentes',
            'Clock In' => 'Fichar entrada',
            'Clock Out' => 'Fichar saída',
            'Weekly Timesheet' => 'Xornada semanal',
            'Monthly Timesheet' => 'Xornada mensual',
            'Weekly' => 'Semanal',
            'Monthly' => 'Mensual',
            'Leave Management' => 'Xestión de permisos',
            'Request Leave' => 'Solicitar permiso',
            'Employees' => 'Empregados',
            'Reports & Export' => 'Informes e exportación',
            'Compliance Dashboard' => 'Panel de cumprimento',
            'Audit Log' => 'Rexistro de auditoría',
            'Active' => 'Activo',
            'Inactive' => 'Inactivo',
            'Cancel' => 'Cancelar',
            'Department' => 'Departamento',
        ],
    ];

    public static function init(array $appConfig = []): void
    {
        $defaultLocale = self::sanitizeLocale($appConfig['default_locale'] ?? 'es');
        $configSupportedLocales = array_filter(
            array_map([self::class, 'sanitizeLocale'], (array) ($appConfig['supported_locales'] ?? self::$supportedLocales))
        );

        self::$supportedLocales = !empty($configSupportedLocales) ? array_values(array_unique($configSupportedLocales)) : self::$supportedLocales;

        $requestedLocale = self::sanitizeLocale($_GET['lang'] ?? '');
        if ($requestedLocale && in_array($requestedLocale, self::$supportedLocales, true)) {
            $_SESSION['lang'] = $requestedLocale;
        }

        $sessionLocale = self::sanitizeLocale($_SESSION['lang'] ?? '');
        self::$locale = $sessionLocale && in_array($sessionLocale, self::$supportedLocales, true)
            ? $sessionLocale
            : $defaultLocale;

        if (!in_array(self::$locale, self::$supportedLocales, true)) {
            self::$locale = self::$supportedLocales[0];
        }

        self::$translations = self::loadTranslations(self::$locale);
    }

    public static function translate(string $key, array $replacements = []): string
    {
        $translation = self::$translations[$key] ?? $key;

        foreach ($replacements as $placeholder => $value) {
            $translation = str_replace(':' . $placeholder, (string) $value, $translation);
        }

        return $translation;
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    public static function getSupportedLocales(): array
    {
        return self::$supportedLocales;
    }

    public static function getLocaleMeta(string $locale): array
    {
        return self::$localeMeta[$locale] ?? [
            'abbr' => strtoupper($locale),
            'flag' => '/assets/flags/' . $locale . '.svg',
        ];
    }

    public static function urlWithLang(string $locale): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        parse_str((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY), $query);
        $query['lang'] = $locale;
        return $path . '?' . http_build_query($query);
    }

    public static function translateContent(string $content): string
    {
        $translations = self::$contentTranslations[self::$locale] ?? [];
        if (empty($translations)) {
            return $content;
        }

        $parts = preg_split('/(<[^>]+>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $content;
        }

        foreach ($parts as $index => $part) {
            if ($part !== '' && $part[0] !== '<') {
                $parts[$index] = strtr($part, $translations);
            }
        }

        return implode('', $parts);
    }

    private static function loadTranslations(string $locale): array
    {
        $langFile = dirname(__DIR__) . '/Lang/' . $locale . '.php';
        if (file_exists($langFile)) {
            $translations = require $langFile;
            if (is_array($translations)) {
                return $translations;
            }
        }

        return [];
    }

    private static function sanitizeLocale(string $locale): string
    {
        return preg_replace('/[^a-z]/', '', strtolower($locale));
    }
}
