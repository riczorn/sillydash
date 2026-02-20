<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silly Dashboard</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/sillydash.css') ?>">
</head>

<body>

    <header>
        <div class="logo">
            <a href="<?= site_url('') ?>">
                <img src="<?= base_url('assets/images/joker.png') ?>" alt="Silly Dashboard">
                Silly Dashboard
            </a>
        </div>

        <?php
        $uri = uri_string();
        // Adjust for subdirectory if needed, but uri_string helper handles request uri.
        // Logic: strpos($uri, 'setup')
        $isSetup = (strpos($uri, 'setup') !== false);
        if (!$isSetup):
            ?>

            <nav class="nav-center">
                <?php if (session()->get('isLoggedIn')): ?>
                    <a href="<?= site_url('') ?>">Dashboard</a>
                    <?php if (session()->get('role') === 'admin'): ?>
                        <a href="<?= site_url('users') ?>">Users</a>
                        <a href="<?= site_url('settings') ?>">Settings</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="user-menu">
                <?php if (session()->get('isLoggedIn')): ?>
                    <span>
                        <?= esc(session()->get('username')) ?>
                    </span>
                    <a href="<?= site_url('logout') ?>">Logout</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if (!$isSetup && session()->get('isLoggedIn')): ?>
        <aside>
            <ul>
                <li><a href="<?= site_url('') ?>">Dashboard</a></li>
                <li><a href="<?= site_url('accounts') ?>">Accounts</a></li>
            </ul>

            <?php
            // Only attempt to load top accounts if config exists and we are logged in
            $topAccounts = [];
            if (file_exists(ROOTPATH . 'config.php')) {
                try {
                    $db = \App\Core\Database::getInstance();

                    $prefix = $db->getPrefix();
                    $accountsTable = $prefix . 'accounts';
                    $recordsTable = $prefix . 'records';

                    // Native Query
                    $sql = "SELECT a.domain, SUM(r.size_bytes) as total_size 
                            FROM {$accountsTable} a
                            JOIN {$recordsTable} r ON r.account_id = a.id
                            WHERE r.kind = 'account'
                            AND DATE(r.time) = (SELECT MAX(DATE(time)) FROM {$recordsTable})
                            AND (a.parent_id IS NULL OR a.parent_id = 0)";

                    if (session()->get('role') !== 'admin') {
                        $allowedStr = session()->get('allowed_accounts');
                        if (!empty($allowedStr)) {
                            // Need to protect against SQL injection if directly interpolating?
                            // Better to use FIND_IN_SET or prepared statement with IN clause.
                            // For simplicity given array of allowed domains strings:
                            $allowed = explode(',', $allowedStr);
                            $placeholders = implode(',', array_fill(0, count($allowed), '?'));
                            $sql .= " AND a.domain IN ($placeholders)";
                            $params = $allowed;
                        } else {
                            $sql .= " AND 1=0";
                            $params = [];
                        }
                    } else {
                        $params = [];
                    }

                    $sql .= " GROUP BY a.id ORDER BY total_size DESC LIMIT 15";

                    $topAccounts = $db->fetchAll($sql, $params);

                } catch (\Throwable $e) {
                    // Start of table 'accounts' doesn't exist yet or DB connection failed
                    $topAccounts = [];
                }
            }
            ?>
            <?php if (!empty($topAccounts)): ?>
                <?php $activeAccount = $_GET['account'] ?? null; ?>
                <div class="sidebar-top-accounts">
                    <h4>Top Accounts</h4>
                    <ul>
                        <?php foreach ($topAccounts as $acc): ?>
                            <li<?= $activeAccount === $acc['domain'] ? ' class="active"' : '' ?>>
                                <a href="<?= site_url('?account=' . urlencode($acc['domain'])) ?>">
                                    <span class="account-domain">
                                        <?= esc($acc['domain']) ?>
                                    </span>
                                    <span class="account-size">
                                        <?= number_format($acc['total_size'] / (1024 * 1024 * 1024), 1) ?>
                                        GB
                                    </span>
                                </a>
                                </li>
                            <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </aside>
    <?php else: ?>
        <!-- Empty sidebar placeholder or nothing, grid will keep space -->
        <aside></aside>
    <?php endif; ?>

    <main>
        <?php if (!session()->get('isLoggedIn') && empty($content)): ?>
            <div class="empty-state">
                <img src="<?= base_url('assets/images/joker.png') ?>" alt="Silly Dashboard">
                <p>Please log in to access the dashboard.</p>
            </div>
        <?php endif; ?>
        <?= $content ?? '' ?>
    </main>

    <footer>
        &copy; 2026 fasterweb.net
    </footer>


</body>

</html>