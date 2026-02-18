<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h1>Users</h1>
    <a href="<?= site_url('users/create') ?>" class="btn-primary"
        style="width: auto; margin-top: 0; padding: 0.5rem 1rem; text-decoration: none;">+ Create User</a>
</div>

<div class="card">
    <h2>User List</h2>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; background: var(--bg-body); border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Username</th>
                    <th style="padding: 10px;">Email</th>
                    <th style="padding: 10px;">Role</th>
                    <th style="padding: 10px;">Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 10px;">
                            <?= esc($user['id']) ?>
                        </td>
                        <td style="padding: 10px;">
                            <a href="<?= site_url('users/edit/' . $user['id']) ?>"
                                style="color: var(--accent-color); text-decoration: none;">
                                <?= esc($user['username']) ?>
                            </a>
                        </td>
                        <td style="padding: 10px;">
                            <?= esc($user['email']) ?>
                        </td>
                        <td style="padding: 10px;">
                            <span
                                class="status-indicator <?= $user['role'] === 'admin' ? 'status-green' : 'status-red' ?>"></span>
                            <?= esc(ucfirst($user['role'])) ?>
                        </td>
                        <td style="padding: 10px;">
                            <?= esc($user['created_at']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>