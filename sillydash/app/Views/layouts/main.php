<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silly Dashboard</title>
    <link rel="stylesheet" href="<?= base_url('public/css/utils.css') ?>">
</head>

<body>

    <header>
        <div class="logo">
            <img src="<?= base_url('public/images/joker.png') ?>" alt="Silly Dashboard"
                style="height: 36px; width: auto; vertical-align: middle; margin-right: 0.5rem;">
            Silly Dashboard
        </div>

        <?php
        $uri = uri_string();
        $isSetup = (strpos($uri, 'setup') === 0);
        if (!$isSetup):
            ?>

            <nav class="nav-center">
                <?php if (session()->get('isLoggedIn')): ?>
                    <a href="<?= site_url('/') ?>">Dashboard</a>
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
                <?php else: ?>
                    <a href="#" id="loginBtn">Login</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if (!$isSetup && session()->get('isLoggedIn')): ?>
        <aside>
            <ul>
                <li><a href="<?= site_url('/') ?>">Dashboard</a></li>
                <li><a href="<?= site_url('accounts') ?>">Accounts</a></li>
            </ul>

            <?php
            // Only attempt to load top accounts if config exists and we are logged in
            $topAccounts = [];
            if (file_exists(ROOTPATH . 'config.php')) {
                try {
                    $db = \Config\Database::connect();
                    $builder = $db->table('accounts a')
                        ->select('a.domain, SUM(r.size_bytes) as total_size')
                        ->join('records r', 'r.account_id = a.id')
                        ->where('r.kind', 'account')
                        ->where("DATE(r.time) = (SELECT MAX(DATE(time)) FROM " . $db->prefixTable('records') . ")", null, false)
                        ->groupStart()
                        ->where('a.parent_id', null)
                        ->orWhere('a.parent_id', 0)
                        ->groupEnd()
                        ->groupBy('a.id')
                        ->orderBy('total_size', 'DESC')
                        ->limit(15);

                    if (session()->get('role') !== 'admin') {
                        $allowedStr = session()->get('allowed_accounts');
                        if (!empty($allowedStr)) {
                            $allowed = explode(',', $allowedStr);
                            $builder->whereIn('a.domain', $allowed);
                        } else {
                            $builder->where('1=0');
                        }
                    }

                    $topAccounts = $builder->get()->getResultArray();
                } catch (\Throwable $e) {
                    // Start of table 'silly_accounts' doesn't exist yet or DB connection failed
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
                                <a href="<?= site_url('/?account=' . urlencode($acc['domain'])) ?>">
                                    <span class="account-domain"><?= esc($acc['domain']) ?></span>
                                    <span class="account-size"><?= number_format($acc['total_size'] / (1024 * 1024 * 1024), 1) ?>
                                        GB</span>
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
        <?php if (!session()->get('isLoggedIn') && empty($this->sections['content'])): ?>
            <div
                style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:60vh; opacity:0.7;">
                <img src="<?= base_url('public/images/joker.png') ?>" alt="Silly Dashboard"
                    style="max-width: 200px; margin-bottom: 1.5rem;">
                <p style="color:var(--text-muted); font-size:1.1rem;">Please log in to access the dashboard.</p>
            </div>
        <?php endif; ?>
        <?= $this->renderSection('content') ?>
    </main>

    <footer>
        &copy; 2026 fasterweb.net
    </footer>

    <!-- Login Modal -->
    <?php if (!session()->get('isLoggedIn')): ?>
        <div id="loginModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Login</h2>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="error" style="color: #cf6679; margin-bottom: 1rem;">
                        <?= session()->getFlashdata('error') ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="error" style="color: #cf6679; margin-bottom: 1rem;">
                        <?php foreach (session()->getFlashdata('errors') as $error): ?>
                            <p><?= esc($error) ?></p>
                        <?php endforeach ?>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('login') ?>" method="post">
                    <?= csrf_field() ?>
                    <div style="margin-bottom: 1rem;">
                        <label for="username"
                            style="display:block; margin-bottom:0.5rem; color:var(--text-muted);">Username</label>
                        <input type="text" name="username" id="username" value="<?= old('username') ?>" required
                            style="width:100%; padding:0.5rem; background:var(--bg-body); border:1px solid var(--border-color); color:var(--text-main); border-radius:4px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label for="password"
                            style="display:block; margin-bottom:0.5rem; color:var(--text-muted);">Password</label>
                        <input type="password" name="password" id="password" required
                            style="width:100%; padding:0.5rem; background:var(--bg-body); border:1px solid var(--border-color); color:var(--text-main); border-radius:4px;">
                    </div>
                    <button type="submit"
                        style="width:100%; padding:0.75rem; background:var(--secondary-color); border:none; border-radius:4px; font-weight:600; cursor:pointer; color:#000;">Login</button>
                </form>
            </div>
        </div>



        <script>
            // Get the modal
            var modal = document.getElementById("loginModal");
            var btn = document.getElementById("loginBtn");
            var span = document.getElementsByClassName("close-modal")[0];

            if (btn) {
                btn.onclick = function (e) {
                    e.preventDefault();
                    modal.style.display = "block";
                }
            }

            if (span) {
                span.onclick = function () {
                    modal.style.display = "none";
                }
            }

            window.onclick = function (event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }

            // Auto-open if errors, but NOT if we are on the setup page (which handles its own errors)
            <?php
            $uri = uri_string();
            $isSetup = (strpos($uri, 'setup') === 0);
            if (!$isSetup && (session()->getFlashdata('error') || session()->getFlashdata('errors'))):
                ?>
                modal.style.display = "block";
            <?php endif; ?>
        </script>
    <?php endif; ?>
</body>

</html>