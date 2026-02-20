<?php ob_start(); ?>
<div style="text-align: center; margin-bottom: 2rem;">
    <img src="<?= base_url('assets/images/joker.png') ?>" alt="Silly Dashboard" style="max-height: 200px;">
</div>


<div class="card login-card">
    <h2>Login</h2>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="result-display error"
            style="margin-bottom: 1rem; padding: 1rem; border-left: 4px solid #cf6679; color: #cf6679; background: rgba(255,255,255,0.05);">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('attempt-login') ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?= esc(old('username')) ?>" placeholder="Username"
                required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Password" required>
        </div>
        <button type="submit" class="btn-primary">Login</button>
    </form>
</div>

<style>
    .login-card {
        max-width: 400px;
        margin: 0 auto 2rem auto;
        padding: 2rem;
    }

    .login-card h2 {
        text-align: center;
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        color: var(--text-main);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--text-muted);
        font-size: 0.9rem;
        font-weight: 500;
    }

    .form-group input {
        width: 100%;
        padding: 1rem;
        font-size: 1.1rem;
        background-color: #2a2a2a;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        color: var(--text-main);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--accent-color);
        box-shadow: 0 0 0 3px rgba(187, 134, 252, 0.1);
    }

    .btn-primary {
        width: 100%;
        padding: 1rem;
        font-size: 1.1rem;
        font-weight: 600;
        background-color: var(--accent-color);
        color: #121212;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: opacity 0.2s;
    }

    .btn-primary:hover {
        opacity: 0.9;
    }
</style>


<?php $content = ob_get_clean();
include __DIR__ . '/layouts/main.php'; ?>