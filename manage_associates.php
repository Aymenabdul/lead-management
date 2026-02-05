<!DOCTYPE html>
<?php
// Require authentication and admin role
require_once __DIR__ . '/auth_middleware.php';
$user = AuthMiddleware::requireAuth();

// Check if user is admin
if ($user['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Associates | Lead Maintenance</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom Confirmation Modal */
        .confirm-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.2s ease;
        }

        .confirm-overlay.active {
            display: flex;
        }

        .confirm-dialog {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            max-width: 450px;
            width: 90%;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .confirm-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }

        .confirm-icon.warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.1));
            color: #f59e0b;
            border: 2px solid rgba(245, 158, 11, 0.3);
        }

        .confirm-icon.danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1));
            color: #ef4444;
            border: 2px solid rgba(239, 68, 68, 0.3);
        }

        .confirm-icon.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.1));
            color: #10b981;
            border: 2px solid rgba(16, 185, 129, 0.3);
        }

        .confirm-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 1rem;
            text-align: center;
        }

        .confirm-message {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
            text-align: center;
            white-space: pre-line;
        }

        .confirm-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .confirm-btn {
            padding: 0.75rem 2rem;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .confirm-btn-cancel {
            background: rgba(148, 163, 184, 0.1);
            color: var(--text-muted);
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .confirm-btn-cancel:hover {
            background: rgba(148, 163, 184, 0.2);
            transform: translateY(-2px);
        }

        .confirm-btn-confirm {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .confirm-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .confirm-btn-confirm.danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .confirm-btn-confirm.danger:hover {
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .confirm-btn-confirm.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .confirm-btn-confirm.warning:hover {
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <!-- Sidebar -->
    <?php $currentPage = 'marketing_associates';
    include __DIR__ . '/components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div>
                <h1 class="page-title">Manage Marketing Associates</h1>
                <p style="color: var(--text-muted)">Create and manage marketing associate accounts</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addAssociateModal')">
                <i class="fa-solid fa-plus"></i> New Marketing Associate
            </button>
        </header>

        <!-- Associates Table -->
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="associatesTableBody">
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted);">Loading marketing
                                associates...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Associate Modal -->
    <div class="modal" id="addAssociateModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('addAssociateModal')">&times;</button>
            <h2 style="margin-bottom: 1.5rem;">Add New Marketing Associate</h2>
            <form id="addAssociateForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="johndoe">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="john@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required
                        placeholder="Minimum 6 characters">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn"
                        style="background: transparent; border: 1px solid var(--text-muted); color: var(--text-muted);"
                        onclick="closeModal('addAssociateModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Marketing Associate</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('changePasswordModal')">&times;</button>
            <h2 style="margin-bottom: 1.5rem;">Change Password</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                Changing password for: <strong id="changePasswordUsername" style="color: var(--text-main);"></strong>
            </p>
            <form id="changePasswordForm">
                <input type="hidden" name="user_id" id="changePasswordUserId">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" required
                        placeholder="Minimum 6 characters">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required
                        placeholder="Re-enter password">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn"
                        style="background: transparent; border: 1px solid var(--text-muted); color: var(--text-muted);"
                        onclick="closeModal('changePasswordModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Modal -->
    <?php include __DIR__ . '/components/security_modal.php'; ?>

    <!-- Custom Confirmation Modal -->
    <div class="confirm-overlay" id="confirmOverlay">
        <div class="confirm-dialog">
            <div class="confirm-icon" id="confirmIcon">
                <i class="fa-solid fa-question-circle"></i>
            </div>
            <h3 class="confirm-title" id="confirmTitle">Confirm Action</h3>
            <p class="confirm-message" id="confirmMessage">Are you sure?</p>
            <div class="confirm-buttons">
                <button class="confirm-btn confirm-btn-cancel" id="confirmCancel">
                    <i class="fa-solid fa-times"></i> Cancel
                </button>
                <button class="confirm-btn confirm-btn-confirm" id="confirmOk">
                    <i class="fa-solid fa-check"></i> Confirm
                </button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="js/main.js"></script>
    <script>
        // Custom Confirmation Dialog
        function showConfirm(options) {
            return new Promise((resolve) => {
                const overlay = document.getElementById('confirmOverlay');
                const icon = document.getElementById('confirmIcon');
                const title = document.getElementById('confirmTitle');
                const message = document.getElementById('confirmMessage');
                const cancelBtn = document.getElementById('confirmCancel');
                const okBtn = document.getElementById('confirmOk');

                // Set content
                title.textContent = options.title || 'Confirm Action';
                message.textContent = options.message || 'Are you sure?';

                // Set icon and style
                icon.className = 'confirm-icon';
                okBtn.className = 'confirm-btn confirm-btn-confirm';

                if (options.type === 'danger') {
                    icon.classList.add('danger');
                    icon.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i>';
                    okBtn.classList.add('danger');
                } else if (options.type === 'warning') {
                    icon.classList.add('warning');
                    icon.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i>';
                    okBtn.classList.add('warning');
                } else {
                    icon.classList.add('success');
                    icon.innerHTML = '<i class="fa-solid fa-question-circle"></i>';
                }

                // Set button text
                cancelBtn.innerHTML = `<i class="fa-solid fa-times"></i> ${options.cancelText || 'Cancel'}`;
                okBtn.innerHTML = `<i class="fa-solid fa-check"></i> ${options.confirmText || 'Confirm'}`;

                // Show overlay
                overlay.classList.add('active');

                // Handle clicks
                const handleCancel = () => {
                    overlay.classList.remove('active');
                    cancelBtn.removeEventListener('click', handleCancel);
                    okBtn.removeEventListener('click', handleOk);
                    resolve(false);
                };

                const handleOk = () => {
                    overlay.classList.remove('active');
                    cancelBtn.removeEventListener('click', handleCancel);
                    okBtn.removeEventListener('click', handleOk);
                    resolve(true);
                };

                cancelBtn.addEventListener('click', handleCancel);
                okBtn.addEventListener('click', handleOk);

                // Close on overlay click
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        handleCancel();
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadAssociates();
            document.getElementById('addAssociateForm').addEventListener('submit', handleAddAssociate);
            document.getElementById('changePasswordForm').addEventListener('submit', handleChangePassword);
        });

        async function loadAssociates() {
            try {
                const response = await fetch('/api/associates.php?role=user');
                const data = await response.json();

                const tbody = document.getElementById('associatesTableBody');
                tbody.innerHTML = '';

                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No marketing associates found.</td></tr>';
                    return;
                }

                data.forEach(assoc => {
                    const tr = document.createElement('tr');
                    const statusBadge = assoc.is_active ?
                        '<span class="badge badge-paid">Active</span>' :
                        '<span class="badge badge-new">Inactive</span>';

                    const toggleText = assoc.is_active ? 'Deactivate' : 'Activate';
                    const toggleIcon = assoc.is_active ? 'fa-ban' : 'fa-check';
                    const toggleColor = assoc.is_active ? '#f59e0b' : '#10b981';

                    tr.innerHTML = `
                        <td style="font-weight: 600; color: var(--text-main);">${assoc.full_name || '-'}</td>
                        <td>${assoc.username}</td>
                        <td style="color: var(--text-muted);">${assoc.email}</td>
                        <td>${statusBadge}</td>
                        <td style="color: var(--text-muted); font-size: 0.9em;">${new Date(assoc.created_at).toLocaleDateString()}</td>
                        <td style="color: var(--text-muted); font-size: 0.9em;">${assoc.last_login ? new Date(assoc.last_login).toLocaleDateString() : 'Never'}</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <button 
                                    class="btn" 
                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: rgba(${assoc.is_active ? '245, 158, 11' : '16, 185, 129'}, 0.15); color: ${toggleColor}; border: 1px solid rgba(${assoc.is_active ? '245, 158, 11' : '16, 185, 129'}, 0.3);"
                                    onclick="toggleStatus(${assoc.id}, ${assoc.is_active})"
                                    title="${toggleText}">
                                    <i class="fa-solid ${toggleIcon}"></i>
                                </button>
                                <button 
                                    class="btn" 
                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: rgba(99, 102, 241, 0.15); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.3);"
                                    onclick="openChangePassword(${assoc.id}, '${assoc.username}')"
                                    title="Change Password">
                                    <i class="fa-solid fa-key"></i>
                                </button>
                                <button 
                                    class="btn" 
                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);"
                                    onclick="deleteAssociate(${assoc.id}, '${assoc.username}')"
                                    title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (error) {
                console.error('Error loading associates:', error);
                showToast('Failed to load marketing associates', 'error');
            }
        }

        async function handleAddAssociate(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/api/associates.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Marketing Associate created successfully', 'success');
                    closeModal('addAssociateModal');
                    e.target.reset();
                    loadAssociates();
                } else {
                    showToast(result.error || 'Failed to create marketing associate', 'error');
                }
            } catch (error) {
                console.error(error);
                showToast('Error creating marketing associate', 'error');
            }
        }

        // Toggle active/inactive status
        async function toggleStatus(userId, currentStatus) {
            const action = currentStatus ? 'deactivate' : 'activate';
            const actionTitle = currentStatus ? 'Deactivate Marketing Associate' : 'Activate Marketing Associate';

            const confirmed = await showConfirm({
                title: actionTitle,
                message: `Are you sure you want to ${action} this marketing associate?\n\n${currentStatus ? 'They will not be able to login until reactivated.' : 'They will be able to login and manage leads.'}`,
                type: 'warning',
                confirmText: action.charAt(0).toUpperCase() + action.slice(1)
            });

            if (!confirmed) {
                return;
            }

            try {
                const response = await fetch('/api/associates.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'toggle_status',
                        user_id: userId,
                        is_active: !currentStatus
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast(`Marketing Associate ${action}d successfully`, 'success');
                    loadAssociates();
                } else {
                    showToast(result.error || 'Failed to update status', 'error');
                }
            } catch (error) {
                console.error(error);
                showToast('Error updating status', 'error');
            }
        }

        // Open change password modal
        function openChangePassword(userId, username) {
            document.getElementById('changePasswordUserId').value = userId;
            document.getElementById('changePasswordUsername').textContent = username;
            openModal('changePasswordModal');
        }

        // Handle change password
        async function handleChangePassword(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            // Validate passwords match
            if (data.new_password !== data.confirm_password) {
                showToast('Passwords do not match', 'error');
                return;
            }

            if (data.new_password.length < 6) {
                showToast('Password must be at least 6 characters', 'error');
                return;
            }

            try {
                const response = await fetch('/api/associates.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'change_password',
                        user_id: data.user_id,
                        new_password: data.new_password
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Password changed successfully', 'success');
                    closeModal('changePasswordModal');
                    e.target.reset();
                } else {
                    showToast(result.error || 'Failed to change password', 'error');
                }
            } catch (error) {
                console.error(error);
                showToast('Error changing password', 'error');
            }
        }

        // Delete associate
        async function deleteAssociate(userId, username) {
            // First confirmation
            const confirmed1 = await showConfirm({
                title: 'Delete Marketing Associate',
                message: `Are you sure you want to DELETE ${username}?\n\nThis will permanently delete:\n• The marketing associate account\n• All their leads\n• All their conversions\n\nThis action cannot be undone!`,
                type: 'danger',
                confirmText: 'Yes, Delete'
            });

            if (!confirmed1) {
                return;
            }

            // Double confirmation for safety
            const confirmed2 = await showConfirm({
                title: 'Final Confirmation',
                message: `This is permanent and cannot be reversed!\n\nAre you absolutely sure you want to delete ${username}?`,
                type: 'danger',
                confirmText: 'Delete Forever'
            });

            if (!confirmed2) {
                return;
            }

            try {
                const response = await fetch('/api/associates.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Marketing Associate deleted successfully', 'success');
                    loadAssociates();
                } else {
                    showToast(result.error || 'Failed to delete marketing associate', 'error');
                }
            } catch (error) {
                console.error(error);
                showToast('Error deleting marketing associate', 'error');
            }
        }
    </script>

</body>

</html>