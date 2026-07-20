<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

$loginError = '';

// Handle login submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        $loginError = 'Session expired, please try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $admin = admin_attempt_login($username, $password);
        if ($admin) {
            admin_login($admin);
            header('Location: admin.php');
            exit;
        }
        $loginError = 'Incorrect username or password.';
    }
}

if (!admin_is_logged_in()) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= h(SITE_NAME) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="meeting.css">
    <link rel="stylesheet" href="admin.css">
    </head>
    <body>
    <div class="bg-layer">
      <div class="bg-grid"></div>
      <div class="brush b1"></div>
      <div class="brush b2"></div>
    </div>
    <div class="meet-wrap">
      <div class="meet-card admin-login-card">
        <div class="meet-head">
          <h1><i class="fa-solid fa-lock"></i> Admin Login</h1>
          <p>Sign in to manage meeting bookings.</p>
        </div>

        <?php if ($loginError): ?>
          <div class="meet-alert show error"><?= h($loginError) ?></div>
        <?php endif; ?>

        <form method="post" action="admin.php">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <label class="f-label" for="username">Username</label>
          <input class="f-input" type="text" id="username" name="username" autocomplete="username" required>

          <label class="f-label" for="password">Password</label>
          <input class="f-input" type="password" id="password" name="password" autocomplete="current-password" required>

          <button class="f-btn" type="submit" name="login_submit" value="1">
            <span>Log In</span>
            <i class="fa-solid fa-arrow-right-to-bracket"></i>
          </button>
        </form>
      </div>
      <footer class="meet-footer">Maintained by <span><?= h(SITE_NAME) ?></span> — Est. 2007</footer>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// ---- Logged in: dashboard ----
$pdo = get_db();

$statusFilter = $_GET['status'] ?? 'all';
$allowedFilters = ['all', 'pending', 'confirmed', 'cancelled'];
if (!in_array($statusFilter, $allowedFilters, true)) {
    $statusFilter = 'all';
}

$sql = 'SELECT * FROM bookings';
$params = [];
if ($statusFilter !== 'all') {
    $sql .= ' WHERE status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY meeting_date ASC, id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$counts = $pdo->query(
    "SELECT status, COUNT(*) c FROM bookings GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — <?= h(SITE_NAME) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="bg-layer">
  <div class="bg-grid"></div>
  <div class="brush b1"></div>
  <div class="brush b2"></div>
</div>

<div class="admin-wrap">

  <div class="admin-topbar">
    <div class="admin-brand"><i class="fa-solid fa-calendar-days"></i> Meeting Admin</div>
    <div class="admin-user">
      <span><?= h($_SESSION['admin_username']) ?></span>
      <a href="admin/logout.php" class="admin-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log out</a>
    </div>
  </div>

  <div class="admin-stats">
    <div class="stat-box"><div class="stat-num"><?= (int)($counts['pending'] ?? 0) ?></div><div class="stat-label">Pending</div></div>
    <div class="stat-box"><div class="stat-num"><?= (int)($counts['confirmed'] ?? 0) ?></div><div class="stat-label">Confirmed</div></div>
    <div class="stat-box"><div class="stat-num"><?= (int)($counts['cancelled'] ?? 0) ?></div><div class="stat-label">Cancelled</div></div>
  </div>

  <div class="admin-filters">
    <a href="admin.php?status=all" class="filter-chip <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
    <a href="admin.php?status=pending" class="filter-chip <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="admin.php?status=confirmed" class="filter-chip <?= $statusFilter === 'confirmed' ? 'active' : '' ?>">Confirmed</a>
    <a href="admin.php?status=cancelled" class="filter-chip <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Name</th>
          <th>Email</th>
          <th>Amount</th>
          <th>Payment Ref</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$bookings): ?>
          <tr><td colspan="8" class="empty-row">No bookings found.</td></tr>
        <?php endif; ?>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?= h(date('M j, Y', strtotime($b['meeting_date']))) ?></td>
            <td><?= h($b['name']) ?></td>
            <td><?= h($b['email']) ?></td>
            <td><?= h(CURRENCY_LABEL) ?> <?= h(number_format((float)$b['amount'], 2)) ?></td>
            <td><?= h($b['payment_reference'] ?? '—') ?></td>
            <td><span class="badge badge-<?= h($b['payment_status']) ?>"><?= h(ucfirst($b['payment_status'])) ?></span></td>
            <td><span class="badge badge-<?= h($b['status']) ?>"><?= h(ucfirst($b['status'])) ?></span></td>
            <td class="actions-cell">
              <?php if ($b['status'] !== 'confirmed'): ?>
              <form method="post" action="admin/actions.php" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <input type="hidden" name="action" value="confirm">
                <button type="submit" class="mini-btn confirm" title="Confirm payment & booking"><i class="fa-solid fa-check"></i></button>
              </form>
              <?php endif; ?>
              <?php if ($b['status'] !== 'cancelled'): ?>
              <form method="post" action="admin/actions.php" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="mini-btn cancel" title="Cancel & free up the date"><i class="fa-solid fa-xmark"></i></button>
              </form>
              <?php endif; ?>
              <form method="post" action="admin/actions.php" class="inline-form" onsubmit="return confirm('Delete this booking permanently?');">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="mini-btn delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>
</body>
</html>
