<?php ob_start(); ?>
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h1>Initial Setup</h1>
    <p>Welcome to Silly Dashboard. Please configure your database and admin account.</p>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="result-display error">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="result-display error">
            <ul>
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <li>
                        <?= esc($error) ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('setup/attempt') ?>" method="post">
        <?= csrf_field() ?>

        <h2>Database Configuration</h2>
        <div class="form-group">
            <label for="db_host">Database Host</label>
            <input type="text" name="db_host" id="db_host" value="<?= old('db_host') ? old('db_host') : 'localhost' ?>"
                required>
        </div>
        <div class="form-group">
            <label for="db_name">Database Name</label>
            <input type="text" name="db_name" id="db_name" value="<?= old('db_name') ?>" required>
        </div>
        <div class="form-group">
            <label for="db_user">Database User</label>
            <input type="text" name="db_user" id="db_user" value="<?= old('db_user') ?>" required>
        </div>
        <div class="form-group">
            <label for="db_pass">Database Password</label>
            <div class="password-wrapper">
                <input type="password" name="db_pass" id="db_pass" class="password-input" value="<?= old('db_pass') ?>">
                <button type="button" class="toggle-password" tabindex="-1" aria-label="Toggle password visibility">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="feather feather-eye">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>
        <div class="form-group">
            <label for="db_prefix">Table Prefix</label>
            <input type="text" name="db_prefix" id="db_prefix"
                value="<?= old('db_prefix') ? old('db_prefix') : 'silly_' ?>">
        </div>

        <div class="form-group">
            <label for="list_folder">List Folder Path (Absolute or Relative to utils)</label>
            <input type="text" name="list_folder" id="list_folder"
                value="<?= old('list_folder') ? old('list_folder') : './files' ?>">
        </div>

        <div class="form-group">
            <label for="cron_token">Cron Token</label>
            <input type="text" name="cron_token" id="cron_token"
                value="<?= old('cron_token') ? old('cron_token') : 'super-secret-token' ?>">
            <small class="form-hint">
                Secret token for authenticating cron requests: <code>/cron?token=YOUR_TOKEN</code>
            </small>
        </div>
        <h2>Admin Account</h2>
        <div class="form-group">
            <label for="admin_user">Username</label>
            <input type="text" name="admin_user" id="admin_user"
                value="<?= old('admin_user') ? old('admin_user') : 'admin' ?>" required>
        </div>
        <div class="form-group">
            <label for="admin_email">Email</label>
            <input type="email" name="admin_email" id="admin_email" value="<?= old('admin_email') ?>" required>
        </div>
        <div class="form-group">
            <label for="admin_pass">Password</label>
            <div class="password-wrapper">
                <input type="password" name="admin_pass" id="admin_pass" class="password-input" required>
                <button type="button" class="toggle-password" tabindex="-1" aria-label="Toggle password visibility">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="feather feather-eye">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary">Save & Install</button>
    </form>
</div>



<script>
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function () {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);

            // Toggle Eye Icon
            if (type === 'text') {
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M1 1l22 22"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path></svg>';
            } else {
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
            }
        });
    });
</script>
<?php $content = ob_get_clean();
include __DIR__ . '/../layouts/main.php'; ?>