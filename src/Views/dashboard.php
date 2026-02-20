<?php ob_start(); ?>

<?php
// Already loaded via functions.php, no need for helper('format')
?>
<h1>Dashboard
    <?php if (!empty($account)): ?> –
        <?= esc($account) ?>
    <?php endif; ?>
</h1>

<div class="card">
    <div class="card-header">
        <h2>Disk Usage Trends</h2>
        <div class="chart-controls">
            <button id="resetZoomBtn" title="Double click chart to reset">Reset Zoom</button>
            <select id="daysFilter">
                <option value="7">Last 7 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="180">Last 6 Months</option>
                <option value="365">Last Year</option>
                <option value="730">Last 2 Years</option>
            </select>
        </div>
    </div>
    <div id="chartContainer" class="dashboard-chart" data-chart-url="<?= site_url('api/chart-data') ?>"
        data-detail-url="<?= site_url('api/chart-detail') ?>" <?php if (!empty($account)): ?>data-account="
        <?= esc($account) ?>" <?php endif; ?>>
    </div>
</div>

<?php if (!empty($account)): ?>
    <div class="card card-spacing">
        <h2>Subdomains –
            <?= esc($account) ?>
        </h2>
        <div id="subdomainChartContainer" class="dashboard-chart-large"
            data-subdomain-url="<?= site_url('api/subdomain-data') ?>" data-account="<?= esc($account) ?>"></div>
    </div>
<?php endif; ?>

<?php if (empty($account)): ?>
    <div class="card card-spacing">
        <h2>Accounts Distribution (Top 15 + Others)</h2>
        <div id="accountsPieChartContainer" class="dashboard-chart-large"
            data-distribution-url="<?= site_url('api/accounts-distribution') ?>"></div>
    </div>
<?php endif; ?>

<!-- Drill-down Modal -->
<div id="drillDownModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeDrillDown()">&times;</span>
        <h3 id="drillDownTitle">Details</h3>
        <div id="drillDownContent">Loading...</div>
    </div>
</div>

<script src="https://d3js.org/d3.v7.min.js"></script>
<script src="<?= base_url('assets/js/sillydash.js') ?>"></script>

<div class="card card-spacing">
    <h2>Data Inspection</h2>
    <div class="table-responsive">
        <?php if (!empty($account)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subdomain</th>
                        <th class="text-right">Size</th>
                        <th class="text-right">Mail</th>
                        <th class="text-right">DB</th>
                        <th class="text-right">Spam</th>
                        <th class="text-right">Log</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subdomainData as $sub => $sizes): ?>
                        <tr>
                            <td>
                                <?= esc($sub) ?>
                            </td>
                            <td class="text-right">
                                <?= formatBytes($sizes['account']) ?>
                            </td>
                            <td class="text-right">
                                <?= $sizes['mail'] ? formatBytes($sizes['mail']) : '-' ?>
                            </td>
                            <td class="text-right">
                                <?= $sizes['db'] ? formatBytes($sizes['db']) : '-' ?>
                            </td>
                            <td class="text-right">
                                <?= $sizes['spam'] ? formatBytes($sizes['spam']) : '-' ?>
                            </td>
                            <td class="text-right">
                                <?= $sizes['log'] ? formatBytes($sizes['log']) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($subdomainData)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No subdomain data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table id="debugTable" class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Accounts</th>
                        <th>Mail</th>
                        <th>DB</th>
                        <th>Spam</th>
                        <th>Log</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Populated by JS -->
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>Overview</h2>
    <p>Total Accounts: <strong>
            <?= count($accounts) ?>
        </strong></p>
    <?php


    ?>
    <p>Total Disk Usage: <strong>
            <?= formatBytes($totalDiskUsage) ?>
        </strong></p>
</div>

<?php $content = ob_get_clean();
include __DIR__ . '/layouts/main.php'; ?>