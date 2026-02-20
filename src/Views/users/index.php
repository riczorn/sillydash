<?php ob_start(); ?>
<div class="card-header">
    <h1 class="mb-0">Users</h1>
    <a href="<?= site_url('users/create') ?>" class="btn-primary btn-auto-width m-0" style="text-decoration: none;">+
        Create User</a>
</div>

<div class="card">
    <h2>User List</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <?= esc($user['id']) ?>
                        </td>
                        <td>
                            <a href="<?= site_url('users/edit/' . $user['id']) ?>" class="account-domain-link">
                                <?= esc($user['username']) ?>
                            </a>
                        </td>
                        <td>
                            <?= esc($user['email']) ?>
                        </td>
                        <td>
                            <span
                                class="status-indicator <?= $user['role'] === 'admin' ? 'status-green' : 'status-red' ?>"></span>
                            <?= esc(ucfirst($user['role'])) ?>
                        </td>
                        <td>
                            <?= esc($user['created_at']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<?php $content = ob_get_clean();
include __DIR__ . '/../layouts/main.php'; ?>