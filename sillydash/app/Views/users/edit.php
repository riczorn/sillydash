<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h1>Edit User</h1>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="result-display error"
            style="border-left: 4px solid #cf6679; color: #cf6679; background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <p><?= esc($error) ?></p>
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

        <div class="form-row" style="display: flex; gap: 1rem;">
            <div class="form-group" style="flex: 1;">
                <label for="password">New Password <small
                        style="color:var(--text-muted); font-weight:normal;">(optional)</small></label>
                <input type="password" name="password" id="password" placeholder="••••••••">
            </div>

            <div class="form-group" style="flex: 1;">
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

<style>
    .form-group {
        margin-bottom: 1rem;
    }

    label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        background-color: #2a2a2a;
        color: var(--text-main);
        border-radius: 4px;
        font-size: 1rem;
    }

    input:focus,
    select:focus {
        outline: 2px solid var(--accent-color);
        border-color: transparent;
    }

    .account-list {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid var(--border-color);
        padding: 0.5rem;
        border-radius: 4px;
        background: #2a2a2a;
    }

    .checkbox-item {
        margin-bottom: 0.25rem;
    }

    .checkbox-item label {
        font-weight: normal;
        display: flex;
        align-items: center;
        cursor: pointer;
        margin-bottom: 0;
    }

    .checkbox-item input {
        width: auto;
        margin-right: 0.75rem;
    }

    .btn-primary {
        background-color: var(--accent-color);
        color: #121212;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        margin-top: 1rem;
        transition: opacity 0.2s;
    }

    .btn-primary:hover {
        opacity: 0.9;
    }

    .btn-secondary {
        display: block;
        text-align: center;
        color: var(--text-muted);
        text-decoration: none;
        margin-top: 0.75rem;
        font-size: 0.9rem;
    }

    .btn-secondary:hover {
        color: var(--text-main);
    }

    .form-hint {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
        display: block;
    }
</style>

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
<?= $this->endSection() ?>