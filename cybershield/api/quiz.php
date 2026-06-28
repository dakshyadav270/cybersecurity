<?php
// api/quiz.php  –  Submit score (POST) or get leaderboard (GET)
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

// ── GET: public leaderboard ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = db()->query(
        'SELECT u.username, u.badge_level, u.best_score, u.quizzes_taken
         FROM users u
         WHERE u.is_banned = 0 AND u.best_score > 0
         ORDER BY u.best_score DESC, u.quizzes_taken ASC
         LIMIT 20'
    )->fetchAll();

    jsonOut(['leaderboard' => $rows]);
}

// ── POST: submit score ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $score   = (int)($input['score']   ?? -1);
    $total_q = (int)($input['total_q'] ?? 10);

    if ($score < 0 || $score > $total_q || $total_q < 1) {
        jsonOut(['error' => 'Invalid score data.'], 422);
    }

    $userId = $_SESSION['user_id'];

    // Save score record
    db()->prepare('INSERT INTO quiz_scores (user_id, score, total_q) VALUES (?, ?, ?)')
       ->execute([$userId, $score, $total_q]);

    // Update user stats
    db()->prepare(
        'UPDATE users
         SET total_score   = total_score + ?,
             quizzes_taken = quizzes_taken + 1,
             best_score    = GREATEST(best_score, ?)
         WHERE id = ?'
    )->execute([$score, $score, $userId]);

    // Update badge
    updateBadge($userId);
    bumpStat('quizzes_taken');

    // Return updated user info
    $stmt = db()->prepare('SELECT badge_level, best_score, quizzes_taken FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $updated = $stmt->fetch();

    jsonOut([
        'success'    => true,
        'badge'      => $updated['badge_level'],
        'best_score' => $updated['best_score'],
        'quizzes'    => $updated['quizzes_taken'],
    ]);
}

jsonOut(['error' => 'Method not allowed'], 405);
