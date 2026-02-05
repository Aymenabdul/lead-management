<!DOCTYPE html>
<?php
// Require authentication and technical role
require_once __DIR__ . '/auth_middleware.php';
$user = AuthMiddleware::requireAuth();

// Check if user is technical
if ($user['role'] !== 'technical') {
    header('Location: /index.php');
    exit;
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects | Lead Maintenance</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .days-left {
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            display: inline-block;
            font-size: 0.8rem;
        }

        .days-left.overdue {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            color: #ef4444;
        }

        .days-left.warning {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.1));
            color: #f59e0b;
        }

        .days-left.safe {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(22, 163, 74, 0.1));
            color: #22c55e;
        }

        .sheet-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
        }

        .sheet-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        /* Status Dropdown Styling */
        .status-dropdown {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='white' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            padding-right: 2rem !important;
            transition: all 0.2s;
        }

        .status-dropdown:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .status-dropdown:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .status-dropdown option {
            background: var(--card-bg);
            color: var(--text-main);
            padding: 0.5rem;
        }

        .sheet-btn i {
            margin-right: 0.5rem;
        }

        .project-description {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php $currentPage = 'projects';
    include __DIR__ . '/components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div>
                <h1 class="page-title">My Projects</h1>
                <p style="color: var(--text-muted)">View and manage your assigned projects</p>
            </div>
        </header>

        <!-- Stats Cards -->
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card"
                style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem; font-weight: 500;">Total
                            Assigned</h3>
                        <div style="font-size: 2rem; font-weight: 700;" id="totalProjects">0</div>
                    </div>
                    <i class="fa-solid fa-briefcase" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>

            <div class="card"
                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem; font-weight: 500;">Completed
                            On Time</h3>
                        <div style="font-size: 2rem; font-weight: 700;" id="completedOnTime">0</div>
                    </div>
                    <i class="fa-solid fa-check-circle" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>

            <div class="card"
                style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem; font-weight: 500;">Overdue
                        </h3>
                        <div style="font-size: 2rem; font-weight: 700;" id="overdueProjects">0</div>
                    </div>
                    <i class="fa-solid fa-exclamation-triangle" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>

            <div class="card"
                style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem; font-weight: 500;">Pending
                        </h3>
                        <div style="font-size: 2rem; font-weight: 700;" id="pendingProjects">0</div>
                    </div>
                    <i class="fa-solid fa-clock" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem;">Assigned Projects</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days Left</th>
                            <th>Status</th>
                            <th>Sheet Action</th>
                        </tr>
                    </thead>
                    <tbody id="projectsTableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted);">Loading projects...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Calculate days left based on status
        function calculateDaysLeft(endDate, status) {
            // For completed projects, show on-time message
            if (status === 'Completed') {
                return {
                    text: '✓ On Time',
                    class: 'safe'
                };
            }

            // For on-hold projects, show on-hold message
            if (status === 'On Hold') {
                return {
                    text: '⏸ On Hold',
                    class: 'warning'
                };
            }

            // Only calculate days for Pending and In Progress
            if (!endDate) return { text: 'No deadline', class: 'safe' };

            const end = new Date(endDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            end.setHours(0, 0, 0, 0);

            const diffTime = end - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays < 0) {
                return {
                    text: `${Math.abs(diffDays)} days overdue`,
                    class: 'overdue'
                };
            } else if (diffDays === 0) {
                return {
                    text: 'Due today',
                    class: 'warning'
                };
            } else if (diffDays <= 7) {
                return {
                    text: `${diffDays} days left`,
                    class: 'warning'
                };
            } else {
                return {
                    text: `${diffDays} days left`,
                    class: 'safe'
                };
            }
        }

        // Format date for display
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        // Get status badge HTML
        function getStatusDropdown(projectId, currentStatus) {
            const statuses = ['Pending', 'In Progress', 'Completed', 'On Hold'];
            const statusColors = {
                'Pending': 'secondary',
                'In Progress': 'warning',
                'Completed': 'success',
                'On Hold': 'danger'
            };

            return `
                <select 
                    class="status-dropdown badge badge-${statusColors[currentStatus] || 'secondary'}" 
                    onchange="updateProjectStatus(${projectId}, this.value, this)"
                    style="cursor: pointer; border: none; padding: 0.35rem 0.75rem; font-weight: 500;">
                    ${statuses.map(status => `
                        <option value="${status}" ${status === currentStatus ? 'selected' : ''}>
                            ${status}
                        </option>
                    `).join('')}
                </select>
            `;
        }

        // Update project status
        async function updateProjectStatus(projectId, newStatus, selectElement) {
            console.log('updateProjectStatus called:', { projectId, newStatus });
            const originalStatus = selectElement.dataset.originalStatus || selectElement.value;
            selectElement.dataset.originalStatus = originalStatus;

            try {
                const response = await fetch('/api/update_project_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        project_id: projectId,
                        status: newStatus
                    })
                });

                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('Response data:', result);

                if (result.success) {
                    // Update dropdown styling based on new status
                    const statusColors = {
                        'Pending': 'secondary',
                        'In Progress': 'warning',
                        'Completed': 'success',
                        'On Hold': 'danger'
                    };
                    selectElement.className = `status-dropdown badge badge-${statusColors[newStatus]}`;
                    selectElement.dataset.originalStatus = newStatus;

                    showToast('Status updated successfully', 'success');

                    // Reload projects to update stats
                    loadProjects();
                } else {
                    // Revert on error
                    selectElement.value = originalStatus;
                    showToast(result.error || 'Failed to update status', 'error');
                }
            } catch (error) {
                console.error('Error updating status:', error);
                selectElement.value = originalStatus;
                showToast('Error updating status', 'error');
            }
        }

        // Load projects
        async function loadProjects() {
            try {
                const response = await fetch('/api/technical_projects.php', {
                    credentials: 'include'
                });

                if (!response.ok) {
                    // Try to get error message from response
                    const errorData = await response.json().catch(() => null);
                    const errorMessage = errorData?.error || `Server error: ${response.status}`;
                    throw new Error(errorMessage);
                }

                const projects = await response.json();

                // Check if response has an error property
                if (projects.error) {
                    throw new Error(projects.error);
                }

                const tbody = document.getElementById('projectsTableBody');

                if (projects.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted);">
                                No projects assigned yet
                            </td>
                        </tr>
                    `;
                    return;
                }

                tbody.innerHTML = projects.map(project => {
                    const daysLeft = calculateDaysLeft(project.end_date, project.status);
                    const sheetUrl = `/doc_prep.php?project_id=${project.id}&project_name=${encodeURIComponent(project.project_name)}`;

                    return `
                        <tr>
                            <td>
                                <div style="font-weight: 600;">${project.project_name}</div>
                                ${project.description ? `<div class="project-description">${project.description}</div>` : ''}
                            </td>
                            <td>${formatDate(project.start_date)}</td>
                            <td>${formatDate(project.end_date)}</td>
                            <td>
                                <span class="days-left ${daysLeft.class}">
                                    ${daysLeft.text}
                                </span>
                            </td>
                            <td>${getStatusDropdown(project.id, project.status)}</td>
                            <td>
                                <a href="${sheetUrl}" class="sheet-btn" style="text-decoration: none; display: inline-block;">
                                    <i class="fa-solid fa-file-alt"></i>
                                    Open Sheet
                                </a>
                            </td>
                        </tr>
                    `;
                }).join('');

                // Calculate and update stats
                updateStats(projects);

            } catch (error) {
                console.error('Error loading projects:', error);
                const tbody = document.getElementById('projectsTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--danger); padding: 2rem;">
                            <i class="fa-solid fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                            <strong>Failed to load projects</strong><br>
                            <span style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.5rem; display: block;">
                                ${error.message}
                 </span>
                        </td>
                    </tr>
                `;
                showToast('Failed to load projects: ' + error.message, 'error');
            }
        }

        // Update statistics
        function updateStats(projects) {
            const total = projects.length;

            // Count completed on time (completed before or on end_date)
            const completedOnTime = projects.filter(p => {
                if (p.status !== 'Completed' || !p.end_date) return false;
                const endDate = new Date(p.end_date);
                const completedDate = new Date(p.created_at); // Assuming created_at is when it was marked complete
                // For now, we'll count all completed projects as on time
                // In a real scenario, you'd have a completion_date field
                return true;
            }).length;

            // Count overdue (only for Pending and In Progress, past end_date)
            const overdue = projects.filter(p => {
                // Only count as overdue if status is Pending or In Progress
                if (p.status !== 'Pending' && p.status !== 'In Progress') return false;
                if (!p.end_date) return false;

                const endDate = new Date(p.end_date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                endDate.setHours(0, 0, 0, 0);
                return endDate < today;
            }).length;

            // Count pending
            const pending = projects.filter(p => p.status === 'Pending').length;

            // Update DOM
            document.getElementById('totalProjects').textContent = total;
            document.getElementById('completedOnTime').textContent = completedOnTime;
            document.getElementById('overdueProjects').textContent = overdue;
            document.getElementById('pendingProjects').textContent = pending;
        }

        // Open sheet for project - Navigate to doc_prep.php
        function openSheet(projectId, projectName) {
            console.log('openSheet function called');
            console.log('Project ID:', projectId);
            console.log('Project Name:', projectName);

            const url = `/doc_prep.php?project_id=${projectId}&project_name=${encodeURIComponent(projectName)}`;
            console.log('Navigating to:', url);

            // Redirect to doc_prep.php with project_id parameter
            window.location.href = url;
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Logout function
        async function logout() {
            try {
                await fetch('/api/logout.php');
                window.location.href = '/login.php';
            } catch (e) {
                window.location.href = '/login.php';
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadProjects();
        });
    </script>
</body>

</html>