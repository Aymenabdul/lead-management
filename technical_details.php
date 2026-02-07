<!DOCTYPE html>
<?php
require_once __DIR__ . '/auth_middleware.php';
$user = AuthMiddleware::requireAuth();

if ($user['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

$associate_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$associate_name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Technical Associate';

if (!$associate_id) {
    header('Location: /technical_associates.php');
    exit;
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $associate_name; ?> - Projects | Lead Maintenance</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- Sidebar -->
    <?php $currentPage = 'technical_associates';
    include __DIR__ . '/components/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <div>
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                    <button onclick="window.location.href='/technical_associates.php'" class="btn"
                        style="padding: 0.5rem 1rem; background: rgba(148, 163, 184, 0.1); border: 1px solid rgba(148, 163, 184, 0.3);">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </button>
                    <h1 class="page-title" style="margin: 0;"><?php echo $associate_name; ?></h1>
                </div>
                <p style="color: var(--text-muted)">Project assignment and tracking</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('assignProjectModal')">
                <i class="fa-solid fa-plus"></i> Assign New Project
            </button>
        </header>

        <div class="card">
            <h2 style="margin-bottom: 1.5rem;">Assigned Projects</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Project Turn</th>
                            <th>Project Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days Left</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="projectsTableBody">
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted);">Loading projects...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Assign Project Modal -->
    <div class="modal" id="assignProjectModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('assignProjectModal')">&times;</button>
            <h2 style="margin-bottom: 1.5rem;">Assign Project</h2>
            <form id="assignProjectForm">
                <input type="hidden" name="user_id" value="<?php echo $associate_id; ?>">
                <div class="form-group">
                    <label>Project Name (Converted Lead)</label>
                    <select name="project_name" id="assignProjectSelect" class="form-control" required>
                        <option value="">Loading available leads...</option>
                    </select>
                </div>
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div>
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="In Progress">In Progress</option>
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                        <option value="On Hold">On Hold</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"
                        placeholder="Project goals and details..."></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn" style="border: 1px solid var(--border); color: var(--text-muted);"
                        onclick="closeModal('assignProjectModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Project</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Project Modal -->
    <div class="modal" id="editProjectModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('editProjectModal')">&times;</button>
            <h2 style="margin-bottom: 1.5rem;">Edit Project</h2>
            <form id="editProjectForm">
                <input type="hidden" name="project_id" id="editProjectId">
                <div class="form-group">
                    <label>Project Name</label>
                    <select name="project_name" id="editProjectName" class="form-control" required>
                    </select>
                </div>
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="editStartDate" class="form-control">
                    </div>
                    <div>
                        <label>End Date</label>
                        <input type="date" name="end_date" id="editEndDate" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="editStatus" class="form-control">
                        <option value="In Progress">In Progress</option>
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                        <option value="On Hold">On Hold</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn" style="border: 1px solid var(--border); color: var(--text-muted);"
                        onclick="closeModal('editProjectModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Project</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Project Modal -->
    <div class="modal" id="viewProjectModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('viewProjectModal')">&times;</button>
            <h2 style="margin-bottom: 1rem;" id="viewTitle"></h2>

            <div style="margin-bottom: 1.5rem;">
                <span class="badge" id="viewStatus"></span>
            </div>

            <div class="form-group">
                <label>Timeline</label>
                <div style="font-weight: 500; font-size: 1.1rem; color: var(--text-main);">
                    <span id="viewStart"></span> <i class="fa-solid fa-arrow-right"
                        style="font-size:0.8em; color:var(--text-muted); margin:0 0.5rem;"></i> <span
                        id="viewEnd"></span>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <div id="viewDesc"
                    style="background: var(--bg-color); padding: 1rem; border-radius: 8px; color: var(--text-main); line-height: 1.6; white-space: pre-wrap;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-primary" onclick="closeModal('viewProjectModal')">Close</button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script>
        const associateId = <?php echo $associate_id; ?>;
        let projectsData = [];

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
            loadProjects();
            loadAvailableLeads();
            document.getElementById('assignProjectForm').addEventListener('submit', handleAssignProject);
            document.getElementById('editProjectForm').addEventListener('submit', handleEditProject);
        });

        async function loadProjects() {
            try {
                const res = await fetch(`/api/technical_associates.php?user_id=${associateId}`);
                const data = await res.json();
                projectsData = data;

                const tbody = document.getElementById('projectsTableBody');
                tbody.innerHTML = '';

                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No projects assigned yet.</td></tr>';
                    return;
                }

                data.forEach((proj, index) => {
                    const tr = document.createElement('tr');

                    const statusColor = proj.status === 'Completed' ? 'var(--success)' :
                        (proj.status === 'In Progress' ? 'var(--primary)' : 'var(--warning)');
                    const bg = proj.status === 'Completed' ? '#dcfce7' :
                        (proj.status === 'In Progress' ? '#e0e7ff' : '#fef3c7');

                    const statusBadge = `<span class="badge" style="background: ${bg}; color: ${statusColor}; border: 1px solid ${statusColor}30">${proj.status}</span>`;

                    const start = proj.start_date ? new Date(proj.start_date).toLocaleDateString() : '-';
                    const end = proj.end_date ? new Date(proj.end_date).toLocaleDateString() : 'Ongoing';

                    // Days Left Calculation
                    let daysLeftDisplay = '-';
                    if (proj.status === 'Completed') {
                        daysLeftDisplay = '<span style="color: var(--success); font-weight: 500;">Done</span>';
                    } else if (proj.end_date) {
                        const now = new Date();
                        now.setHours(0, 0, 0, 0);
                        const endDate = new Date(proj.end_date);
                        endDate.setHours(0, 0, 0, 0);

                        const diffTime = endDate - now;
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                        if (diffDays < 0) {
                            daysLeftDisplay = `<span style="color: #ef4444; font-weight: 600;">Overdue by ${Math.abs(diffDays)} days</span>`;
                        } else if (diffDays === 0) {
                            daysLeftDisplay = `<span style="color: #f59e0b; font-weight: 600;">Due Today</span>`;
                        } else {
                            const color = diffDays <= 3 ? '#f59e0b' : 'var(--success)';
                            daysLeftDisplay = `<span style="color: ${color}; font-weight: 600;">${diffDays} days</span>`;
                        }
                    } else {
                        daysLeftDisplay = '<span style="color: var(--text-muted);">No Deadline</span>';
                    }

                    tr.innerHTML = `
                        <td style="color: var(--text-muted); font-weight: 500;">#${projectsData.length - index}</td>
                        <td style="font-weight: 600; color: var(--text-main);">${proj.project_name}</td>
                        <td style="color: var(--text-muted);">${start}</td>
                        <td style="color: var(--text-muted);">${end}</td>
                        <td>${daysLeftDisplay}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-primary" onclick="viewProject(${proj.id})" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; border: 1px solid var(--primary); color: var(--primary);" onclick="openEditProject(${proj.id})" title="Edit Project">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; border: 1px solid var(--success); color: var(--success);" 
                                    onclick="window.location.href='/doc_prep.php?project_id=${proj.id}&project_name=${encodeURIComponent(proj.project_name)}'" 
                                    title="Open Sheet">
                                    <i class="fa-solid fa-table"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (err) {
                console.error(err);
                showToast('Failed to load projects', 'error');
            }
        }

        async function loadAvailableLeads() {
            try {
                const res = await fetch('/api/technical_associates.php?mode=available_leads');
                if (!res.ok) {
                    const errText = await res.text();
                    throw new Error(`Server Error: ${res.status} ${res.statusText} - ${errText}`);
                }
                const leads = await res.json();

                if (leads.error) {
                    throw new Error(leads.error);
                }

                const select = document.getElementById('assignProjectSelect');
                select.innerHTML = '<option value="">Select a Converted Lead...</option>';

                if (leads.length === 0) {
                    const option = document.createElement('option');
                    option.disabled = true;
                    option.text = 'No unassigned converted leads found';
                    select.appendChild(option);
                    return;
                }

                leads.forEach(lead => {
                    const option = document.createElement('option');
                    option.value = lead.name;
                    option.textContent = `${lead.name} ${lead.service ? `(${lead.service})` : ''}`;
                    select.appendChild(option);
                });
            } catch (err) {
                console.error('Error loading available leads:', err);
                const select = document.getElementById('assignProjectSelect');
                select.innerHTML = `<option value="">Error: ${err.message}</option>`;
            }
        }

        async function handleAssignProject(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries()); // contains user_id

            try {
                const res = await fetch('/api/technical_associates.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    showToast('Project assigned successfully');
                    closeModal('assignProjectModal');
                    e.target.reset();
                    document.querySelector('input[name="user_id"]').value = associateId;
                    loadProjects();
                    loadAvailableLeads();
                } else {
                    showToast(result.error || 'Failed to assign', 'error');
                }
            } catch (err) {
                showToast('Error assigning project', 'error');
            }
        }

        async function openEditProject(id) {
            const proj = projectsData.find(p => p.id == id);
            if (!proj) return;

            document.getElementById('editProjectId').value = proj.id;

            const select = document.getElementById('editProjectName');
            // Initial state with current project
            select.innerHTML = `<option value="${proj.project_name}" selected>${proj.project_name} (Current)</option><option disabled>Loading other options...</option>`;

            try {
                const res = await fetch('/api/technical_associates.php?mode=available_leads');
                if (res.ok) {
                    const leads = await res.json();

                    // Rebuild options with current project + available leads
                    let html = `<option value="${proj.project_name}" selected>${proj.project_name} (Current)</option>`;

                    if (!leads.error && Array.isArray(leads)) {
                        leads.forEach(lead => {
                            html += `<option value="${lead.name}">${lead.name} ${lead.service ? `(${lead.service})` : ''}</option>`;
                        });
                    }
                    select.innerHTML = html;
                }
            } catch (e) {
                console.error("Error fetching leads for edit:", e);
                // Fallback: just keep the current project as the only option if fetch fails
                select.innerHTML = `<option value="${proj.project_name}" selected>${proj.project_name}</option>`;
            }

            document.getElementById('editStartDate').value = proj.start_date || '';
            document.getElementById('editEndDate').value = proj.end_date || '';
            document.getElementById('editStatus').value = proj.status;
            document.getElementById('editDescription').value = proj.description || '';

            openModal('editProjectModal');
        }

        async function handleEditProject(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('/api/technical_associates.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    showToast('Project updated successfully');
                    closeModal('editProjectModal');
                    loadProjects();
                } else {
                    showToast(result.error || 'Failed to update', 'error');
                }
            } catch (err) {
                showToast('Error updating project', 'error');
            }
        }

        function viewProject(id) {
            const proj = projectsData.find(p => p.id == id);
            if (!proj) return;

            document.getElementById('viewTitle').textContent = proj.project_name;
            const statusEl = document.getElementById('viewStatus');
            statusEl.textContent = proj.status;

            const statusColor = proj.status === 'Completed' ? 'var(--success)' : (proj.status === 'In Progress' ? 'var(--primary)' : 'var(--warning)');
            statusEl.style.color = statusColor;
            statusEl.style.background = proj.status === 'Completed' ? '#dcfce7' : (proj.status === 'In Progress' ? '#e0e7ff' : '#fef3c7');
            statusEl.style.border = `1px solid ${statusColor}30`;

            document.getElementById('viewStart').textContent = proj.start_date ? new Date(proj.start_date).toLocaleDateString() : 'N/A';
            document.getElementById('viewEnd').textContent = proj.end_date ? new Date(proj.end_date).toLocaleDateString() : 'Ongoing';
            document.getElementById('viewDesc').textContent = proj.description || 'No description provided.';

            openModal('viewProjectModal');
        }
    </script>
</body>

</html>