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
            <div class="form-group">
                <label for="role">Role</label>
                <select name="role" id="role">
                    <option value="employee" <?= $employee['role'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                    <option value="manager" <?= $employee['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                    <option value="admin" <?= $employee['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" name="department" id="department" value="<?= htmlspecialchars($employee['department'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="hourly_rate">Hourly Rate ($)</label>
                <input type="number" name="hourly_rate" id="hourly_rate" step="0.01" min="0" value="<?= $employee['hourly_rate'] ?? '' ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" <?= $employee['is_active'] ? 'checked' : '' ?>>
                Active Employee
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Employee</button>
            <a href="/admin/employees" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
