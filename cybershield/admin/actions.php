<?php
// admin/actions.php  –  Admin action handler
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(['error' => 'Method not allowed'], 405);

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

switch ($action) {

    case 'ban':
        $id = (int)($input['user_id'] ?? 0);
        if (!$id) jsonOut(['error' => 'Invalid user ID'], 422);
        // Prevent banning yourself or other admins
        $stmt = db()->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $target = $stmt->fetch();
        if (!$target) jsonOut(['error' => 'User not found'], 404);
        if ($target['role'] === 'admin') jsonOut(['error' => 'Cannot ban an admin account'], 403);
        if ($id === (int)$_SESSION['user_id']) jsonOut(['error' => 'Cannot ban yourself'], 403);
        db()->prepare('UPDATE users SET is_banned = 1 WHERE id = ?')->execute([$id]);
        jsonOut(['success' => true]);

    case 'unban':
        $id = (int)($input['user_id'] ?? 0);
        if (!$id) jsonOut(['error' => 'Invalid user ID'], 422);
        db()->prepare('UPDATE users SET is_banned = 0 WHERE id = ?')->execute([$id]);
        jsonOut(['success' => true]);

    case 'delete':
        $id = (int)($input['user_id'] ?? 0);
        if (!$id) jsonOut(['error' => 'Invalid user ID'], 422);
        $stmt = db()->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $target = $stmt->fetch();
        if (!$target) jsonOut(['error' => 'User not found'], 404);
        if ($target['role'] === 'admin') jsonOut(['error' => 'Cannot delete an admin account'], 403);
        if ($id === (int)$_SESSION['user_id']) jsonOut(['error' => 'Cannot delete yourself'], 403);
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        jsonOut(['success' => true]);

    case 'mark_read':
        $id = (int)($input['msg_id'] ?? 0);
        if (!$id) jsonOut(['error' => 'Invalid message ID'], 422);
        db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]);
        jsonOut(['success' => true]);

    default:
        jsonOut(['error' => 'Unknown action'], 400);
}
