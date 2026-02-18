<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="text-align: center; margin-bottom: 2rem;">
    <img src="<?= base_url('public/images/joker.png') ?>" alt="Silly Dashboard" style="max-height: 200px;">
</div>
<h1>System Status</h1>

<div class="card">
    <h2>Configuration & Database</h2>
    <ul>
        <li>
            <span class="status-indicator <?= $configLoaded ? 'status-green' : 'status-red' ?>"></span>
            Config File Loaded: <strong>
                <?= $configLoaded ? 'Yes' : 'No' ?>
            </strong>
        </li>
        <li>
            <span class="status-indicator <?= $dbConnected ? 'status-green' : 'status-red' ?>"></span>
            Database Connected: <strong>
                <?= $dbConnected ? 'Yes' : 'No' ?>
            </strong>
        </li>
    </ul>
</div>

<div class="card">
    <h2>File System</h2>
    <ul>
        <li>
            List Folder Path: <code><?= esc($listFolder) ?></code>
        </li>
        <li>
            <span class="status-indicator <?= $folderReadable ? 'status-green' : 'status-red' ?>"></span>
            Folder Readable: <strong>
                <?= $folderReadable ? 'Yes' : 'No' ?>
            </strong>
        </li>
    </ul>
</div>

<?= $this->endSection() ?>