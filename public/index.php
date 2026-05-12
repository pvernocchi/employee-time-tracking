<?php
/**
 * Front Controller - Entry Point
 * All requests are routed through this file.
 */

// Error reporting (disable display in production)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\I18n;
use App\Core\Router;
use App\Core\SetupManager;
use App\Core\SmtpMailer;
use App\Core\View;

$setupManager = new SetupManager(dirname(__DIR__));
$config = $setupManager->loadConfig();

// Set timezone
date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Madrid');

// Initialize session
session_name($config['session']['name'] ?? 'ett_session');
session_start();

I18n::init($config['app'] ?? []);

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > ($config['session']['lifetime'] ?? 3600))) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

View::setPath(__DIR__ . '/../src/Views');

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize router
$router = new Router();
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

// ----- Setup Routes -----
$router->get('/install', [\App\Controllers\SetupController::class, 'install']);
$router->post('/install', [\App\Controllers\SetupController::class, 'processInstall']);
$router->get('/install/upgrade', [\App\Controllers\SetupController::class, 'upgrade']);
$router->post('/install/upgrade', [\App\Controllers\SetupController::class, 'processUpgrade']);

$setupStatus = $setupManager->getStatus($setupManager->configExists() ? $config : null);

if (!$setupStatus['configExists'] || !$setupStatus['databaseConnected'] || !$setupStatus['isInstalled']) {
    if (!str_starts_with($requestPath, '/install')) {
        header('Location: /install');
        exit;
    }

    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    exit;
}

if ($setupStatus['needsUpgrade']) {
    if (!str_starts_with($requestPath, '/install/upgrade')) {
        header('Location: /install/upgrade');
        exit;
    }

    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    exit;
}

Database::getInstance($config['database']);

// Initialise SMTP encryption secret (used to encrypt stored SMTP passwords)
SmtpMailer::setSecret($config['smtp']['secret'] ?? '');

// ----- Public Routes -----
$router->get('/login', [\App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [\App\Controllers\AuthController::class, 'login']);
$router->get('/logout', [\App\Controllers\AuthController::class, 'logout']);

// MFA verification (during login – no full auth required)
$router->get('/mfa/verify', [\App\Controllers\MfaController::class, 'showVerify']);
$router->post('/mfa/verify', [\App\Controllers\MfaController::class, 'verify']);
$router->post('/mfa/webauthn/auth-challenge', [\App\Controllers\MfaController::class, 'webAuthnAuthChallenge']);
$router->post('/mfa/webauthn/auth-verify', [\App\Controllers\MfaController::class, 'webAuthnAuthVerify']);

// ----- Protected Routes -----
$authMiddleware = [Auth::class . '::requireLogin'];
$adminMiddleware = [Auth::class . '::requireAdmin'];
$managerMiddleware = [Auth::class . '::requireManager'];

// Dashboard
$router->get('/', [\App\Controllers\DashboardController::class, 'index'], $authMiddleware);
$router->get('/dashboard', [\App\Controllers\DashboardController::class, 'index'], $authMiddleware);

// Clock In/Out
$router->get('/clock', [\App\Controllers\ClockController::class, 'index'], $authMiddleware);
$router->post('/clock/in', [\App\Controllers\ClockController::class, 'clockIn'], $authMiddleware);
$router->post('/clock/out', [\App\Controllers\ClockController::class, 'clockOut'], $authMiddleware);

// Timesheet
$router->get('/timesheet', [\App\Controllers\TimesheetController::class, 'index'], $authMiddleware);
$router->get('/timesheet/weekly', [\App\Controllers\TimesheetController::class, 'weekly'], $authMiddleware);
$router->get('/timesheet/monthly', [\App\Controllers\TimesheetController::class, 'monthly'], $authMiddleware);

// Leave Management
$router->get('/leave', [\App\Controllers\LeaveController::class, 'index'], $authMiddleware);
$router->get('/leave/request', [\App\Controllers\LeaveController::class, 'showRequest'], $authMiddleware);
$router->post('/leave/request', [\App\Controllers\LeaveController::class, 'submitRequest'], $authMiddleware);
$router->get('/leave/edit/{id}', [\App\Controllers\LeaveController::class, 'showEdit'], $authMiddleware);
$router->post('/leave/edit/{id}', [\App\Controllers\LeaveController::class, 'update'], $authMiddleware);
$router->post('/leave/cancel/{id}', [\App\Controllers\LeaveController::class, 'cancel'], $authMiddleware);

// Admin: Leave approval
$router->get('/admin/leave', [\App\Controllers\LeaveController::class, 'adminIndex'], $managerMiddleware);
$router->post('/admin/leave/approve/{id}', [\App\Controllers\LeaveController::class, 'approve'], $managerMiddleware);
$router->post('/admin/leave/reject/{id}', [\App\Controllers\LeaveController::class, 'reject'], $managerMiddleware);

// Admin: Settings (SMTP)
$router->get('/admin/settings/smtp', [\App\Controllers\AdminSettingsController::class, 'smtpSettings'], $adminMiddleware);
$router->post('/admin/settings/smtp', [\App\Controllers\AdminSettingsController::class, 'saveSmtpSettings'], $adminMiddleware);
$router->post('/admin/settings/smtp/test', [\App\Controllers\AdminSettingsController::class, 'testSmtp'], $adminMiddleware);

// Admin: Employee Management
$router->get('/admin/employees', [\App\Controllers\EmployeeController::class, 'index'], $adminMiddleware);
$router->get('/admin/employees/create', [\App\Controllers\EmployeeController::class, 'create'], $adminMiddleware);
$router->post('/admin/employees/create', [\App\Controllers\EmployeeController::class, 'store'], $adminMiddleware);
$router->get('/admin/employees/edit/{id}', [\App\Controllers\EmployeeController::class, 'edit'], $adminMiddleware);
$router->post('/admin/employees/edit/{id}', [\App\Controllers\EmployeeController::class, 'update'], $adminMiddleware);

// Admin: Reports
$router->get('/admin/reports', [\App\Controllers\ReportController::class, 'index'], $managerMiddleware);
$router->get('/admin/reports/export', [\App\Controllers\ReportController::class, 'export'], $managerMiddleware);
$router->get('/admin/reports/overtime', [\App\Controllers\ReportController::class, 'overtime'], $managerMiddleware);
$router->get('/admin/reports/violations', [\App\Controllers\ReportController::class, 'violations'], $managerMiddleware);

// Compliance
$router->get('/compliance', [\App\Controllers\ComplianceController::class, 'dashboard'], $adminMiddleware);
$router->get('/compliance/audit', [\App\Controllers\ComplianceController::class, 'auditLog'], $adminMiddleware);
$router->get('/compliance/export-inspection', [\App\Controllers\ComplianceController::class, 'exportForInspection'], $adminMiddleware);
$router->get('/compliance/privacy', [\App\Controllers\ComplianceController::class, 'privacyNotice'], $authMiddleware);
$router->post('/compliance/consent', [\App\Controllers\ComplianceController::class, 'dataProtectionConsent'], $authMiddleware);

// Inspector
$inspectorMiddleware = [Auth::class . '::requireInspector'];
$router->get('/inspector', [\App\Controllers\InspectorController::class, 'index'], $inspectorMiddleware);
$router->get('/inspector/employee/{id}', [\App\Controllers\InspectorController::class, 'viewEmployee'], $inspectorMiddleware);
$router->get('/inspector/export', [\App\Controllers\InspectorController::class, 'export'], $inspectorMiddleware);

// Employee self-export (Art. 34.9 ET)
$router->get('/timesheet/export', [\App\Controllers\TimesheetController::class, 'exportOwn'], $authMiddleware);

// MFA self-service (user must be logged in)
$router->get('/mfa/setup', [\App\Controllers\MfaController::class, 'showSetup'], $authMiddleware);
$router->get('/mfa/enroll/totp', [\App\Controllers\MfaController::class, 'showTotpEnroll']);
$router->post('/mfa/enroll/totp', [\App\Controllers\MfaController::class, 'storeTotpEnroll']);
$router->post('/mfa/webauthn/register-challenge', [\App\Controllers\MfaController::class, 'webAuthnRegisterChallenge']);
$router->post('/mfa/webauthn/register-verify', [\App\Controllers\MfaController::class, 'webAuthnRegisterVerify']);
$router->post('/mfa/remove/{id}', [\App\Controllers\MfaController::class, 'removeMfaMethod'], $authMiddleware);

// Admin: Security settings
$router->get('/admin/security', [\App\Controllers\SecurityController::class, 'settings'], $adminMiddleware);
$router->post('/admin/security', [\App\Controllers\SecurityController::class, 'saveSettings'], $adminMiddleware);
$router->get('/admin/security/users/{id}/mfa', [\App\Controllers\SecurityController::class, 'userMfaIndex'], $adminMiddleware);
$router->post('/admin/security/users/{id}/mfa/toggle', [\App\Controllers\SecurityController::class, 'toggleUserMfaRequired'], $adminMiddleware);
$router->post('/admin/security/mfa/{id}/revoke', [\App\Controllers\SecurityController::class, 'revokeMfaMethod'], $adminMiddleware);

// Dispatch request
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
