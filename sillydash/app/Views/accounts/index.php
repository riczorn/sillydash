<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<h1>Accounts</h1>

<div class="card">
    <?php helper('format'); ?>
    <h2>Accounts List</h2>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; background: var(--bg-body); border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 10px;">Domain</th>
                    <th style="padding: 10px;">Username</th>
                    <th style="padding: 10px;">Home Directory</th>
                    <th style="padding: 10px;">DB Names</th>
                    <th style="padding: 10px;">Size</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                    <?php
                    $depth = $account['depth'] ?? 0;
                    $hasChildren = $account['has_children'] ?? false;
                    $parentId = $account['parent_id'] ?? 0;
                    $isChild = $depth > 0;
                    $displayStyle = $isChild ? 'display: none;' : '';
                    $rowClass = 'account-row';
                    if ($isChild)
                        $rowClass .= ' child-of-' . $parentId;
                    ?>
                    <tr class="<?= $rowClass ?>" data-id="<?= $account['id'] ?>"
                        style="border-bottom: 1px solid var(--border-color); <?= $displayStyle ?>">
                        <td style="padding: 10px;">
                            <div style="display: flex; align-items: center; margin-left: <?= $depth * 20 ?>px;">
                                <!-- Fixed-width container for toggle/spacer to ensure alignment -->
                                <div style="display:inline-flex; justify-content:center; align-items:center; width:24px; margin-right:5px;">
                                    <?php if ($hasChildren): ?>
                                        <button type="button" class="toggle-children" data-id="<?= $account['id'] ?>" 
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;">
                                            <span class="toggle-icon" style="color: #4CAF50; font-weight: bold; font-size: 1.2em;">+</span>
                                        </button>
                                    <?php elseif ($depth > 0): ?>
                                        <span style="color: var(--text-muted); font-size: 1.2em;">↳</span>
                                    <?php endif; ?>
                                </div>

                                <a href="<?= site_url('/?account=' . urlencode($account['domain'])) ?>" 
                                   style="color: var(--accent-color); text-decoration: none; font-weight: <?= $depth === 0 ? 'bold' : 'normal' ?>;">
                                    <?= esc($account['domain']) ?>
                                    <?php if (($account['children_count'] ?? 0) > 0): ?>
                                        <span style="color: var(--text-muted); font-weight: normal; margin-left: 5px;">(+<?= $account['children_count'] ?>)</span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </td>
                        <td style="padding: 10px; color: var(--text-muted);">
                            <?= esc($account['username']) ?>
                        </td>
                        <td style="padding: 10px; color: var(--text-muted); font-size: 0.9em;">
                            <?= esc($account['home_directory']) ?>
                        </td>
                        <td style="padding: 10px; color: var(--text-muted); font-size: 0.9em;">
                            <?= esc($account['db_names']) ?>
                        </td>
                        <td style="padding: 10px;"><strong>
                                <?= formatBytes($account['total_size']) ?>
                            </strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.querySelectorAll('.toggle-children').forEach(btn => {
        btn.innerHTML = '<span style="color: #4CAF50; font-weight: bold; font-size: 1.2em;">+</span>';

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const parentId = this.getAttribute('data-id');
            const children = document.querySelectorAll('.child-of-' + parentId);
            const isExpanded = this.getAttribute('data-expanded') === 'true';

            children.forEach(row => {
                if (isExpanded) {
                    row.style.display = 'none';
                    // Recursively hide grandchildren? For now, just direct children. 
                    // If we want recursive hide, we'd need to trigger click on expanded children too.
                    // But usually collapsing parent collapses everything visually.
                    // Actually, if we hide the child row, the grandchild row (which is child-of-child) 
                    // remains potentially "visible" but detached? 
                    // No, table structure is flat. Grandchildren are just valid rows.
                    // We need to hide ALL descendants if we collapse.
                    hideDescendants(parentId);
                } else {
                    row.style.display = 'table-row';
                }
            });

            if (isExpanded) {
                this.innerHTML = '<span style="color: #4CAF50; font-weight: bold; font-size: 1.2em;">+</span>';
                this.setAttribute('data-expanded', 'false');
            } else {
                this.innerHTML = '<span style="color: #f44336; font-weight: bold; font-size: 1.2em;">-</span>';
                this.setAttribute('data-expanded', 'true');
            }
        });
    });

    function hideDescendants(parentId) {
        // Find visible children of this parent
        const children = document.querySelectorAll('.child-of-' + parentId);
        children.forEach(childRow => {
            childRow.style.display = 'none';
            // Reset button if it exists
            const btn = childRow.querySelector('.toggle-children');
            if (btn && btn.getAttribute('data-expanded') === 'true') {
                btn.innerHTML = '<span style="color: #4CAF50; font-weight: bold; font-size: 1.2em;">+</span>';
                btn.setAttribute('data-expanded', 'false');
                // Recurse
                const childId = childRow.getAttribute('data-id');
                hideDescendants(childId);
            }
        });
    }
</script>

<?= $this->endSection() ?>