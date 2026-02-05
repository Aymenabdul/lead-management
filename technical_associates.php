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
    <title>Technical Team | Lead Maintenance</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom Confirmation Modal (Copied from manage_associates.php) */
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

        .confirm-btn-confirm {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .confirm-btn-confirm.danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <!-- Sidebar -->
    <?php $currentPage = 'technical_associates';
    include __DIR__ . '/components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div>
                <h1 class="page-title">Technical Team</h1>
                <p style="color: var(--text-muted)">Manage technical associates and assignments</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addTechnicalModal')">
                <i class="fa-solid fa-plus"></i> New Technical Associate
            </button>
        </header>

        <!-- Associates Table -->
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Associate Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Projects Assigned</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="technicalTableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted);">Loading technical
                                team...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Technical Associate Modal -->
    <div class="modal" id="addTechnicalModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('addTechnicalModal')">&times;</button>
            <h2 style="margin-bottom: 1.5rem;">Add Technical Associate</h2>
            <form id="addTechnicalForm">
                <input type="hidden" name="role" value="technical">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="Jane Dev">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="janedev">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="jane@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Min 6 chars">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn"
                        style="background: transparent; border: 1px solid var(--border); color: var(--text-muted);"
                        onclick="closeModal('addTechnicalModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Account</button>
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

    <!-- Scripts -->
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

                title.textContent = options.title || 'Confirm Action';
                message.textContent = options.message || 'Are you sure?';

                icon.className = 'confirm-icon';
                okBtn.className = 'confirm-btn confirm-btn-confirm';

                if (options.type === 'danger') {
                    icon.classList.add('danger');
                    icon.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i>';
                    okBtn.classList.add('danger');
                } else {
                    icon.classList.add('warning');
                    icon.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i>';
                    okBtn.classList.add('warning');
                }

                cancelBtn.innerHTML = `<i class="fa-solid fa-times"></i> ${options.cancelText || 'Cancel'}`;
                okBtn.innerHTML = `<i class="fa-solid fa-check"></i> ${options.confirmText || 'Confirm'}`;

                overlay.classList.add('active');

                const handleCancel = () => {
                    overlay.classList.remove('active');
                    cleanup();
                    resolve(false);
                };

                const handleOk = () => {
                    overlay.classList.remove('active');
                    cleanup();
                    resolve(true);
                };

                const cleanup = () => {
                    cancelBtn.removeEventListener('click', handleCancel);
                    okBtn.removeEventListener('click', handleOk);
                }

                cancelBtn.addEventListener('click', handleCancel);
                okBtn.addEventListener('click', handleOk);

                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) handleCancel();
                });
            });
        }

        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.style.borderLeft = type === 'success' ? '4px solid var(--success)' : '4px solid var(--danger)';
            toast.innerHTML = type === 'success' ? '<i class="fa-solid fa-check-circle" style="color:var(--success)"></i> ' + message : '<i class="fa-solid fa-exclamation-circle" style="color:var(--danger)"></i> ' + message;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
        }

        async function logout() {
            try { await fetch('/api/logout.php'); window.location.href = '/login.php'; }
            catch (e) { window.location.href = '/login.php'; }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadTechnicalAssociates();

            document.getElementById('addTechnicalForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData.entries());

                try {
                    const res = await fetch('/api/associates.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    const result = await res.json();
                    if (result.success) {
                        showToast('Technical Associate created');
                        closeModal('addTechnicalModal');
                        e.target.reset();
                        loadTechnicalAssociates();
                    } else {
                        showToast(result.error || 'Failed to create', 'error');
                    }
                } catch (err) {
                    showToast('Error creating associate', 'error');
                }
            });

            document.getElementById('changePasswordForm').addEventListener('submit', handleChangePassword);
        });

        async function loadTechnicalAssociates() {
            try {
                const res = await fetch('/api/technical_associates.php');
                const data = await res.json();

                const tbody = document.getElementById('technicalTableBody');
                tbody.innerHTML = '';

                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No technical associates found.</td></tr>';
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
                        <td>
                            <div style="font-weight: 600; color: var(--primary); cursor: pointer;" onclick="viewDetails(${assoc.id}, '${assoc.full_name || assoc.username}')">
                                ${assoc.full_name || assoc.username}
                            </div>
                        </td>
                        <td style="color: var(--text-muted);">${assoc.username}</td>
                        <td style="color: var(--text-muted);">${assoc.email}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-weight: 600; color: var(--primary); font-size: 1.1rem;">${assoc.project_count}</span>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">Projects</span>
                            </div>
                        </td>
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

            } catch (err) {
                console.error(err);
                showToast('Failed to load technical team', 'error');
            }
        }

        function viewDetails(userId, name) {
            window.location.href = `/technical_details.php?id=${userId}&name=${encodeURIComponent(name)}`;
        }

        async function toggleStatus(userId, currentStatus) {
            const action = currentStatus ? 'deactivate' : 'activate';
            const actionTitle = currentStatus ? 'Deactivate Associate' : 'Activate Associate';

            const confirmed = await showConfirm({
                title: actionTitle,
                message: `Are you sure you want to ${action} this technical associate?\n\n${currentStatus ? 'They will not be able to login until reactivated.' : 'They will be able to login.'}`,
                type: 'warning',
                confirmText: action.charAt(0).toUpperCase() + action.slice(1)
            });

            if (!confirmed) return;

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
                    showToast(`Associate ${action}d successfully`);
                    loadTechnicalAssociates();
                } else {
                    showToast(result.error || 'Failed to update status', 'error');
                }
            } catch (error) {
                showToast('Error updating status', 'error');
            }
        }

        function openChangePassword(userId, username) {
            document.getElementById('changePasswordUserId').value = userId;
            document.getElementById('changePasswordUsername').textContent = username;
            openModal('changePasswordModal');
        }

        async function handleChangePassword(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

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
                    showToast('Password changed successfully');
                    closeModal('changePasswordModal');
                    e.target.reset();
                } else {
                    showToast(result.error || 'Failed to change password', 'error');
                }
            } catch (error) {
                showToast('Error changing password', 'error');
            }
        }

        async function deleteAssociate(userId, username) {
            const confirmed1 = await showConfirm({
                title: 'Delete Technical Associate',
                message: `Are you sure you want to DELETE ${username}?\n\nThis will permanently delete:\n• The user account\n• All their project history\n\nThis action cannot be undone!`,
                type: 'danger',
                confirmText: 'Yes, Delete'
            });

            if (!confirmed1) return;

            const confirmed2 = await showConfirm({
                title: 'Final Confirmation',
                message: `This is permanent and cannot be reversed!\n\nDelete ${username}?`,
                type: 'danger',
                confirmText: 'Delete Forever'
            });

            if (!confirmed2) return;

            try {
                const response = await fetch('/api/associates.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                const result = await response.json();
                if (result.success) {
                    showToast('Associate deleted successfully');
                    loadTechnicalAssociates();
                } else {
                    showToast(result.error || 'Failed to delete associate', 'error');
                }
            } catch (error) {
                showToast('Error deleting associate', 'error');
            }
        }
    </script>
</body>

</html>