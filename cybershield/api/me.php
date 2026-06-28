<?php
// api/me.php  –  Returns current logged-in user info
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    jsonOut(['loggedIn' => false]);
}

$user = currentUser();
if (!$user) {
    session_destroy();
    jsonOut(['loggedIn' => false]);
}

jsonOut([
    'loggedIn' => true,
    'user' => [
        'id'           => $user['id'],
        'username'     => $user['username'],
        'email'        => $user['email'],
        'role'         => $user['role'],
        'badge'        => $user['badge_level'],
        'best_score'   => $user['best_score'],
        'total_score'  => $user['total_score'],
        'quizzes'      => $user['quizzes_taken'],
        'created_at'   => $user['created_at'],
        'last_login'   => $user['last_login'],
    ]
]);
