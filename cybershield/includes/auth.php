<?php
// includes/auth.php  –  Session helpers & badge logic
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Start session once
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Check if logged in ──────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// ── Require login (redirect or JSON error) ──────────────────
function requireLogin(bool $json = true): void {
    if (!isLoggedIn()) {
        if ($json) {
            http_response_code(401);
            die(json_encode(['error' => 'Please log in first.']));
        }
        header('Location: /cybershield/public/index.php');
        exit;
    }
}

// ── Require admin ───────────────────────────────────────────
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        die(json_encode(['error' => 'Admin access required.']));
    }
}

// ── Get current user from DB ────────────────────────────────
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND is_banned = 0');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// ── Update badge level based on best score ──────────────────
function updateBadge(int $userId): void {
    $stmt = db()->prepare('SELECT best_score FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $best = (int)($stmt->fetchColumn() ?? 0);
    $badge = match(true) {
        $best >= 10 => 'CyberHero',
        $best >= 8  => 'Guardian',
        $best >= 5  => 'Defender',
        default     => 'Rookie',
    };
    db()->prepare('UPDATE users SET badge_level = ? WHERE id = ?')
       ->execute([$badge, $userId]);
}

// ── Bump daily stats counter ─────────────────────────────────
function bumpStat(string $column): void {
    $today = date('Y-m-d');
    db()->prepare("INSERT INTO site_stats (stat_date, $column)
                   VALUES (?, 1)
                   ON DUPLICATE KEY UPDATE $column = $column + 1")
       ->execute([$today]);
}

// ── JSON response helper ────────────────────────────────────
function jsonOut(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// ── CSRF token ─────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(TOKEN_BYTES));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf(string $token): bool {
    return hash_equals($_SESSION['csrf'] ?? '', $token);
}
