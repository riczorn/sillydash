<?php ob_start(); ?>
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h1>Edit User</h1>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="result-display error">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <p>
                    <?= esc($error) ?>
                </p>
            <?php endforeach ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('users/update/' . $user['id']) ?>" method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?= esc(old('username', $user['username'])) ?>"
                required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= esc(old('email', $user['email'])) ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">New Password <small class="text-muted"
                        style="font-weight:normal;">(optional)</small></label>
                <input type="password" name="password" id="password" placeholder="••••••••">
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select name="role" id="role">
                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
        </div>

        <div class="form-group" id="account-selection" style="transition: opacity 0.3s;">
            <label>Allowed Accounts <small style="font-weight:normal; color:var(--text-muted);">(Top-level
                    domains)</small></label>
            <div class="account-list">
                <?php
                $allowed = explode(',', $user['allowed_accounts'] ?? '');
                ?>
                <?php if (empty($accounts)): ?>
                    <div style="padding: 0.5rem; color: var(--text-muted); font-style: italic;">No accounts found.</div>
                <?php else: ?>
                    <?php foreach ($accounts as $acc): ?>
                        <div class="checkbox-item">
                            <label>
                                <input type="checkbox" name="allowed_accounts[]" value="<?= esc($acc['domain']) ?>"
                                    <?= in_array($acc['domain'], $allowed) ? 'checked' : '' ?>>
                                <?= esc($acc['domain']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <small class="form-hint">Admin users have access to all accounts regardless of selection.</small>
        </div>

        <button type="submit" class="btn-primary">Save Changes</button>
        <a href="<?= site_url('users') ?>" class="btn-secondary">Cancel</a>
    </form>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role');
        const accountSection = document.getElementById('account-selection');

        function toggleAccounts() {
            if (roleSelect.value === 'admin') {
                accountSection.style.opacity = '0.5';
                accountSection.style.pointerEvents = 'none';
            } else {
                accountSection.style.opacity = '1';
                accountSection.style.pointerEvents = 'auto';
            }
        }

        roleSelect.addEventListener('change', toggleAccounts);
        toggleAccounts(); // Initial state
    });
</script>
<?php $content = ob_get_clean();
include __DIR__ . '/../layouts/main.php'; ?>