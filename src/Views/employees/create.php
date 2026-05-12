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

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Employee</button>
            <a href="/admin/employees" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
