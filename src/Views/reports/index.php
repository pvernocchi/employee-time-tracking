<?php $title = 'Reports'; ?>

<div class="page-header">
    <h1>Reports & Export</h1>
</div>

<div class="card">
    <h2>Generate Timesheet Report</h2>
    <form method="GET" action="/admin/reports/export">
        <div class="form-row">
            <div class="form-group">
                <label for="employee_id">Employee</label>
                <select name="employee_id" id="employee_id">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?> <?= $emp['department'] ? '(' . htmlspecialchars($emp['department']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="format">Format</label>
                <select name="format" id="format">
                    <option value="csv">CSV (Excel)</option>
                    <option value="view">View in Browser</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </div>
    </form>
</div>
