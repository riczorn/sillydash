<?php ob_start(); ?>
<h1>Accounts</h1>

<div class="card">
    <?php // helper('format'); formatBytes is global ?>
    <h2>Accounts List</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Username</th>
                    <th>Home Directory</th>
                    <th>DB Names</th>
                    <th>Size</th>
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
                    <tr class="<?= $rowClass ?>" data-id="<?= $account['id'] ?>" style="<?= $displayStyle ?>">
                        <td>
                            <div class="account-tree-node" style="margin-left: <?= $depth * 20 ?>px;">
                                <!-- Fixed-width container for toggle/spacer to ensure alignment -->
                                <div class="account-toggle-wrapper">
                                    <?php if ($hasChildren): ?>
                                        <button type="button" class="account-toggle-btn toggle-children"
                                            data-id="<?= $account['id'] ?>">
                                            <span class="account-toggle-icon">+</span>
                                        </button>
                                    <?php elseif ($depth > 0): ?>
                                        <span class="account-toggle-sub">↳</span>
                                    <?php endif; ?>
                                </div>

                                <a href="<?= site_url('?account=' . urlencode($account['domain'])) ?>"
                                    class="account-domain-link <?= $depth === 0 ? 'root-node' : '' ?>">
                                    <?= esc($account['domain']) ?>
                                    <?php if (($account['children_count'] ?? 0) > 0): ?>
                                        <span class="account-children-count">(+<?= $account['children_count'] ?>)</span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </td>
                        <td class="text-muted">
                            <?= esc($account['username']) ?>
                        </td>
                        <td class="text-muted text-monospace">
                            <?= esc($account['home_directory']) ?>
                        </td>
                        <td class="text-muted text-monospace">
                            <?= esc($account['db_names']) ?>
                        </td>
                        <td class="text-strong">
                            <?= formatBytes($account['total_size']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isset($unmatched) && count($unmatched) > 0): ?>
    <h2 style="margin-top: 30px;">Unmatched Accounts</h2>
    <div class="card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Path (Home Directory)</th>
                        <th>Size</th>
                        <th>Occurrences</th>
                        <th class="col-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unmatched as $u): ?>
                        <tr>
                            <td class="text-monospace"><?= esc($u['path']) ?></td>
                            <td class="text-strong"><?= formatBytes($u['total_size']) ?></td>
                            <td><?= esc($u['occurrences']) ?></td>
                            <td>
                                <button type="button" class="btn-primary assign-btn"
                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem;"
                                    data-path="<?= htmlspecialchars($u['path']) ?>">Assign</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Assign Modal -->
<dialog id="assignModal" class="assign-modal">
    <h3>Assign Unmatched Path</h3>
    <p class="modal-path-display" id="modalPathDisplay"></p>

    <form id="assignForm">
        <input type="hidden" name="path" id="modalPathInput">

        <div class="form-group">
            <label class="block-label">Action</label>
            <label class="radio-label"><input type="radio" name="action" value="create" checked
                    onchange="toggleActionFields()"> Create new account</label>
            <label><input type="radio" name="action" value="link" onchange="toggleActionFields()"> Link to
                existing</label>
        </div>

        <div class="form-group">
            <label class="block-label">Domain / Alias Name</label>
            <input type="text" name="domain" id="modalDomainInput" class="form-control" required>
            <small class="form-hint">The identifier/domain for this new record.</small>
        </div>

        <div class="form-group" id="parentSelectGroup" style="display: none;">
            <label class="block-label">Parent Account</label>
            <select name="parent_id" id="modalParentSelect" class="form-control">
                <option value="">-- Select Parent --</option>
                <?php if (isset($flatAccounts)): ?>
                    <?php foreach ($flatAccounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>"><?= esc($acc['domain']) ?> (<?= esc($acc['home_directory']) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-actions">
            <button type="button" class="btn-secondary"
                onclick="document.getElementById('assignModal').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="margin-top: 0;">Assign</button>
        </div>
    </form>
</dialog>

<script>
    document.querySelectorAll('.toggle-children').forEach(btn => {
        btn.innerHTML = '<span class="account-toggle-icon">+</span>';

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const parentId = this.getAttribute('data-id');
            const children = document.querySelectorAll('.child-of-' + parentId);
            const isExpanded = this.getAttribute('data-expanded') === 'true';

            children.forEach(row => {
                if (isExpanded) {
                    row.style.display = 'none';
                    // Recursively hide descendants
                    const childId = row.getAttribute('data-id');
                    hideDescendants(childId);
                } else {
                    row.style.display = 'table-row';
                }
            });

            if (isExpanded) {
                this.innerHTML = '<span class="account-toggle-icon">+</span>';
                this.setAttribute('data-expanded', 'false');
            } else {
                this.innerHTML = '<span class="account-toggle-icon expanded">-</span>';
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
                btn.innerHTML = '<span class="account-toggle-icon">+</span>';
                btn.setAttribute('data-expanded', 'false');
                // Recurse
                const childId = childRow.getAttribute('data-id');
                hideDescendants(childId);
            }
        });
    }

    // Modal Logic
    const assignModal = document.getElementById('assignModal');
    const pathDisplay = document.getElementById('modalPathDisplay');
    const pathInput = document.getElementById('modalPathInput');
    const domainInput = document.getElementById('modalDomainInput');
    const parentSelectGroup = document.getElementById('parentSelectGroup');
    const parentSelect = document.getElementById('modalParentSelect');

    document.querySelectorAll('.assign-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const path = this.getAttribute('data-path');
            pathDisplay.textContent = "Path: " + path;
            pathInput.value = path;

            // Try to guess a domain name
            let guessedDomain = path.split('/').pop();
            domainInput.value = guessedDomain || '';

            // Reset UI
            document.querySelector('input[name="action"][value="create"]').checked = true;
            toggleActionFields();

            assignModal.showModal();
        });
    });

    function toggleActionFields() {
        const action = document.querySelector('input[name="action"]:checked').value;
        if (action === 'link') {
            parentSelectGroup.style.display = 'block';
            parentSelect.required = true;
        } else {
            parentSelectGroup.style.display = 'none';
            parentSelect.required = false;
        }
    }

    document.getElementById('assignForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        const formData = new FormData(this);

        fetch('<?= site_url('accounts/assign') ?>', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Assign';
                }
            })
            .catch(err => {
                alert('Error assigning path');
                console.error(err);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Assign';
            });
    });
</script>

<?php $content = ob_get_clean();
include __DIR__ . '/../layouts/main.php'; ?>