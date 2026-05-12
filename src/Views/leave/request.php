<?php $title = 'Request Leave'; ?>

<div class="page-header">
    <h1>Request Leave</h1>
    <div class="page-actions">
        <a href="/leave" class="btn btn-outline">← Back to Leave</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="/leave/request">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-group">
            <label for="leave_type">Leave Type</label>
            <select name="leave_type" id="leave_type" required>
                <option value="">Select type...</option>
                <option value="vacation">Vacation</option>
                <option value="sick">Sick Leave</option>
                <option value="personal">Personal</option>
                <option value="unpaid">Unpaid</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" required>
            </div>
        </div>

        <div class="form-group">
            <label for="reason">Reason (optional)</label>
            <textarea name="reason" id="reason" rows="3" placeholder="Brief description..."></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Submit Request</button>
            <a href="/leave" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
