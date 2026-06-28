<?php
// profile.php  –  User profile page with badges & quiz history
require_once __DIR__ . '/includes/auth.php';
requireLogin(false);

$user = currentUser();
if (!$user) { header('Location: /index.html'); exit; }

// Quiz history (last 10)
$history = db()->prepare(
    'SELECT score, total_q, percentage, taken_at FROM quiz_scores
     WHERE user_id = ? ORDER BY taken_at DESC LIMIT 10'
);
$history->execute([$user['id']]);
$scores = $history->fetchAll();

// Global rank
$rank = db()->prepare(
    'SELECT COUNT(*) + 1 AS rank FROM users
     WHERE best_score > ? AND role = "user" AND is_banned = 0'
);
$rank->execute([$user['best_score']]);
$rankNum = $rank->fetchColumn();

$badgeInfo = [
    'Rookie'    => ['icon' => '🔰', 'color' => '#7a8fa8', 'desc' => 'Just getting started on your cybersecurity journey.'],
    'Defender'  => ['icon' => '🛡️', 'color' => '#00d4ff', 'desc' => 'You\'ve shown solid cybersecurity awareness.'],
    'Guardian'  => ['icon' => '⚔️', 'color' => '#ffd166', 'desc' => 'A skilled guardian of digital security!'],
    'CyberHero' => ['icon' => '🦸', 'color' => '#39ff14', 'desc' => 'Elite cybersecurity expert — perfect score!'],
];
$badge = $badgeInfo[$user['badge_level']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($user['username']) ?>'s Profile – CyberShield</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Inter:wght@400;500;600;700&display=swap');
  :root{--bg:#0a0e17;--surface:#0f1624;--card:#141d2e;--border:#1e2d45;--accent:#00d4ff;--accent2:#ff4d6d;--safe:#39ff14;--warn:#ffd166;--text:#e2e8f0;--muted:#7a8fa8}
  *{margin:0;padding:0;box-sizing:border-box}
  body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;padding:2rem 1rem}
  .container{max-width:900px;margin:0 auto}
  .back-btn{display:inline-block;margin-bottom:1.5rem;color:var(--muted);text-decoration:none;font-size:.85rem;padding:.4rem .8rem;border:1px solid var(--border);border-radius:6px;transition:.2s}
  .back-btn:hover{color:var(--accent);border-color:var(--accent)}
  /* Profile card */
  .profile-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:2rem;display:flex;align-items:center;gap:2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
  .profile-card::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at top left,rgba(0,212,255,.05),transparent 60%);pointer-events:none}
  .avatar{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;font-family:'Share Tech Mono',monospace;flex-shrink:0;border:3px solid;border-color:<?= $badge['color'] ?>}
  .profile-info h2{font-size:1.5rem;margin-bottom:.3rem}
  .profile-info .email{color:var(--muted);font-size:.85rem;margin-bottom:.6rem}
  .badge-display{display:inline-flex;align-items:center;gap:.5rem;padding:.4rem 1rem;border-radius:20px;font-size:.85rem;font-weight:600;border:1px solid;font-family:'Share Tech Mono',monospace}
  .profile-meta{margin-left:auto;text-align:right;font-size:.82rem;color:var(--muted);line-height:1.8}
  .profile-meta strong{color:var(--text)}
  /* Stats row */
  .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem}
  .stat-box{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:1.2rem;text-align:center}
  .stat-box .val{font-family:'Share Tech Mono',monospace;font-size:1.8rem;color:var(--accent);display:block}
  .stat-box .lbl{font-size:.75rem;color:var(--muted);margin-top:.3rem}
  /* Badge progress */
  .badge-section{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem}
  .badge-section h3{margin-bottom:1rem;font-size:.9rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:1px}
  .badge-list{display:flex;gap:1rem;flex-wrap:wrap}
  .badge-item{flex:1;min-width:150px;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:1rem;text-align:center;opacity:.4;transition:.2s}
  .badge-item.earned{opacity:1;border-color:var(--accent)}
  .badge-item.current{opacity:1;border-color:<?= $badge['color'] ?>;box-shadow:0 0 12px <?= $badge['color'] ?>33}
  .badge-item .b-icon{font-size:1.8rem;display:block;margin-bottom:.4rem}
  .badge-item .b-name{font-family:'Share Tech Mono',monospace;font-size:.8rem;margin-bottom:.2rem}
  .badge-item .b-req{font-size:.72rem;color:var(--muted)}
  /* Progress bar */
  .progress-wrap{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem}
  .progress-wrap h3{margin-bottom:1rem;font-size:.9rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:1px}
  .progress-bar-bg{background:var(--bg);border-radius:6px;height:12px;overflow:hidden;margin-bottom:.4rem}
  .progress-bar-fill{height:100%;border-radius:6px;background:linear-gradient(90deg,var(--accent),var(--accent2));transition:width .8s ease}
  .progress-labels{display:flex;justify-content:space-between;font-size:.75rem;color:var(--muted)}
  /* Quiz history */
  .history-section{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem}
  .history-section h3{margin-bottom:1rem;font-size:.9rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:1px}
  .history-empty{color:var(--muted);font-size:.85rem;text-align:center;padding:2rem}
  table{width:100%;border-collapse:collapse;font-size:.83rem}
  th{color:var(--muted);font-weight:500;text-align:left;padding:.6rem .8rem;border-bottom:1px solid var(--border);font-size:.78rem}
  td{padding:.6rem .8rem;border-bottom:1px solid rgba(30,45,69,.5)}
  .score-high{color:var(--safe)}
  .score-mid{color:var(--warn)}
  .score-low{color:var(--accent2)}
  /* Dark mode toggle */
  .theme-toggle{position:fixed;top:1rem;right:1rem;background:var(--card);border:1px solid var(--border);border-radius:20px;padding:.4rem .8rem;font-size:.8rem;cursor:pointer;color:var(--muted);transition:.2s}
  .theme-toggle:hover{color:var(--accent);border-color:var(--accent)}
</style>
</head>
<body>
<button class="theme-toggle" onclick="toggleTheme()" id="themeBtn">☀️ Light</button>
<div class="container">
  <a href="/cybershield/public/index.php" class="back-btn">← Back to CyberShield</a>

  <!-- Profile Card -->
  <div class="profile-card">
    <div class="avatar"><?= htmlspecialchars($user['avatar_initial']) ?></div>
    <div class="profile-info">
      <h2><?= htmlspecialchars($user['username']) ?></h2>
      <div class="email"><?= htmlspecialchars($user['email']) ?></div>
      <div class="badge-display" style="color:<?= $badge['color'] ?>;border-color:<?= $badge['color'] ?>33;background:<?= $badge['color'] ?>11">
        <?= $badge['icon'] ?> <?= $user['badge_level'] ?>
      </div>
      <div style="font-size:.78rem;color:var(--muted);margin-top:.6rem"><?= $badge['desc'] ?></div>
    </div>
    <div class="profile-meta">
      <div>Global Rank <strong>#<?= $rankNum ?></strong></div>
      <div>Member since <strong><?= date('M Y', strtotime($user['created_at'])) ?></strong></div>
      <?php if ($user['last_login']): ?>
      <div>Last login <strong><?= date('d M Y', strtotime($user['last_login'])) ?></strong></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-box"><span class="val"><?= $user['best_score'] ?>/10</span><div class="lbl">Best Score</div></div>
    <div class="stat-box"><span class="val"><?= $user['quizzes_taken'] ?></span><div class="lbl">Quizzes Taken</div></div>
    <div class="stat-box"><span class="val"><?= $user['total_score'] ?></span><div class="lbl">Total Points</div></div>
    <div class="stat-box"><span class="val">#<?= $rankNum ?></span><div class="lbl">Global Rank</div></div>
  </div>

  <!-- Progress to next badge -->
  <?php
    $nextBadge = match($user['badge_level']) {
        'Rookie'   => ['name'=>'Defender',  'need'=>5,  'label'=>'Score 5+ to earn Defender 🛡️'],
        'Defender' => ['name'=>'Guardian',  'need'=>8,  'label'=>'Score 8+ to earn Guardian ⚔️'],
        'Guardian' => ['name'=>'CyberHero', 'need'=>10, 'label'=>'Score 10/10 to become CyberHero 🦸'],
        default    => null,
    };
    $best = (int)$user['best_score'];
  ?>
  <?php if ($nextBadge): ?>
  <div class="progress-wrap">
    <h3>// NEXT BADGE PROGRESS</h3>
    <p style="font-size:.83rem;color:var(--muted);margin-bottom:.8rem"><?= $nextBadge['label'] ?></p>
    <?php $pct = min(100, round($best / $nextBadge['need'] * 100)); ?>
    <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:<?= $pct ?>%"></div></div>
    <div class="progress-labels"><span>Current: <?= $best ?>/10</span><span>Need: <?= $nextBadge['need'] ?>/10</span></div>
  </div>
  <?php endif; ?>

  <!-- Badges -->
  <div class="badge-section">
    <h3>// BADGE COLLECTION</h3>
    <div class="badge-list">
      <?php
        $order  = ['Rookie','Defender','Guardian','CyberHero'];
        $thresholds = ['Rookie'=>0,'Defender'=>5,'Guardian'=>8,'CyberHero'=>10];
        $current = $user['badge_level'];
        $currentIdx = array_search($current, $order);
        foreach ($order as $i => $b):
          $info   = $badgeInfo[$b];
          $earned = $i <= $currentIdx;
          $isCurr = $b === $current;
          $cls    = $isCurr ? 'badge-item current' : ($earned ? 'badge-item earned' : 'badge-item');
      ?>
      <div class="<?= $cls ?>">
        <span class="b-icon"><?= $info['icon'] ?></span>
        <div class="b-name"><?= $b ?></div>
        <div class="b-req">Score <?= $thresholds[$b] ?>+</div>
        <?= $earned ? '<div style="font-size:.7rem;color:var(--safe);margin-top:.3rem">✓ Earned</div>' : '' ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Quiz History -->
  <div class="history-section">
    <h3>// QUIZ HISTORY</h3>
    <?php if (!$scores): ?>
      <div class="history-empty">No quizzes taken yet. <a href="/#quiz" style="color:var(--accent)">Take your first quiz →</a></div>
    <?php else: ?>
    <table>
      <thead><tr><th>#</th><th>Score</th><th>Percentage</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($scores as $i => $s):
        $pct = round($s['percentage']);
        $cls = $pct >= 80 ? 'score-high' : ($pct >= 50 ? 'score-mid' : 'score-low');
      ?>
        <tr>
          <td><?= count($scores) - $i ?></td>
          <td class="<?= $cls ?>" style="font-family:'Share Tech Mono',monospace"><?= $s['score'] ?>/<?= $s['total_q'] ?></td>
          <td><span class="<?= $cls ?>"><?= $pct ?>%</span></td>
          <td style="color:var(--muted)"><?= date('d M Y, g:i A', strtotime($s['taken_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

</div>

<script>
// Dark/light mode toggle
let dark = true;
function toggleTheme() {
  dark = !dark;
  document.documentElement.style.setProperty('--bg',      dark ? '#0a0e17' : '#f0f4f8');
  document.documentElement.style.setProperty('--surface', dark ? '#0f1624' : '#e2e8f0');
  document.documentElement.style.setProperty('--card',    dark ? '#141d2e' : '#ffffff');
  document.documentElement.style.setProperty('--border',  dark ? '#1e2d45' : '#cbd5e1');
  document.documentElement.style.setProperty('--text',    dark ? '#e2e8f0' : '#1a202c');
  document.documentElement.style.setProperty('--muted',   dark ? '#7a8fa8' : '#64748b');
  document.getElementById('themeBtn').textContent = dark ? '☀️ Light' : '🌙 Dark';
  localStorage.setItem('theme', dark ? 'dark' : 'light');
}
// Restore saved theme
if (localStorage.getItem('theme') === 'light') toggleTheme();
</script>
</body>
</html>
