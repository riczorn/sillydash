<?php ob_start(); ?>
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h1 class="mb-0">Settings</h1>
        <button type="button" id="runCronBtn" class="btn-success">
            ▶ Run Cron Now
        </button>
    </div>

    <div id="cronResult"
        style="display:none; margin-bottom: 1.5rem; padding: 1rem; border-radius: 4px; font-size: 0.9rem;"></div>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="result-display success">
            <?= session()->getFlashdata('message') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="result-display error">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('settings/save') ?>" method="post">
        <?= csrf_field() ?>

        <h2>Files</h2>
        <div class="form-group">
            <label for="list_folder">List Folder Path</label>
            <input type="text" name="list_folder" id="list_folder"
                value="<?= esc($config['list_folder'] ?? './files') ?>" placeholder="./files or /absolute/path">
            <small class="form-hint">Path to directory containing .list files. Relative to project root or
                absolute.</small>
        </div>

        <h2>Security</h2>
        <div class="form-group">
            <label for="cron_token">Cron Token</label>
            <div class="password-wrapper">
                <input type="password" name="cron_token" id="cron_token" class="password-input"
                    value="<?= esc($config['cron_token'] ?? '') ?>" placeholder="Secret token for cron endpoint">
                <button type="button" class="toggle-password" tabindex="-1" aria-label="Toggle visibility">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
            <small class="form-hint">Used to authenticate cron requests: <a
                    href="<?= site_url('cron') ?>?token=<?= esc($config['cron_token'] ?? '') ?>&days=3"
                    target="_blank"><?= site_url('cron') ?>?token=YOUR_TOKEN&days=3</a></small>
        </div>

        <h2>SMTP Configuration</h2>
        <div class="form-group">
            <label for="smtp_host">SMTP Host</label>
            <input type="text" name="smtp_host" id="smtp_host" value="<?= esc($config['smtp']['host'] ?? '') ?>"
                placeholder="smtp.example.com">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="smtp_port">Port</label>
                <input type="number" name="smtp_port" id="smtp_port" value="<?= esc($config['smtp']['port'] ?? 587) ?>"
                    min="1" max="65535">
            </div>
            <div class="form-group">
                <label for="smtp_security">Security</label>
                <select name="smtp_security" id="smtp_security">
                    <?php $sec = $config['smtp']['security'] ?? 'tls'; ?>
                    <option value="tls" <?= $sec === 'tls' ? 'selected' : '' ?>>TLS</option>
                    <option value="ssl" <?= $sec === 'ssl' ? 'selected' : '' ?>>SSL</option>
                    <option value="none" <?= $sec === 'none' ? 'selected' : '' ?>>None</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="smtp_user">SMTP Username</label>
            <input type="text" name="smtp_user" id="smtp_user" value="<?= esc($config['smtp']['user'] ?? '') ?>"
                placeholder="user@example.com">
        </div>
        <div class="form-group">
            <label for="smtp_pass">SMTP Password</label>
            <div class="password-wrapper">
                <input type="password" name="smtp_pass" id="smtp_pass" class="password-input"
                    value="<?= esc($config['smtp']['pass'] ?? '') ?>">
                <button type="button" class="toggle-password" tabindex="-1" aria-label="Toggle visibility">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>

        <?php
        $userModel = new \App\Models\UserModel();
        $currentUser = $userModel->find(session()->get('id'));
        $userEmail = $currentUser['email'] ?? '';
        $hasEmail = !empty($userEmail);
        ?>
        <div class="form-group mt-2">
            <button type="button" id="testSmtpBtn" class="btn-success <?= !$hasEmail ? 'disabled' : '' ?>"
                <?= !$hasEmail ? 'disabled' : '' ?>>
                ✉ Test Configuration
            </button>
            <?php if (!$hasEmail): ?>
                <small class="form-hint text-error">Your user account has no email address — set one in Users
                    to enable testing.</small>
            <?php else: ?>
                <small class="form-hint">Sends a test email to <strong>
                        <?= esc($userEmail) ?>
                    </strong></small>
            <?php endif; ?>
            <div id="testSmtpResult"
                style="display:none; margin-top: 0.5rem; padding: 0.75rem; border-radius: 4px; font-size: 0.9rem;">
            </div>
        </div>

        <h2>Database <small class="text-muted" style="font-weight:normal; font-size:0.7em;">(read only)</small>
        </h2>
        <div class="form-group">
            <label>Host</label>
            <input type="text" value="<?= esc($config['database']['hostname'] ?? '') ?>" readonly disabled>
        </div>
        <div class="form-group">
            <label>Database</label>
            <input type="text" value="<?= esc($config['database']['database'] ?? '') ?>" readonly disabled>
        </div>
        <div class="form-group">
            <label>Prefix</label>
            <input type="text" value="<?= esc($config['database']['DBPrefix'] ?? $config['db_prefix'] ?? '') ?>"
                readonly disabled>
        </div>

        <button type="submit" class="btn-primary">Save Settings</button>
    </form>
</div>

<script>
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function () {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);

            if (type === 'text') {
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M1 1l22 22"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path></svg>';
            } else {
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
            }
        });
    });
</script>

<script>
    const cronBtn = document.getElementById('runCronBtn');
    const cronResult = document.getElementById('cronResult');
    // Use the saved config token for the request, as the input field might have unsaved changes
    const cronToken = '<?= esc($config['cron_token'] ?? '') ?>';

    if (cronBtn) {
        cronBtn.addEventListener('click', async function () {
            if (!cronToken) {
                alert('No Cron Token found in configuration. Please save settings first.');
                return;
            }

            cronBtn.disabled = true;
            cronBtn.textContent = '⏳ Running...';
            cronResult.style.display = 'none';

            fetch('<?= site_url('cron') ?>?token=' + encodeURIComponent(cronToken))
                .then(r => r.json())
                .then(data => {
                    cronResult.style.display = 'block';
                    if (data.status === 'success') {
                        cronResult.style.background = 'rgba(76, 175, 80, 0.15)';
                        cronResult.style.borderLeft = '4px solid #4CAF50';
                        cronResult.style.color = '#4CAF50'; // Green text

                        // Format detailed message
                        let msg = `<strong>${data.message}</strong><br>`;
                        msg += `New files scanned: ${data.new_files_scanned}<br>`;
                        msg += `Files processed: ${data.files_processed}`;
                        cronResult.innerHTML = msg;
                    } else {
                        throw new Error(data.error || 'Unknown error');
                    }
                })
                .catch(err => {
                    cronResult.style.display = 'block';
                    cronResult.style.background = 'rgba(207, 102, 121, 0.15)';
                    cronResult.style.borderLeft = '4px solid #cf6679';
                    cronResult.style.color = '#cf6679';
                    cronResult.textContent = 'Cron failed: ' + err.message;
                })
                .finally(() => {
                    cronBtn.disabled = false;
                    cronBtn.textContent = '▶ Run Cron Now';
                });
        });
    }

    // Existing Test Email Script
    const testBtn = document.getElementById('testSmtpBtn');
    const testResult = document.getElementById('testSmtpResult');
    if (testBtn && !testBtn.disabled) {
        testBtn.addEventListener('click', function () {
            testBtn.disabled = true;
            testBtn.textContent = '⏳ Sending...';
            testResult.style.display = 'none';

            const params = new URLSearchParams();
            params.set('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            params.set('smtp_host', document.getElementById('smtp_host').value);
            params.set('smtp_port', document.getElementById('smtp_port').value);
            params.set('smtp_security', document.getElementById('smtp_security').value);
            params.set('smtp_user', document.getElementById('smtp_user').value);
            params.set('smtp_pass', document.getElementById('smtp_pass').value);

            fetch('<?= site_url('settings/test-email') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString()
            })
                .then(r => r.json())
                .then(data => {
                    testResult.style.display = 'block';
                    if (data.success) {
                        testResult.style.background = 'rgba(76, 175, 80, 0.15)';
                        testResult.style.borderLeft = '4px solid #4CAF50';
                        testResult.style.color = '#4CAF50';
                    } else {
                        testResult.style.background = 'rgba(207, 102, 121, 0.15)';
                        testResult.style.borderLeft = '4px solid #cf6679';
                        testResult.style.color = '#cf6679';
                    }
                    testResult.textContent = data.message;
                })
                .catch(err => {
                    testResult.style.display = 'block';
                    testResult.style.background = 'rgba(207, 102, 121, 0.15)';
                    testResult.style.borderLeft = '4px solid #cf6679';
                    testResult.style.color = '#cf6679';
                    testResult.textContent = 'Request failed: ' + err.message;
                })
                .finally(() => {
                    testBtn.disabled = false;
                    testBtn.textContent = '✉ Test Configuration';
                });
        });
    }
</script>
<?php $content = ob_get_clean();
include __DIR__ . '/../layouts/main.php'; ?>