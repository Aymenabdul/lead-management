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

// Get associate ID from query parameter
$associate_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$associate_name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Associate';

if (!$associate_id) {
    header('Location: /admin_dashboard.php');
    exit;
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $associate_name; ?> - Details | Lead Maintenance
    </title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid rgba(148, 163, 184, 0.1);
        }

        .tab {
            padding: 1rem 2rem;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
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
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
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

        .stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-mini {
            background: var(--card-bg);
            border-radius: 16px;
            border: var(--glass-border);
            padding: 1.5rem;
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-mini:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.2);
        }

        .stat-mini::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), transparent);
            border-radius: 0 16px 0 100%;
        }

        .stat-mini-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .stat-mini-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-mini-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Enhanced Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            background: var(--card-bg);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        thead {
            background: rgba(99, 102, 241, 0.1);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        thead th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary);
            border-bottom: 2px solid rgba(99, 102, 241, 0.2);
        }

        tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        tbody tr:hover {
            background: rgba(99, 102, 241, 0.05);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        tbody tr:nth-child(even) {
            background: rgba(148, 163, 184, 0.02);
        }

        tbody tr:nth-child(even):hover {
            background: rgba(99, 102, 241, 0.05);
        }

        tbody td {
            padding: 1rem;
            font-size: 0.95rem;
        }

        tbody td:first-child {
            font-weight: 500;
            color: var(--text-main);
        }

        /* Badge enhancements */
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
        }

        .badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .badge:hover {
            transform: scale(1.05);
        }

        /* Empty state styling */
        tbody tr td[colspan] {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-muted);
            font-size: 1rem;
        }

        tbody tr td[colspan]::before {
            content: '📋';
            display: block;
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Card title enhancement */
        .card h2 {
            position: relative;
            padding-left: 1rem;
        }

        .card h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            border-radius: 2px;
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
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                    <button onclick="history.back()" class="btn"
                        style="padding: 0.5rem 1rem; background: rgba(148, 163, 184, 0.1); border: 1px solid rgba(148, 163, 184, 0.3);">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </button>
                    <h1 class="page-title" style="margin: 0;">
                        <?php echo $associate_name; ?>
                    </h1>
                </div>
                <p style="color: var(--text-muted)">View all leads and conversions for this associate</p>
            </div>
        </header>

        <!-- Mini Stats -->
        <div class="stats-mini">
            <div class="stat-mini">
                <div class="stat-mini-value" id="miniTotalLeads">0</div>
                <div class="stat-mini-label">
                    <i class="fa-solid fa-users" style="color: #8b5cf6; margin-right: 0.5rem;"></i> Total Leads
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="miniConverted">0</div>
                <div class="stat-mini-label">
                    <i class="fa-solid fa-check-circle" style="color: var(--success); margin-right: 0.5rem;"></i>
                    Converted
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="miniPaid">0</div>
                <div class="stat-mini-label">
                    <i class="fa-solid fa-dollar-sign" style="color: #22c55e; margin-right: 0.5rem;"></i> Paid
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="miniPending">0</div>
                <div class="stat-mini-label">
                    <i class="fa-solid fa-clock" style="color: var(--warning); margin-right: 0.5rem;"></i> Pending
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('leads')">
                <i class="fa-solid fa-users"></i> All Leads
            </button>
            <button class="tab" onclick="switchTab('converted')">
                <i class="fa-solid fa-check-circle"></i> Converted Leads
            </button>
        </div>

        <!-- Leads Tab -->
        <div id="leadsTab" class="tab-content active">
            <div class="card">
                <h2 style="margin-bottom: 1.5rem;">All Leads</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Platform</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody id="leadsTableBody">
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted);">Loading leads...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Converted Tab -->
        <div id="convertedTab" class="tab-content">
            <div class="card">
                <h2 style="margin-bottom: 1.5rem;">Converted Leads</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Assignee</th>
                                <th>Service</th>
                                <th>Payment Status</th>
                                <th>Converted Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="convertedTableBody">
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted);">Loading converted
                                    leads...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="toast-container" id="toastContainer"></div>

    <script>
        const associateId = <?php echo $associate_id; ?>;

        // Store actual data
        let leadsData = [];
        let convertedData = [];

        document.addEventListener('DOMContentLoaded', () => {
            loadAssociateData();
        });

        async function loadAssociateData() {
            await Promise.all([
                loadLeads(),
                loadConverted()
            ]);
            updateMiniStats();
        }

        async function loadLeads() {
            try {
                const response = await fetch(`/api/associate_leads.php?user_id=${associateId}`);
                const leads = await response.json();

                // Store the data - ensure it's an array
                leadsData = Array.isArray(leads) ? leads : [];

                const tbody = document.getElementById('leadsTableBody');
                tbody.innerHTML = '';

                if (!Array.isArray(leads) || leads.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No leads found.</td></tr>';
                    return;
                }

                leads.forEach(lead => {
                    const tr = document.createElement('tr');
                    const statusBadge = getStatusBadge(lead.status);

                    tr.innerHTML = `
                        <td>
                            <span style="font-weight: 600; color: var(--text-main);">${lead.name}</span>
                        </td>
                        <td>${lead.phone}</td>
                        <td style="color: var(--text-muted);">${lead.email || '-'}</td>
                        <td><span class="badge badge-new" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">${lead.platform}</span></td>
                        <td style="font-size: 0.9em;">${lead.service}</td>
                        <td>${statusBadge}</td>
                        <td style="color: var(--text-muted); font-size: 0.9em;">${new Date(lead.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (error) {
                console.error('Error loading leads:', error);
                // showToast('Failed to load leads', 'error');
                leadsData = []; // Ensure it's an empty array on error
            }
        }

        async function loadConverted() {
            try {
                const response = await fetch(`/api/associate_converted.php?user_id=${associateId}`);
                const converted = await response.json();

                // Store the data - ensure it's an array
                convertedData = Array.isArray(converted) ? converted : [];

                const tbody = document.getElementById('convertedTableBody');
                tbody.innerHTML = '';

                if (!Array.isArray(converted) || converted.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No converted leads found.</td></tr>';
                    return;
                }

                converted.forEach(lead => {
                    const tr = document.createElement('tr');
                    const paymentBadge = getPaymentBadge(lead.payment_status);

                    tr.innerHTML = `
                        <td>
                            <span style="font-weight: 600; color: var(--text-main);">${lead.name}</span>
                        </td>
                        <td>${lead.phone}</td>
                        <td style="color: var(--text-muted);">${lead.email || '-'}</td>
                        <td>
                            ${lead.assignee_name
                            ? `<span class="badge badge-success" style="font-size: 0.8em;"><i class="fa-solid fa-user-check" style="margin-right: 4px;"></i>${lead.assignee_name}</span>`
                            : `<span class="badge badge-secondary" style="font-size: 0.8em; opacity: 0.7;">Unassigned</span>`
                        }
                        </td>
                        <td style="font-size: 0.9em;">${lead.service}</td>
                        <td>${paymentBadge}</td>
                        <td style="color: var(--text-muted); font-size: 0.9em;">${new Date(lead.converted_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="doc_prep.php?id=${lead.id}" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; text-decoration: none;" title="Open Sheet">
                                    <i class="fa-solid fa-table"></i>
                                </a>
                                <a href="word_prep.php?id=${lead.id}" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; text-decoration: none;" title="Open Word Doc">
                                    <i class="fa-solid fa-file-word"></i>
                                </a>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (error) {
                console.error('Error loading converted leads:', error);
                showToast('Failed to load converted leads', 'error');
                convertedData = []; // Ensure it's an empty array on error
            }
        }

        function updateMiniStats() {
            // Use actual data arrays instead of counting table rows
            const leadsCount = leadsData.length;
            const convertedCount = convertedData.length;

            // Count paid and pending from actual data
            let paidCount = 0;
            let pendingCount = 0;

            convertedData.forEach(lead => {
                if (lead.payment_status === 'Paid') paidCount++;
                if (lead.payment_status === 'Pending') pendingCount++;
            });

            document.getElementById('miniTotalLeads').textContent = leadsCount;
            document.getElementById('miniConverted').textContent = convertedCount;
            document.getElementById('miniPaid').textContent = paidCount;
            document.getElementById('miniPending').textContent = pendingCount;
        }

        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.closest('.tab').classList.add('active');

            // Update tab content
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            if (tab === 'leads') {
                document.getElementById('leadsTab').classList.add('active');
            } else {
                document.getElementById('convertedTab').classList.add('active');
            }
        }

        function getStatusBadge(status) {
            const badges = {
                'New': 'badge-new',
                'Contacted': 'badge-pending',
                'Converted': 'badge-paid',
                'Lost': 'badge-new'
            };
            return `<span class="badge ${badges[status] || 'badge-new'}">${status}</span>`;
        }

        function getPaymentBadge(status) {
            const badges = {
                'Paid': 'badge-paid',
                'Pending': 'badge-pending',
                'Refunded': 'badge-new'
            };
            return `<span class="badge ${badges[status] || 'badge-pending'}">${status}</span>`;
        }

        // Utility functions
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
            try {
                await fetch('/api/logout.php');
                localStorage.removeItem('user');
                window.location.href = '/login.php';
            } catch (error) {
                console.error('Logout error:', error);
                window.location.href = '/login.php';
            }
        }
    </script>
</body>

</html>