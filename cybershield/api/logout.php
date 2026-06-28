<?php
// api/logout.php
require_once __DIR__ . '/../includes/auth.php';
session_destroy();
jsonOut(['success' => true]);
