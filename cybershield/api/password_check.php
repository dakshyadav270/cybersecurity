<?php
// api/password_check.php  –  Server-side password strength analysis
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(['error' => 'Method not allowed'], 405);

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$password = $input['password'] ?? '';

if ($password === '') jsonOut(['score' => 0, 'label' => 'Empty']);

// Common weak passwords
$common = ['password','123456','12345678','qwerty','abc123','letmein','welcome','admin','password1'];
if (in_array(strtolower($password), $common)) {
    jsonOut(['score' => 1, 'label' => 'VERY WEAK — Common password!', 'tips' => ['Avoid common passwords']]);
}

$checks = [
    'length'  => strlen($password) >= 12,
    'upper'   => (bool)preg_match('/[A-Z]/', $password),
    'lower'   => (bool)preg_match('/[a-z]/', $password),
    'number'  => (bool)preg_match('/[0-9]/', $password),
    'symbol'  => (bool)preg_match('/[^A-Za-z0-9]/', $password),
];

$score = array_sum($checks);
$tips  = [];
if (!$checks['length'])  $tips[] = 'Use at least 12 characters';
if (!$checks['upper'])   $tips[] = 'Add uppercase letters (A-Z)';
if (!$checks['lower'])   $tips[] = 'Add lowercase letters (a-z)';
if (!$checks['number'])  $tips[] = 'Add numbers (0-9)';
if (!$checks['symbol'])  $tips[] = 'Add special characters (!@#$...)';

$labels = [
    1 => 'VERY WEAK — Change it now',
    2 => 'WEAK — Add more variety',
    3 => 'MODERATE — Getting better',
    4 => 'STRONG — Almost there',
    5 => 'VERY STRONG — Great password!',
];

jsonOut([
    'score'  => $score,
    'label'  => $labels[$score] ?? 'VERY WEAK',
    'checks' => $checks,
    'tips'   => $tips,
]);
