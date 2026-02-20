<?php ob_start(); ?>
<div class="card" style="max-width: 500px; margin: 0 auto;">
    <h1>Change Password</h1>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="result-display"
            style="border-left: 4px solid var(--secondary-color); color: var(--secondary-color); background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <?= session()->getFlashdata('message') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('errors')): ?>
        <div
            style="border-left: 4px solid #cf6679; color: #cf6679; background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <p>
                    <?= esc($error) ?>
                </p>
            <?php endforeach ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('change-password') ?>" method="post">
        <?= csrf_field() ?>

        <div style="margin-bottom: 1rem;">
            <label for="password" style="display:block; margin-bottom:0.5rem; font-weight:500;">New Password</label>
            <input type="password" name="password" id="password" required
                style="width:100%; padding:0.75rem; border:1px solid var(--border-color); background:#2a2a2a; color:var(--text-main); border-radius:4px; font-size:1rem;">
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="confpassword" style="display:block; margin-bottom:0.5rem; font-weight:500;">Confirm
                Password</label>
            <input type="password" name="confpassword" id="confpassword" required
                style="width:100%; padding:0.75rem; border:1px solid var(--border-color); background:#2a2a2a; color:var(--text-main); border-radius:4px; font-size:1rem;">
        </div>

        <button type="submit"
            style="width:100%; padding:0.75rem 1.5rem; background:var(--accent-color); color:#121212; border:none; border-radius:4px; font-size:1rem; font-weight:600; cursor:pointer; transition:opacity 0.2s;">
            Update Password
        </button>
    </form>
</div>
<?php $content = ob_get_clean();
include __DIR__ . '/../layouts/main.php'; ?>