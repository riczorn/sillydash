<?php ob_start(); ?>
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h1>Manual Configuration Required</h1>

    <div class="result-display warning">
        <?= esc($message) ?>
    </div>

    <p>Create a file named <code>config.php</code> in the root directory (<code><?= ROOTPATH ?></code>) and paste the
        code below:</p>

    <pre class="code-block"><?= esc($configContent) ?></pre>

    <div class="mt-2">
        <a href="<?= site_url('/') ?>" class="btn-primary"
            style="display: inline-block; text-align: center; text-decoration: none;">I have created the file,
            continue</a>
    </div>
</div>


<?php $content = ob_get_clean();
include __DIR__ . '/../layouts/main.php'; ?>