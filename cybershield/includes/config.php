<?php
// ============================================================
//  includes/config.php  –  Edit these settings before running
// ============================================================

// ── DATABASE ────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'cybershield');
define('DB_USER', 'root');         // ← change to your MySQL username
define('DB_PASS', '');             // ← change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// ── SITE ────────────────────────────────────────────────────
define('SITE_URL',  'http://localhost/cybershield');   // ← your site URL (no trailing slash)
define('SITE_NAME', 'CyberShield');

// ── SESSION ─────────────────────────────────────────────────
define('SESSION_LIFETIME', 60 * 60 * 2);  // 2 hours

// ── EMAIL (SMTP via PHPMailer or mail()) ────────────────────
define('MAIL_FROM',     'noreply@cybershield.local');
define('MAIL_FROM_NAME','CyberShield Portal');

// ── SECURITY ────────────────────────────────────────────────
define('BCRYPT_COST', 12);
define('TOKEN_BYTES', 32);

// ── AI CHATBOT (Anthropic) ──────────────────────────────────
// Get your key at https://console.anthropic.com
define('GROQ_API_KEY', 'get you own key');
define('GROQ_MODEL',   'llama-3.1-8b-instant');

// ── TIMEZONE ────────────────────────────────────────────────
date_default_timezone_set('Asia/Kolkata');  // ← set your timezone
