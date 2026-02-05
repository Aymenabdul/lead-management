<!DOCTYPE html>
<?php
// Require authentication and admin role
require_once __DIR__ . '/auth_middleware.php';
$user = AuthMiddleware::requireAuth();

// Check if user is admin
if ($user['role'] !== 'admin') {
    header('Location: /index.php'); // Redirect non-admins to regular dashboard
    exit;
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Lead Maintenance</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: var(--glass-border);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), transparent);
            border-radius: 0 16px 0 100%;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .tab {
            padding: 1rem 1.5rem;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
        }

        .tab:hover {
            color: var(--text-main);
        }

        .tab.active {
            color: var(--primary);
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(0, 0, 0, 0.05);
            /* Light theme fix */
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), #8b5cf6);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .metric-row {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .metric-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .metric-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .card h2 {
            margin-bottom: 0;
        }

        .table-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <!-- Sidebar -->
    <?php $currentPage = 'dashboard';
    include __DIR__ . '/components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div>
                <h1 class="page-title">Admin Dashboard</h1>
                <p style="color: var(--text-muted)">Overview of all associates and lead performance</p>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value" id="totalAssociates">0</div>
                <div class="stat-label">
                    <i class="fa-solid fa-user-group" style="color: var(--primary); margin-right: 0.5rem;"></i> Total
                    Associates
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-value" id="totalLeads">0</div>
                <div class="stat-label">
                    <i class="fa-solid fa-users" style="color: #8b5cf6; margin-right: 0.5rem;"></i> Total Leads
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-value" id="totalConverted">0</div>
                <div class="stat-label">
                    <i class="fa-solid fa-check-circle" style="color: var(--success); margin-right: 0.5rem;"></i>
                    Converted
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-value" id="totalPaid">0</div>
                <div class="stat-label">
                    <i class="fa-solid fa-dollar-sign" style="color: #22c55e; margin-right: 0.5rem;"></i> Paid
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-value" id="totalPending">0</div>
                <div class="stat-label">
                    <i class="fa-solid fa-clock" style="color: var(--warning); margin-right: 0.5rem;"></i> Pending
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-value" id="conversionRate">0%</div>
                <div class="stat-label">
                    <i class="fa-solid fa-percentage" style="color: #a855f7; margin-right: 0.5rem;"></i> Conversion Rate
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('marketing')">
                <i class="fa-solid fa-user-group"></i> Marketing Team
            </button>
            <button class="tab" onclick="switchTab('technical')">
                <i class="fa-solid fa-laptop-code"></i> Technical Team
            </button>
        </div>

        <!-- Marketing Performance Table -->
        <div id="marketingTab" class="tab-content active">
            <div class="card performance-table">
                <div class="table-header-row">
                    <h2>Marketing Associate Performance</h2>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Associate</th>
                                <th>Total Leads</th>
                                <th>Converted</th>
                                <th>Paid</th>
                                <th>Pending</th>
                                <th>Rate</th>
                                <th>Performance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="marketingTableBody">
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-muted);">Loading data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="pagination" id="marketingPagination"></div>
            </div>
        </div>

        <!-- Technical Performance Table -->
        <div id="technicalTab" class="tab-content">
            <div class="card performance-table">
                <div class="table-header-row">
                    <h2>Technical Associate Performance</h2>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Associate</th>
                                <th>Total Projects</th>
                                <th>Completed</th>
                                <th>In Progress</th>
                                <th>Completion Rate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="technicalTableBody">
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted);">Loading data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="pagination" id="technicalPagination"></div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/components/security_modal.php'; ?>

    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Security Modal Functions are now included via the component

        // State
        let marketingData = [];
        let technicalData = [];
        let marketingPage = 1;
        let technicalPage = 1;
        const pageSize = 5;

        document.addEventListener('DOMContentLoaded', () => {
            loadDashboardData();
        });

        async function loadDashboardData() {
            try {
                const response = await fetch('/api/admin_stats.php');
                const data = await response.json();

                if (data.success) {
                    updateStats(data.stats);

                    marketingData = data.marketing_associates;
                    technicalData = data.technical_associates;

                    renderMarketingTable();
                    renderTechnicalTable();
                } else {
                    showToast('Failed to load dashboard data', 'error');
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
                showToast('Error loading dashboard data', 'error');
            }
        }

        function updateStats(stats) {
            document.getElementById('totalAssociates').textContent = stats.total_associates;
            document.getElementById('totalLeads').textContent = stats.total_leads;
            document.getElementById('totalConverted').textContent = stats.total_converted;
            document.getElementById('totalPaid').textContent = stats.total_paid;
            document.getElementById('totalPending').textContent = stats.total_pending;
            document.getElementById('conversionRate').textContent = stats.conversion_rate + '%';
        }

        function renderMarketingTable() {
            const tbody = document.getElementById('marketingTableBody');
            tbody.innerHTML = '';

            if (marketingData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No marketing associates found.</td></tr>';
                renderPagination([], 1, 'marketingPagination', renderMarketingTable);
                return;
            }

            // Pagination Logic
            const start = (marketingPage - 1) * pageSize;
            const end = start + pageSize;
            const pageData = marketingData.slice(start, end);

            pageData.forEach(associate => {
                const tr = document.createElement('tr');
                const conversionRate = associate.total_leads > 0
                    ? Math.round((associate.converted / associate.total_leads) * 100)
                    : 0;

                tr.innerHTML = `
                    <td>
                        <div>
                            <div style="font-weight: 600; color: var(--text-main); margin-bottom: 0.15rem;">${associate.full_name || associate.username}</div>
                            <div style="font-size: 0.8em; color: var(--text-muted);">${associate.email}</div>
                        </div>
                    </td>
                    <td style="font-weight: 600; color: #8b5cf6;">${associate.total_leads}</td>
                    <td style="font-weight: 600; color: var(--success);">${associate.converted}</td>
                    <td style="font-weight: 600; color: #22c55e;">${associate.paid}</td>
                    <td style="font-weight: 600; color: var(--warning);">${associate.pending}</td>
                    <td>
                        <span class="badge ${conversionRate >= 50 ? 'badge-paid' : conversionRate >= 25 ? 'badge-pending' : 'badge-new'}">
                            ${conversionRate}%
                        </span>
                    </td>
                    <td style="min-width: 200px;">
                        <div class="metric-row">
                             <span style="font-size:0.8em; color:var(--text-muted)">Conv. Rate</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${conversionRate}%"></div>
                        </div>
                    </td>
                    <td>
                        <button 
                            class="btn btn-primary" 
                            style="padding: 0.5rem 1rem; font-size: 0.85rem;"
                            onclick="window.location.href='/associate_details.php?id=${associate.id}&name=${encodeURIComponent(associate.full_name || associate.username)}'">
                            <i class="fa-solid fa-eye"></i> Details
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            renderPagination(marketingData, marketingPage, 'marketingPagination', (p) => {
                marketingPage = p;
                renderMarketingTable();
            });
        }

        function renderTechnicalTable() {
            const tbody = document.getElementById('technicalTableBody');
            tbody.innerHTML = '';

            if (technicalData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No technical associates found.</td></tr>';
                renderPagination([], 1, 'technicalPagination', renderTechnicalTable);
                return;
            }

            const start = (technicalPage - 1) * pageSize;
            const end = start + pageSize;
            const pageData = technicalData.slice(start, end);

            pageData.forEach(assoc => {
                const tr = document.createElement('tr');
                const total = parseInt(assoc.total_projects) || 0;
                const completed = parseInt(assoc.completed) || 0;
                const rate = total > 0 ? Math.round((completed / total) * 100) : 0;

                tr.innerHTML = `
                    <td>
                         <div>
                            <div style="font-weight: 600; color: var(--text-main); margin-bottom: 0.15rem;">${assoc.full_name || assoc.username}</div>
                            <div style="font-size: 0.8em; color: var(--text-muted);">${assoc.email}</div>
                        </div>
                    </td>
                    <td style="font-weight: 600; color: var(--primary);">${assoc.total_projects}</td>
                    <td style="color: var(--success); font-weight:500;">${assoc.completed}</td>
                    <td style="color: #6366f1; font-weight:500;">${assoc.in_progress}</td>
                    <td>
                         <div style="display:flex; align-items:center; gap:0.5rem;">
                             <div class="progress-bar" style="width: 100px; margin-top:0;">
                                <div class="progress-fill" style="width: ${rate}%; background:var(--success);"></div>
                            </div>
                            <span style="font-size:0.85rem; color:var(--text-muted);">${rate}%</span>
                         </div>
                    </td>
                    <td>
                        <button 
                            class="btn btn-primary" 
                            style="padding: 0.5rem 1rem; font-size: 0.85rem;"
                             onclick="window.location.href='/technical_details.php?id=${assoc.id}&name=${encodeURIComponent(assoc.full_name || assoc.username)}'">
                            <i class="fa-solid fa-list-check"></i> Details
                        </button>
                    </td>
                 `;
                tbody.appendChild(tr);
            });

            renderPagination(technicalData, technicalPage, 'technicalPagination', (p) => {
                technicalPage = p;
                renderTechnicalTable();
            });
        }

        function renderPagination(data, currentPage, containerId, onPageChange) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';

            const totalPages = Math.ceil(data.length / pageSize);
            if (totalPages <= 1) return;

            // Prev
            const prevBtn = document.createElement('button');
            prevBtn.className = 'page-btn';
            prevBtn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => onPageChange(currentPage - 1);
            container.appendChild(prevBtn);

            // Numbers
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
                btn.innerText = i;
                btn.onclick = () => onPageChange(i);
                container.appendChild(btn);
            }

            // Next
            const nextBtn = document.createElement('button');
            nextBtn.className = 'page-btn';
            nextBtn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
            nextBtn.disabled = currentPage === totalPages;
            nextBtn.onclick = () => onPageChange(currentPage + 1);
            container.appendChild(nextBtn);
        }

        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            // Find clicked tab logic by text/onclick - simplified:
            // Actually passed 'marketing' or 'technical' string
            if (tab === 'marketing') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('marketingTab').classList.add('active');
                document.getElementById('technicalTab').classList.remove('active');
            } else {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('technicalTab').classList.add('active');
                document.getElementById('marketingTab').classList.remove('active');
            }
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.style.borderLeft = type === 'success' ? '4px solid var(--success)' : '4px solid var(--danger)';
            toast.innerText = message;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        async function logout() {
            try { await fetch('/api/logout.php'); window.location.href = '/login.php'; }
            catch (e) { window.location.href = '/login.php'; }
        }
    </script>
</body>

</html>