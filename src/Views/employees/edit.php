<?php $title = 'Edit Employee'; ?>

<div class="page-header">
    <h1>Edit Employee</h1>
    <div class="page-actions">
        <a href="/admin/employees" class="btn btn-outline">← Back</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="/admin/employees/edit/<?= $employee['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" name="first_name" id="first_name" required value="<?= htmlspecialchars($employee['first_name']) ?>">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" name="last_name" id="last_name" required value="<?= htmlspecialchars($employee['last_name']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" name="email" id="email" required value="<?= htmlspecialchars($employee['email']) ?>">
        </div>

        <div class="form-group">
            <label for="password">Password <small>(leave blank to keep current)</small></label>
            <input type="password" name="password" id="password" minlength="6">
        </div>

        <div class="form-row">
            <?php if ($isAdmin): ?>
            <div class="form-group">
                <label for="role">Role</label>
                <select name="role" id="role">
                    <option value="employee" <?= $employee['role'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                    <option value="manager" <?= $employee['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                    <option value="admin" <?= $employee['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="inspector" <?= $employee['role'] === 'inspector' ? 'selected' : '' ?>>Inspector</option>
                </select>
            </div>
            <div class="form-group" id="manager-field">
                <label for="manager_id">Manager</label>
                <select name="manager_id" id="manager_id">
                    <option value="">Select manager...</option>
                    <?php foreach ($managers as $manager): ?>
                        <option value="<?= (int) $manager['id'] ?>" <?= (int) ($employee['manager_id'] ?? 0) === (int) $manager['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']) ?> (<?= htmlspecialchars(ucfirst($manager['role'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" name="department" id="department" value="<?= htmlspecialchars($employee['department'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="hourly_rate">Hourly Rate ($)</label>
                <input type="number" name="hourly_rate" id="hourly_rate" step="0.01" min="0" value="<?= $employee['hourly_rate'] ?? '' ?>">
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <div class="form-group" id="team-members-field" style="display: none;">
            <label for="team_member_ids">Team Members</label>
            <select name="team_member_ids[]" id="team_member_ids" multiple size="8">
                <?php foreach ($teamCandidates as $teamMember): ?>
                    <option value="<?= (int) $teamMember['id'] ?>" <?= in_array((int) $teamMember['id'], $assignedTeamIds, true) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($teamMember['first_name'] . ' ' . $teamMember['last_name']) ?> (<?= htmlspecialchars(ucfirst($teamMember['role'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Hold Ctrl/Cmd to select multiple members.</small>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" <?= $employee['is_active'] ? 'checked' : '' ?>>
                Active Employee
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Employee</button>
            <a href="/admin/employees/<?= (int) $employee['id'] ?>/schedule" class="btn btn-outline">📅 Work Schedule</a>
            <a href="/admin/employees/<?= (int) $employee['id'] ?>/orgchart" class="btn btn-outline">🏢 Org Chart</a>
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
        const teamMembersSelect = document.getElementById('team_member_ids');

        function updateRoleFields() {
            if (!roleSelect || !managerField || !teamMembersField || !managerSelect) return;

            const role = roleSelect.value;
            const isEmployee = role === 'employee';
            const isManager = role === 'manager';

            const showManagerField = isEmployee || isManager;
            const showTeamMembersField = isManager;

            managerField.style.display = showManagerField ? '' : 'none';
            managerSelect.required = isEmployee;
            if (!showManagerField) {
                managerSelect.value = '';
            }

            teamMembersField.style.display = showTeamMembersField ? '' : 'none';
            if (!showTeamMembersField && teamMembersSelect) {
                Array.from(teamMembersSelect.options).forEach((option) => {
                    option.selected = false;
                });
            }
        }

        if (roleSelect) roleSelect.addEventListener('change', updateRoleFields);
        if (roleSelect) updateRoleFields();
    })();
</script>
