<?php $title = 'Add Employee'; ?>

<div class="page-header">
    <h1>Add Employee</h1>
    <div class="page-actions">
        <a href="/admin/employees" class="btn btn-outline">← Back</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="/admin/employees/create">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" name="first_name" id="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" name="last_name" id="last_name" required>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" name="email" id="email" required>
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" name="password" id="password" required minlength="6">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="role">Role</label>
                <select name="role" id="role">
                    <option value="employee">Employee</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                    <option value="inspector">Inspector</option>
                </select>
            </div>
            <div class="form-group" id="manager-field">
                <label for="manager_id">Manager</label>
                <select name="manager_id" id="manager_id">
                    <option value="">Select manager...</option>
                    <?php foreach ($managers as $manager): ?>
                        <option value="<?= (int) $manager['id'] ?>">
                            <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']) ?> (<?= htmlspecialchars(ucfirst($manager['role'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" name="department" id="department">
            </div>
            <div class="form-group">
                <label for="hourly_rate">Hourly Rate ($)</label>
                <input type="number" name="hourly_rate" id="hourly_rate" step="0.01" min="0">
            </div>
        </div>

        <div class="form-group" id="team-members-field" style="display: none;">
            <label for="team_member_ids">Team Members</label>
            <select name="team_member_ids[]" id="team_member_ids" multiple size="8">
                <?php foreach ($teamCandidates as $teamMember): ?>
                    <option value="<?= (int) $teamMember['id'] ?>">
                        <?= htmlspecialchars($teamMember['first_name'] . ' ' . $teamMember['last_name']) ?> (<?= htmlspecialchars(ucfirst($teamMember['role'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Hold Ctrl/Cmd to select multiple members.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Employee</button>
            <a href="/admin/employees" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<script>
    (function () {
        const roleSelect = document.getElementById('role');
        const managerField = document.getElementById('manager-field');
        const managerSelect = document.getElementById('manager_id');
        const teamMembersField = document.getElementById('team-members-field');

        function updateRoleFields() {
            if (!roleSelect || !managerField || !teamMembersField || !managerSelect) return;

            const role = roleSelect.value;
            const showManagerField = role === 'employee' || role === 'manager';
            const showTeamMembersField = role === 'manager';

            managerField.style.display = showManagerField ? '' : 'none';
            managerSelect.required = role === 'employee';
            if (!showManagerField) {
                managerSelect.value = '';
            }

            teamMembersField.style.display = showTeamMembersField ? '' : 'none';
            if (!showTeamMembersField) {
                Array.from(document.getElementById('team_member_ids').options).forEach((option) => {
                    option.selected = false;
                });
            }
        }

        roleSelect.addEventListener('change', updateRoleFields);
        updateRoleFields();
    })();
</script>
