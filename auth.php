<?php
/**
 * admin/auth.php
 * =================================================
 * Central Authentication & Authorization Guard
 *
 * ROLE MODEL (NO BREAKING CHANGE):
 * - users.role stays: 'Admin' or 'Staff'
 * - Super Admin is:
 *       role='Admin' AND admin_level='Super'
 * - HOD (Department Admin) is:
 *       role='Admin' AND admin_level!='Super' AND department_id IS NOT NULL
 *
 * FEATURES:
 * - AJAX safe (JSON response, no redirect)
 * - Absolute-path safe DB include
 * - Always verifies user from DB (prevents stale session)
 * - Loads department_name + admin_level + theme + profile_photo
 *
 * HELPERS:
 * - isAdmin(), isStaff()
 * - isSystemAdmin()  -> Super Admin
 * - isHOD()          -> Department Admin (your HOD)
 * - currentDepartmentId(), currentDepartmentName()
 * - roleBadgeLabel(), roleBadgeColor()
 * - canManageActivityDepartment($deptId)
 * =================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =================================================
   LOAD DATABASE (ABSOLUTE PATH SAFE)
================================================= */
require_once __DIR__ . '/../database.php';

/* =================================================
   HELPER: IS AJAX REQUEST?
================================================= */
function isAjaxRequest(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/* =================================================
   HELPER: AUTH FAIL RESPONSE
================================================= */
function authFail(int $code, string $error): void
{
    if (isAjaxRequest()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => $error
        ]);
        exit;
    }

    // normal page request
    session_unset();
    session_destroy();

    // from /admin/* pages -> login is /auth/login.php
    header('Location: ../auth/login.php');
    exit;
}

/* =================================================
   AUTH CHECK: LOGIN SESSION
================================================= */
if (!isset($_SESSION['user_id'])) {
    authFail(401, 'unauthorized');
}

/* =================================================
   LOAD USER PROFILE (DB IS SOURCE OF TRUTH)
================================================= */
$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) {
    authFail(401, 'unauthorized');
}

$stmt = $conn->prepare("
    SELECT 
        u.user_id,
        u.name,
        u.email,
        u.role,
        u.profile_photo,
        u.theme,
        u.department_id,
        u.admin_level,
        u.status,
        d.department_name
    FROM users u
    LEFT JOIN departments d 
        ON d.department_id = u.department_id
    WHERE u.user_id = ?
    LIMIT 1
");

if (!$stmt) {
    authFail(500, 'server_error');
}

$stmt->bind_param("i", $uid);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$user) {
    authFail(401, 'unauthorized');
}

/* =================================================
   STATUS CHECK (BLOCK INACTIVE)
================================================= */
if (isset($user['status']) && strtolower((string)$user['status']) !== 'active') {
    authFail(403, 'inactive_account');
}

/* =================================================
   ROLE VALIDATION
================================================= */
$allowedRoles = ['Admin', 'Staff'];
if (!in_array(($user['role'] ?? ''), $allowedRoles, true)) {
    authFail(403, 'forbidden');
}

/* =================================================
   SET SESSION CONTEXT (REFRESH EACH REQUEST)
================================================= */
$_SESSION['user_id']       = (int)$user['user_id'];
$_SESSION['name']          = (string)($user['name'] ?? '');
$_SESSION['email']         = (string)($user['email'] ?? '');
$_SESSION['role']          = (string)($user['role'] ?? 'Staff');

$_SESSION['profile_photo'] = (!empty($user['profile_photo']) ? (string)$user['profile_photo'] : null);
$_SESSION['theme']         = (!empty($user['theme']) ? (string)$user['theme'] : 'light');

// Department context
$_SESSION['department_id']   = (!empty($user['department_id']) ? (int)$user['department_id'] : null);
$_SESSION['department_name'] = (string)($user['department_name'] ?? '—');

// Admin hierarchy (NULL for Staff)
$_SESSION['admin_level'] = (!empty($user['admin_level']) ? (string)$user['admin_level'] : null);

/* =================================================
   HELPERS (GLOBAL USE)
================================================= */

/** DB role Admin? */
function isAdmin(): bool
{
    return (($_SESSION['role'] ?? '') === 'Admin');
}

/** DB role Staff? */
function isStaff(): bool
{
    return (($_SESSION['role'] ?? '') === 'Staff');
}

/** Super Admin (your ADMIN) */
function isSystemAdmin(): bool
{
    return isAdmin() && (($_SESSION['admin_level'] ?? null) === 'Super');
}

/**
 * HOD (Department Admin)
 * ✅ FIXED: HOD = Admin but NOT Super + has department_id
 * Works for DB values like: admin_level = 'Normal' OR 'Department' OR NULL
 * As long as department_id is set and admin_level isn't 'Super'.
 */
function isHOD(): bool
{
    if (!isAdmin()) return false;

    $level = (string)($_SESSION['admin_level'] ?? 'Normal');
    if ($level === 'Super') return false;

    $deptId = $_SESSION['department_id'] ?? null;
    return !empty($deptId);
}

/** Backwards compatibility with your existing code names */
function isSuperAdmin(): bool
{
    return isSystemAdmin();
}
function isDepartmentAdmin(): bool
{
    return isHOD();
}

/** Get current user's department ID */
function currentDepartmentId(): ?int
{
    $v = $_SESSION['department_id'] ?? null;
    if ($v === null || $v === '') return null;
    return (int)$v;
}

/** Get current user's department name */
function currentDepartmentName(): string
{
    return (string)($_SESSION['department_name'] ?? '—');
}

/**
 * Badge label:
 * - Staff => STAFF
 * - HOD   => HOD
 * - ADMIN => ADMIN
 */
function roleBadgeLabel(): string
{
    if (isSystemAdmin()) return 'ADMIN';
    if (isHOD()) return 'HOD';
    return 'STAFF';
}

/**
 * Badge color:
 * - STAFF => info
 * - HOD   => warning
 * - ADMIN => danger
 */
function roleBadgeColor(): string
{
    if (isSystemAdmin()) return 'danger';
    if (isHOD()) return 'warning';
    return 'info';
}

/**
 * Permission: can manage (edit/delete/complete) activity of a given department?
 * - ADMIN (Super): YES for all
 * - HOD (Dept Admin): YES only for own department
 * - Staff: NO
 */
function canManageActivityDepartment(?int $activityDepartmentId): bool
{
    if (isSystemAdmin()) return true;

    if (isHOD()) {
        $myDept = currentDepartmentId();
        if ($myDept === null || $activityDepartmentId === null) return false;
        return ((int)$myDept === (int)$activityDepartmentId);
    }

    return false;
}
