<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php helper('format'); ?>
<h1>Dashboard<?php if (!empty($account)): ?> – <?= esc($account) ?><?php endif; ?></h1>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
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
    <div id="chartContainer" style="width:100%; height:400px;" data-chart-url="<?= site_url('api/chart-data') ?>"
        data-detail-url="<?= site_url('api/chart-detail') ?>" <?php if (!empty($account)): ?>data-account="<?= esc($account) ?>" <?php endif; ?>></div>
</div>

<?php if (!empty($account)): ?>
    <div class="card" style="margin-top: 1rem;">
        <h2>Subdomains – <?= esc($account) ?></h2>
        <div id="subdomainChartContainer" style="width:100%; height:450px;"
            data-subdomain-url="<?= site_url('api/subdomain-data') ?>" data-account="<?= esc($account) ?>"></div>
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
<script src="<?= base_url('public/js/utils.js') ?>"></script>

<div class="card" style="margin-top: 1rem;">
    <h2>Data Inspection</h2>
    <div style="overflow-x:auto;">
        <?php if (!empty($account)): ?>
            <table style="width:100%; border-collapse: collapse; margin-top:1rem;">
                <thead>
                    <tr style="background:var(--bg-body); border-bottom:2px solid var(--border-color); text-align:left;">
                        <th style="padding:8px;">Subdomain</th>
                        <th style="padding:8px; text-align:right;">Size</th>
                        <th style="padding:8px; text-align:right;">Mail</th>
                        <th style="padding:8px; text-align:right;">DB</th>
                        <th style="padding:8px; text-align:right;">Spam</th>
                        <th style="padding:8px; text-align:right;">Log</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subdomainData as $sub => $sizes): ?>
                        <tr style="border-bottom:1px solid var(--border-color);">
                            <td style="padding:8px;"><?= esc($sub) ?></td>
                            <td style="padding:8px; text-align:right;"><?= formatBytes($sizes['account']) ?></td>
                            <td style="padding:8px; text-align:right;"><?= $sizes['mail'] ? formatBytes($sizes['mail']) : '-' ?>
                            </td>
                            <td style="padding:8px; text-align:right;"><?= $sizes['db'] ? formatBytes($sizes['db']) : '-' ?>
                            </td>
                            <td style="padding:8px; text-align:right;"><?= $sizes['spam'] ? formatBytes($sizes['spam']) : '-' ?>
                            </td>
                            <td style="padding:8px; text-align:right;"><?= $sizes['log'] ? formatBytes($sizes['log']) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($subdomainData)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:1rem;">No subdomain data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table id="debugTable" style="width:100%; border-collapse: collapse; margin-top:1rem;">
                <thead>
                    <tr style="background:var(--bg-body); border-bottom:2px solid var(--border-color); text-align:left;">
                        <th style="padding:8px;">Date</th>
                        <th style="padding:8px;">Accounts</th>
                        <th style="padding:8px;">Mail</th>
                        <th style="padding:8px;">DB</th>
                        <th style="padding:8px;">Spam</th>
                        <th style="padding:8px;">Log</th>
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
    $totalDiskUsage = 0;
    foreach ($accounts as $m) {
        $totalDiskUsage += $m['total_size'];
    }

    ?>
    <p>Total Disk Usage: <strong>
            <?= formatBytes($totalDiskUsage) ?>
        </strong></p>
</div>


<?= $this->endSection() ?>