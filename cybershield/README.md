# CyberShield – Full Stack Setup Guide

## Project Structure
```
cybershield/
├── public/
│   └── index.html          ← Main frontend (updated)
├── api/
│   ├── register.php         ← POST: register new user
│   ├── login.php            ← POST: login
│   ├── logout.php           ← GET:  logout
│   ├── me.php               ← GET:  current session info
│   ├── verify_email.php     ← GET:  email verification link
│   ├── quiz.php             ← GET: leaderboard | POST: save score
│   ├── contact.php          ← POST: submit contact message
│   ├── chatbot.php          ← POST: AI chatbot (Anthropic)
│   └── password_check.php  ← POST: server-side password strength
├── admin/
│   ├── index.php            ← Admin dashboard
│   └── actions.php          ← Ban/unban/delete/mark-read
├── includes/
│   ├── config.php           ← ⚠️  EDIT THIS FIRST
│   ├── db.php               ← PDO connection
│   └── auth.php             ← Session, helpers
├── mailer/
│   └── mailer.php           ← Email sending
├── profile.php              ← User profile + badges
├── sql/
│   └── cybershield.sql      ← Database schema
└── .htaccess                ← Apache routing + security
```

---

## Step 1 — Requirements
- PHP 8.1+  (with `curl`, `pdo_mysql`, `mbstring` extensions)
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled  (XAMPP / WAMP / Live server)

---

## Step 2 — Database Setup
1. Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`)
2. Click **Import** → select `sql/cybershield.sql` → click **Go**
3. The database `cybershield` will be created with all tables and a default admin account.

> **Default admin login:**  username `admin` | password `Admin@1234`
> ⚠️ Change this password immediately after first login!

---

## Step 3 — Configure the App
Open `includes/config.php` and update:

```php
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('SITE_URL', 'http://localhost/cybershield');  // your URL

// For the AI Chatbot — get your key at https://console.anthropic.com
define('ANTHROPIC_API_KEY', 'sk-ant-...');
```

---

## Step 4 — Place Files on Server
Copy the entire `cybershield/` folder into your web root:
- **XAMPP:** `C:/xampp/htdocs/cybershield/`
- **WAMP:**  `C:/wamp64/www/cybershield/`
- **Linux:** `/var/www/html/cybershield/`

---

## Step 5 — Access the Site
| URL | Description |
|-----|-------------|
| `http://localhost/cybershield/` | Main site |
| `http://localhost/cybershield/profile` | User profile |
| `http://localhost/cybershield/admin/` | Admin dashboard |

---

## API Reference
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/register.php` | POST | — | Register user |
| `/api/login.php` | POST | — | Login |
| `/api/logout.php` | GET | — | Logout |
| `/api/me.php` | GET | — | Session info |
| `/api/verify_email.php?token=` | GET | — | Verify email |
| `/api/quiz.php` | GET | — | Get leaderboard |
| `/api/quiz.php` | POST | ✓ User | Save quiz score |
| `/api/contact.php` | POST | — | Send contact message |
| `/api/chatbot.php` | POST | — | AI chat |
| `/api/password_check.php` | POST | — | Password strength |
| `/admin/actions.php` | POST | ✓ Admin | Ban/delete/mark-read |

---

## Badge System
| Badge | Requirement |
|-------|-------------|
| 🔰 Rookie | Default |
| 🛡️ Defender | Score 5+ in a quiz |
| ⚔️ Guardian | Score 8+ in a quiz |
| 🦸 CyberHero | Score 10/10 (perfect!) |

---

## Email Verification
By default the app uses PHP's built-in `mail()` function.
For production/deployment, replace `mailer/mailer.php` with
**PHPMailer** + SMTP (Gmail, Mailgun, etc.) for reliable delivery.

---

## Security Features Built In
- ✅ Passwords hashed with bcrypt (cost 12)
- ✅ Prepared statements everywhere (no SQL injection)
- ✅ Session regeneration on login
- ✅ HttpOnly, SameSite cookies
- ✅ Rate limiting on contact form (3 per hour per email)
- ✅ Rate limiting on chatbot (20 per hour per session)
- ✅ Admin cannot be banned or deleted via UI
- ✅ Apache blocks direct access to `includes/` and `mailer/`
- ✅ Security headers via `.htaccess`
