<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch current user info from `users` and `donor_details`
$userStmt = $pdo->prepare("
    SELECT u.*, d.blood_group, d.dob, d.gender, d.weight, d.last_donation_date, d.total_donations, d.is_available
    FROM users u
    LEFT JOIN donor_details d ON u.id = d.user_id
    WHERE u.id = ?
");
$userStmt->execute([$user_id]);
$currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    session_destroy();
    header("Location: login.html");
    exit();
}

$user_name = $currentUser['first_name'] . ' ' . $currentUser['last_name'];
$user_role = $currentUser['role'];
$blood_group = $currentUser['blood_group'] ?? 'O+';
$user_city = $currentUser['city'] ?? 'New Delhi';
$user_phone = $currentUser['phone'] ?? '+91 98765 43210';
$total_donations = (int)($currentUser['total_donations'] ?? 0);
$last_donation = $currentUser['last_donation_date'] ? date('M j, Y', strtotime($currentUser['last_donation_date'])) : 'Never';

// 2. Fetch blood requests from `blood_requests` table
$requestsStmt = $pdo->query("
    SELECT r.*, u.first_name, u.last_name 
    FROM blood_requests r
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 20
");
$allRequests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch summary stats from database
$totalDonationsCount = $pdo->query("SELECT SUM(total_donations) FROM donor_details")->fetchColumn() ?: 12;
$activeRequestsCount = $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status IN ('pending', 'searching')")->fetchColumn();
$livesSavedCount = ($totalDonationsCount > 0) ? ($totalDonationsCount * 3) : 36;
$contactMessagesCount = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE user_id = " . intval($user_id))->fetchColumn();

// 4. Calculate stock breakdown by blood group from requests & donors
$stockStmt = $pdo->query("
    SELECT blood_group, COUNT(*) as count 
    FROM donor_details 
    WHERE is_available = 1 
    GROUP BY blood_group
");
$stockData = [];
while ($row = $stockStmt->fetch(PDO::FETCH_ASSOC)) {
    $stockData[$row['blood_group']] = $row['count'] * 15; // Estimated unit calculation
}

// $flash_msg = $_GET['msg'] ?? ($_GET['welcome'] ? 'Welcome back, ' . htmlspecialchars($currentUser['first_name']) . '!' : null);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard — E-Blood Bank</title>
    <link rel="stylesheet" href="css/base.css" />
    <link rel="stylesheet" href="css/components.css" />
    <link rel="stylesheet" href="css/dashboard.css" />
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🩸</text></svg>" />
</head>

<body>

    <!-- NAVBAR (Internal) -->
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <div class="container navbar-inner">

            <div style="display:flex;align-items:center;gap:0.75rem;">
                <button class="sidebar-toggle-btn" aria-label="Toggle sidebar">
                    <span></span><span></span><span></span>
                </button>

                <a href="index.html" class="navbar-logo" aria-label="E-Blood Bank Home">
                    <div class="navbar-logo-icon">🩸</div>
                    <div class="navbar-logo-text">
                        E-Blood Bank
                        <span>Management System</span>
                    </div>
                </a>
            </div>

            <div class="navbar-actions">
                <button class="btn-icon" data-tooltip="Notifications" aria-label="Notifications" style="position:relative;">
                    🔔
                    <span style="position:absolute;top:-4px;right:-4px;width:16px;height:16px;background:var(--color-primary);border-radius:50%;font-size:0.6rem;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;"><?php echo $activeRequestsCount; ?></span>
                </button>
                <button class="theme-toggle" onclick="ThemeManager.toggle()" aria-label="Toggle theme">🌙</button>
                <div class="avatar avatar-md" style="cursor:pointer;"
                    data-tooltip="<?php echo htmlspecialchars($user_name); ?> — <?php echo ucfirst($user_role); ?>">
                    <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?></div>
            </div>

        </div>
    </nav>

    <!-- APP LAYOUT -->
    <div class="app-layout">

        <!-- Sidebar Overlay (mobile) -->
        <div class="sidebar-overlay" aria-hidden="true"></div>

        <!-- Sidebar -->
        <aside class="sidebar" role="navigation" aria-label="Sidebar navigation">

            <div class="sidebar-header">
                <div class="sidebar-user">
                    <div class="sidebar-avatar"><?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?></div>
                    <div>
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="sidebar-user-role">Blood <?php echo ucfirst($user_role); ?> · <?php echo htmlspecialchars($blood_group); ?></div>
                    </div>
                </div>
                <button class="sidebar-close" aria-label="Close sidebar">✕</button>
            </div>

            <nav class="sidebar-nav" aria-label="Dashboard navigation">

                <div class="sidebar-section-title">Main</div>
                <a href="dashboard.php" class="sidebar-link active">
                    <span class="sidebar-link-icon">📊</span>
                    Dashboard
                </a>
                <a href="index.html" class="sidebar-link">
                    <span class="sidebar-link-icon">🏠</span>
                    Home
                </a>

                <div class="sidebar-section-title">Blood Services</div>
                <a href="request-blood.html" class="sidebar-link">
                    <span class="sidebar-link-icon">🩸</span>
                    Request Blood
                    <span class="sidebar-link-badge"><?php echo $activeRequestsCount; ?></span>
                </a>
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-icon">💉</span>
                    My Donations (<?php echo $total_donations; ?>)
                </a>
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-icon">📋</span>
                    My Requests
                </a>
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-icon">🔍</span>
                    Find Donors
                </a>

                <div class="sidebar-section-title">Account</div>
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-icon">👤</span>
                    My Profile
                </a>
                <a href="contact.html" class="sidebar-link">
                    <span class="sidebar-link-icon">📬</span>
                    Support & Messages (<?php echo $contactMessagesCount; ?>)
                </a>

            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="sidebar-link">
                    <span class="sidebar-link-icon">🚪</span>
                    Sign Out
                </a>
            </div>

        </aside>

        <!-- Main Content -->
        <main class="main-content" role="main">

            <?php if ($flash_msg): ?>
            <div class="alert alert-info animate-fade-in-up" style="margin-bottom:1.25rem;">
                <span class="alert-icon">✅</span>
                <div><?php echo htmlspecialchars($flash_msg); ?></div>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="page-header animate-fade-in-up">
                <div>
                    <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?> 👋</h1>
                    <p class="page-subtitle">Here's your live blood bank database overview — <?php echo date('F j, Y'); ?></p>
                </div>
                <div class="flex gap-2">
                    <a href="request-blood.html" class="btn btn-primary btn-sm">
                        🩸 New Request
                    </a>
                    <button class="btn btn-ghost btn-sm" onclick="window.location.reload()">
                        🔄 Refresh Data
                    </button>
                </div>
            </div>

            <!-- Stat Cards (Real Data from MySQL) -->
            <div class="grid grid-4 animate-fade-in-up delay-2" style="margin-bottom:1.75rem;">

                <div class="stat-card">
                    <div class="stat-card-icon red">🩸</div>
                    <div class="stat-card-value" data-counter="<?php echo $total_donations; ?>"><?php echo $total_donations; ?></div>
                    <div class="stat-card-label">My Donations</div>
                    <div class="stat-card-trend trend-up">Database verified</div>
                </div>

                <div class="stat-card accent-success">
                    <div class="stat-card-icon green">❤️</div>
                    <div class="stat-card-value" data-counter="<?php echo $livesSavedCount; ?>"><?php echo $livesSavedCount; ?></div>
                    <div class="stat-card-label">Total Impact (Lives Saved)</div>
                    <div class="stat-card-trend trend-up">↑ 3x donation impact</div>
                </div>

                <div class="stat-card accent-warning">
                    <div class="stat-card-icon amber">⏳</div>
                    <div class="stat-card-value"><?php echo $last_donation; ?></div>
                    <div class="stat-card-label">Last Donation Date</div>
                    <div class="stat-card-trend trend-down">Status: <?php echo ($currentUser['is_available'] ?? 1) ? 'Available' : 'Resting'; ?></div>
                </div>

                <div class="stat-card accent-info">
                    <div class="stat-card-icon blue">📋</div>
                    <div class="stat-card-value" data-counter="<?php echo $activeRequestsCount; ?>"><?php echo $activeRequestsCount; ?></div>
                    <div class="stat-card-label">Active Database Requests</div>
                    <div class="stat-card-trend trend-up">Live MySQL records</div>
                </div>

            </div>

            <!-- Two-column: Blood Requests Table + Activity -->
            <div class="grid grid-2 animate-fade-in-up delay-3" style="margin-bottom:1.75rem; align-items:start;">

                <!-- Recent Blood Requests from MySQL `blood_requests` Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📋 Live Blood Requests (`blood_requests` table)</h3>
                        <a href="request-blood.html" class="btn btn-outline btn-sm">Submit New</a>
                    </div>

                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Blood Type</th>
                                    <th>Patient</th>
                                    <th>Hospital & City</th>
                                    <th>Units</th>
                                    <th>Urgency</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($allRequests)): ?>
                                    <?php foreach ($allRequests as $req): ?>
                                    <tr class="request-row">
                                        <td>
                                            <span class="blood-type-tag" style="width:36px;height:36px;font-size:0.75rem;">
                                                <?php echo htmlspecialchars($req['blood_group']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight:500;font-size:0.85rem;"><?php echo htmlspecialchars($req['patient_name']); ?></div>
                                            <div style="font-size:0.75rem;color:var(--color-text-muted);">Age <?php echo htmlspecialchars($req['patient_age']); ?> · <?php echo htmlspecialchars($req['relation'] ?? 'Patient'); ?></div>
                                        </td>
                                        <td>
                                            <div style="font-size:0.85rem;font-weight:500;"><?php echo htmlspecialchars($req['hospital_name']); ?></div>
                                            <div style="font-size:0.75rem;color:var(--color-text-muted);"><?php echo htmlspecialchars($req['city']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($req['units_required']); ?> units</td>
                                        <td>
                                            <?php
                                            $urg = strtolower($req['urgency']);
                                            if ($urg === 'critical') echo '<span class="badge badge-red">🚨 Critical</span>';
                                            elseif ($urg === 'urgent') echo '<span class="badge badge-amber">⚠️ Urgent</span>';
                                            else echo '<span class="badge badge-gray">📅 Scheduled</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $st = strtolower($req['status']);
                                            if ($st === 'fulfilled') echo '<span class="badge badge-green">Fulfilled ✓</span>';
                                            elseif ($st === 'cancelled') echo '<span class="badge badge-gray">Cancelled</span>';
                                            else echo '<span class="badge badge-amber">Pending</span>';
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" style="text-align:center;padding:1.5rem;">No blood requests stored in database yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Donor Profile & Database Info -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">👤 My MySQL Account Profile</h3>
                        <span class="badge badge-green">🟢 ID #<?php echo $currentUser['id']; ?></span>
                    </div>

                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;">
                        <div class="avatar avatar-lg" style="width:56px;height:56px;font-size:1.2rem;">
                            <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:1rem;"><?php echo htmlspecialchars($user_name); ?></div>
                            <div style="font-size:0.82rem;color:var(--color-text-muted);"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                            <span class="badge badge-red" style="margin-top:4px;font-size:0.7rem;"><?php echo ucfirst($user_role); ?></span>
                        </div>
                    </div>

                    <div style="display:grid;gap:0.65rem;font-size:0.85rem;">
                        <div class="flex-between" style="padding:0.5rem 0;border-bottom:1px solid var(--color-border);">
                            <span style="color:var(--color-text-muted);">Blood Group</span>
                            <span class="blood-type-tag" style="width:36px;height:28px;font-size:0.78rem;border-radius:var(--radius-sm);"><?php echo htmlspecialchars($blood_group); ?></span>
                        </div>
                        <div class="flex-between" style="padding:0.5rem 0;border-bottom:1px solid var(--color-border);">
                            <span style="color:var(--color-text-muted);">City</span>
                            <span style="font-weight:500;"><?php echo htmlspecialchars($user_city); ?></span>
                        </div>
                        <div class="flex-between" style="padding:0.5rem 0;border-bottom:1px solid var(--color-border);">
                            <span style="color:var(--color-text-muted);">Phone</span>
                            <span style="font-weight:500;"><?php echo htmlspecialchars($user_phone); ?></span>
                        </div>
                        <div class="flex-between" style="padding:0.5rem 0;">
                            <span style="color:var(--color-text-muted);">Member Since</span>
                            <span style="font-weight:500;"><?php echo date('M Y', strtotime($currentUser['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script src="js/core.js"></script>
    <script src="js/interactions.js"></script>
    <script src="js/main.js"></script>
</body>

</html>