<?php
// admin/index.php  –  Admin Dashboard
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = db();

// ── Stats ───────────────────────────────────────────────────
$stats = $db->query("
    SELECT
      (SELECT COUNT(*) FROM users WHERE role='user')           AS total_users,
      (SELECT COUNT(*) FROM users WHERE is_banned=1)           AS banned_users,
      (SELECT COUNT(*) FROM users WHERE is_verified=0 AND role='user') AS unverified_users,
      (SELECT COUNT(*) FROM quiz_scores)                       AS total_quizzes,
      (SELECT COUNT(*) FROM contact_messages)                  AS total_messages,
      (SELECT COUNT(*) FROM contact_messages WHERE is_read=0)  AS unread_messages,
      (SELECT COUNT(*) FROM chat_history WHERE role='user')    AS total_chats
")->fetch();

// ── Leaderboard ─────────────────────────────────────────────
$leaderboard = $db->query("
    SELECT username, badge_level, best_score, quizzes_taken, last_login
    FROM users WHERE role='user' AND best_score > 0
    ORDER BY best_score DESC LIMIT 10
")->fetchAll();

// ── Latest users ─────────────────────────────────────────────
$users = $db->query("
    SELECT id, username, email, badge_level, best_score, is_banned, is_verified, created_at, last_login
    FROM users WHERE role='user'
    ORDER BY created_at DESC LIMIT 50
")->fetchAll();

// ── Contact messages ─────────────────────────────────────────
$messages = $db->query("
    SELECT cm.*, u.username FROM contact_messages cm
    LEFT JOIN users u ON cm.user_id = u.id
    ORDER BY cm.created_at DESC LIMIT 30
")->fetchAll();

// ── Daily stats (last 7 days) ─────────────────────────────────
$daily = $db->query("
    SELECT stat_date, new_users, quizzes_taken, messages_sent, chat_requests
    FROM site_stats
    ORDER BY stat_date DESC LIMIT 7
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard – CyberShield</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Inter:wght@400;500;600;700&display=swap');
  :root{--bg:#0a0e17;--surface:#0f1624;--card:#141d2e;--border:#1e2d45;--accent:#00d4ff;--accent2:#ff4d6d;--safe:#39ff14;--warn:#ffd166;--text:#e2e8f0;--muted:#7a8fa8}
  *{margin:0;padding:0;box-sizing:border-box}
  body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;display:flex;min-height:100vh}
  /* Sidebar */
  .sidebar{width:220px;background:var(--surface);border-right:1px solid var(--border);padding:1.5rem 1rem;display:flex;flex-direction:column;gap:.4rem;position:sticky;top:0;height:100vh}
  .sidebar-logo{font-family:'Share Tech Mono',monospace;color:var(--accent);font-size:1rem;margin-bottom:1.5rem;letter-spacing:2px}
  .sidebar-logo span{color:var(--accent2)}
  .nav-item{padding:.6rem 1rem;border-radius:6px;cursor:pointer;font-size:.85rem;color:var(--muted);transition:all .2s;text-decoration:none;display:block}
  .nav-item:hover,.nav-item.active{background:rgba(0,212,255,.08);color:var(--accent)}
  .nav-item .icon{margin-right:.5rem}
  .sidebar-footer{margin-top:auto;font-size:.75rem;color:var(--muted)}
  .logout-btn{display:block;margin-top:.5rem;padding:.5rem 1rem;background:rgba(255,77,109,.1);border:1px solid rgba(255,77,109,.3);color:var(--accent2);border-radius:6px;text-align:center;cursor:pointer;font-size:.8rem;text-decoration:none}
  /* Main */
  .main{flex:1;padding:2rem;overflow-y:auto}
  .page{display:none}.page.active{display:block}
  h1{font-size:1.4rem;margin-bottom:1.5rem;font-weight:700}
  h2{font-size:1rem;font-weight:600;margin-bottom:1rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:1px}
  /* Stat cards */
  .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
  .stat-card{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:1.2rem;text-align:center}
  .stat-card .num{font-family:'Share Tech Mono',monospace;font-size:2rem;color:var(--accent);display:block}
  .stat-card .lbl{font-size:.75rem;color:var(--muted);margin-top:.3rem}
  .stat-card.red .num{color:var(--accent2)}
  .stat-card.green .num{color:var(--safe)}
  .stat-card.yellow .num{color:var(--warn)}
  /* Table */
  .table-wrap{background:var(--card);border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:2rem}
  table{width:100%;border-collapse:collapse;font-size:.83rem}
  th{background:var(--surface);padding:.75rem 1rem;text-align:left;color:var(--muted);font-weight:500;font-size:.78rem;letter-spacing:.5px}
  td{padding:.75rem 1rem;border-top:1px solid var(--border);vertical-align:middle}
  tr:hover td{background:rgba(255,255,255,.02)}
  .badge{display:inline-block;padding:.15rem .5rem;border-radius:3px;font-size:.72rem;font-family:'Share Tech Mono',monospace}
  .badge-Rookie{background:rgba(122,143,168,.15);color:var(--muted)}
  .badge-Defender{background:rgba(0,212,255,.1);color:var(--accent)}
  .badge-Guardian{background:rgba(255,209,102,.1);color:var(--warn)}
  .badge-CyberHero{background:rgba(57,255,20,.1);color:var(--safe)}
  .badge-verified{background:rgba(57,255,20,.1);color:var(--safe)}
  .badge-unverified{background:rgba(255,209,102,.1);color:var(--warn)}
  .badge-banned{background:rgba(255,77,109,.1);color:var(--accent2)}
  .badge-read{background:rgba(57,255,20,.1);color:var(--safe)}
  .badge-unread{background:rgba(255,77,109,.1);color:var(--accent2)}
  /* Action buttons */
  .btn-sm{padding:.3rem .7rem;border-radius:4px;font-size:.75rem;cursor:pointer;border:none;font-family:'Share Tech Mono',monospace}
  .btn-ban{background:rgba(255,77,109,.15);color:var(--accent2);border:1px solid rgba(255,77,109,.3)}
  .btn-unban{background:rgba(57,255,20,.1);color:var(--safe);border:1px solid rgba(57,255,20,.3)}
  .btn-delete{background:rgba(255,77,109,.3);color:#fff;border:none}
  .btn-read{background:rgba(0,212,255,.1);color:var(--accent);border:1px solid rgba(0,212,255,.2)}
  /* Message modal */
  .modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:999;align-items:center;justify-content:center}
  .modal.open{display:flex}
  .modal-box{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:2rem;max-width:560px;width:90%;max-height:80vh;overflow-y:auto}
  .modal-box h3{margin-bottom:1rem;color:var(--accent)}
  .modal-box p{color:var(--muted);font-size:.88rem;line-height:1.6}
  .modal-close{float:right;background:transparent;border:none;color:var(--muted);font-size:1.2rem;cursor:pointer}
  /* Daily stats table highlight */
  .trend-up{color:var(--safe)}
</style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-logo">CYBER<span>SHIELD</span><br><small style="font-size:.65rem;color:var(--muted)">// ADMIN</small></div>
  <a class="nav-item active" onclick="show('overview')" href="#">📊 Overview</a>
  <a class="nav-item" onclick="show('users')" href="#">👥 Users</a>
  <a class="nav-item" onclick="show('leaderboard')" href="#">🏆 Leaderboard</a>
  <a class="nav-item" onclick="show('messages')" href="#">✉️ Messages <?= $stats['unread_messages'] > 0 ? "<span style='color:var(--accent2);font-size:.8rem'>({$stats['unread_messages']})</span>" : '' ?></a>
  <a class="nav-item" onclick="show('daily')" href="#">📈 Daily Stats</a>
  <div class="sidebar-footer">
    Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
    <a class="logout-btn" href="/api/logout.php">⏻ Logout</a>
    <a class="logout-btn" href="/cybershield/public/index.php" style="margin-top:.3rem;background:rgba(0,212,255,.1);border-color:rgba(0,212,255,.3);color:var(--accent)">← Back to Site</a>
  </div>
</div>

<div class="main">

  <!-- OVERVIEW -->
  <div class="page active" id="page-overview">
    <h1>📊 Dashboard Overview</h1>
    <div class="stats-grid">
      <div class="stat-card"><span class="num"><?= $stats['total_users'] ?></span><div class="lbl">Total Users</div></div>
      <div class="stat-card green"><span class="num"><?= $stats['total_quizzes'] ?></span><div class="lbl">Quizzes Taken</div></div>
      <div class="stat-card yellow"><span class="num"><?= $stats['total_messages'] ?></span><div class="lbl">Contact Messages</div></div>
      <div class="stat-card"><span class="num"><?= $stats['total_chats'] ?></span><div class="lbl">AI Chat Requests</div></div>
      <div class="stat-card red"><span class="num"><?= $stats['banned_users'] ?></span><div class="lbl">Banned Users</div></div>
      <div class="stat-card red"><span class="num"><?= $stats['unread_messages'] ?></span><div class="lbl">Unread Messages</div></div>
    </div>
  </div>

  <!-- USERS -->
  <div class="page" id="page-users">
    <h1>👥 User Management</h1>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Username</th><th>Email</th><th>Badge</th><th>Best Score</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr id="row-<?= $u['id'] ?>">
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge badge-<?= $u['badge_level'] ?>"><?= $u['badge_level'] ?></span></td>
            <td><?= $u['best_score'] ?>/10</td>
            <td>
              <?php if ($u['is_banned']): ?>
                <span class="badge badge-banned">BANNED</span>
              <?php elseif ($u['is_verified']): ?>
                <span class="badge badge-verified">Verified</span>
              <?php else: ?>
                <span class="badge badge-unverified">Unverified</span>
              <?php endif; ?>
            </td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td style="display:flex;gap:.4rem">
              <?php if ($u['is_banned']): ?>
                <button class="btn-sm btn-unban" onclick="banUser(<?= $u['id'] ?>, 0)">Unban</button>
              <?php else: ?>
                <button class="btn-sm btn-ban" onclick="banUser(<?= $u['id'] ?>, 1)">Ban</button>
              <?php endif; ?>
              <button class="btn-sm btn-delete" onclick="deleteUser(<?= $u['id'] ?>)">Delete</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- LEADERBOARD -->
  <div class="page" id="page-leaderboard">
    <h1>🏆 Quiz Leaderboard — Top 10</h1>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Rank</th><th>Username</th><th>Badge</th><th>Best Score</th><th>Quizzes Taken</th><th>Last Login</th></tr></thead>
        <tbody>
        <?php foreach ($leaderboard as $i => $l): ?>
          <tr>
            <td><?= $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '#'.($i+1))) ?></td>
            <td><?= htmlspecialchars($l['username']) ?></td>
            <td><span class="badge badge-<?= $l['badge_level'] ?>"><?= $l['badge_level'] ?></span></td>
            <td style="color:var(--accent);font-family:'Share Tech Mono',monospace"><?= $l['best_score'] ?>/10</td>
            <td><?= $l['quizzes_taken'] ?></td>
            <td><?= $l['last_login'] ? date('d M Y', strtotime($l['last_login'])) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- MESSAGES -->
  <div class="page" id="page-messages">
    <h1>✉️ Contact Messages</h1>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Subject</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($messages as $m): ?>
          <tr id="msg-<?= $m['id'] ?>">
            <td><?= $m['id'] ?></td>
            <td><?= htmlspecialchars($m['name']) ?></td>
            <td><?= htmlspecialchars($m['email']) ?></td>
            <td style="cursor:pointer;color:var(--accent)" onclick="viewMsg(<?= $m['id'] ?>, <?= htmlspecialchars(json_encode($m['subject']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($m['message']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($m['name']), ENT_QUOTES) ?>)"><?= htmlspecialchars(substr($m['subject'],0,40)) ?><?= strlen($m['subject'])>40?'…':'' ?></td>
            <td><span class="badge badge-<?= $m['is_read']?'read':'unread' ?>" id="badge-<?= $m['id'] ?>"><?= $m['is_read']?'Read':'Unread' ?></span></td>
            <td><?= date('d M Y', strtotime($m['created_at'])) ?></td>
            <td><?php if (!$m['is_read']): ?><button class="btn-sm btn-read" onclick="markRead(<?= $m['id'] ?>)">Mark Read</button><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- DAILY STATS -->
  <div class="page" id="page-daily">
    <h1>📈 Daily Statistics (Last 7 Days)</h1>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Date</th><th>New Users</th><th>Quizzes Taken</th><th>Messages Sent</th><th>AI Chat Requests</th></tr></thead>
        <tbody>
        <?php foreach ($daily as $d): ?>
          <tr>
            <td><?= date('d M Y', strtotime($d['stat_date'])) ?></td>
            <td class="trend-up"><?= $d['new_users'] ?></td>
            <td><?= $d['quizzes_taken'] ?></td>
            <td><?= $d['messages_sent'] ?></td>
            <td><?= $d['chat_requests'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /main -->

<!-- Message Modal -->
<div class="modal" id="msgModal">
  <div class="modal-box">
    <button class="modal-close" onclick="document.getElementById('msgModal').classList.remove('open')">✕</button>
    <h3 id="modalSubject"></h3>
    <p style="color:var(--muted);font-size:.8rem;margin-bottom:1rem" id="modalFrom"></p>
    <p id="modalBody"></p>
  </div>
</div>

<script>
function show(page) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('page-' + page).classList.add('active');
  event.currentTarget.classList.add('active');
}

async function banUser(id, ban) {
  if (!confirm(ban ? 'Ban this user?' : 'Unban this user?')) return;
  const r = await fetch('/admin/actions.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({action: ban ? 'ban' : 'unban', user_id: id})
  });
  const d = await r.json();
  if (d.success) location.reload();
  else alert(d.error);
}

async function deleteUser(id) {
  if (!confirm('Permanently delete this user? This cannot be undone.')) return;
  const r = await fetch('/admin/actions.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({action: 'delete', user_id: id})
  });
  const d = await r.json();
  if (d.success) document.getElementById('row-' + id).remove();
  else alert(d.error);
}

async function markRead(id) {
  const r = await fetch('/admin/actions.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({action: 'mark_read', msg_id: id})
  });
  const d = await r.json();
  if (d.success) {
    const b = document.getElementById('badge-' + id);
    b.textContent = 'Read'; b.className = 'badge badge-read';
  }
}

function viewMsg(id, subject, message, name) {
  document.getElementById('modalSubject').textContent = subject;
  document.getElementById('modalFrom').textContent = 'From: ' + name;
  document.getElementById('modalBody').textContent = message;
  document.getElementById('msgModal').classList.add('open');
  markRead(id);
}
</script>
</body>
</html>
