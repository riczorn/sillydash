<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h1>Manual Configuration Required</h1>

    <div class="result-display warning" style="border-left: 4px solid #ff9800; color: #ff9800;">
        <?= esc($message) ?>
    </div>

    <p>Create a file named <code>config.php</code> in the root directory (<code><?= ROOTPATH ?></code>) and paste the
        code below:</p>

    <pre
        style="background: #1e1e1e; padding: 1rem; border-radius: 4px; overflow-x: auto; color: #dcdCDC; border: 1px solid #333;"><?= esc($configContent) ?></pre>

    <div style="margin-top: 1.5rem;">
        <a href="<?= site_url('/') ?>" class="btn-primary"
            style="display: inline-block; text-align: center; text-decoration: none;">I have created the file,
            continue</a>
    </div>
</div>

<style>
    .btn-primary {
        background-color: var(--accent-color);
        color: #121212;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s;
    }

    .btn-primary:hover {
        opacity: 0.9;
    }

    .result-display {
        background-color: rgba(255, 255, 255, 0.05);
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
</style>
<?= $this->endSection() ?>