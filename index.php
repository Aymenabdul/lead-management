<!DOCTYPE html>
<?php
// Require authentication
require_once __DIR__ . '/auth_middleware.php';
$user = AuthMiddleware::requireAuth();

// Redirect admins to admin dashboard
if ($user['role'] === 'admin') {
    header('Location: /admin_dashboard.php');
    exit;
}

// Redirect technical employees to their project dashboard
if ($user['role'] === 'technical') {
    header('Location: /technical_dashboard.php');
    exit;
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Maintenance | Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <!-- Sidebar -->
    <!-- Sidebar -->
    <?php $currentPage = 'leads';
    include __DIR__ . '/components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div>
                <h1 class="page-title">Lead Management</h1>
                <p style="color: var(--text-muted)">Track and manage your incoming leads.</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addLeadModal')">
                <i class="fa-solid fa-plus"></i> New Lead
            </button>
        </header>

        <!-- Stats Row (Optional Visual) -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card">
                <h3 style="color: var(--text-muted); font-size: 0.9rem;">Total Leads</h3>
                <div style="font-size: 2rem; font-weight: 700; margin-top: 0.5rem;" id="totalLeadsCount">0</div>
            </div>
            <div class="card">
                <h3 style="color: var(--text-muted); font-size: 0.9rem;">Active</h3>
                <div style="font-size: 2rem; font-weight: 700; margin-top: 0.5rem; color: var(--warning);"
                    id="activeLeadsCount">0</div>
            </div>
            <div class="card">
                <h3 style="color: var(--text-muted); font-size: 0.9rem;">Conversion Rate</h3>
                <div style="font-size: 2rem; font-weight: 700; margin-top: 0.5rem; color: var(--success);">--%</div>
            </div>
        </div>

        <!-- Leads Table -->
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact Info</th>
                            <th>Platform</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leadsTableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted);">Loading leads...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Lead Modal -->
    <div class="modal" id="addLeadModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('addLeadModal')">&times;</button>
            <h2 style="margin-bottom: 1.5rem;">Add New Lead</h2>
            <form id="addLeadForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" class="form-control" required placeholder="+1 234 567 890">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="john@example.com">
                </div>
                <div class="form-group">
                    <label>Platform</label>
                    <select name="platform" class="form-control">
                        <option value="Facebook">Facebook</option>
                        <option value="Instagram">Instagram</option>
                        <option value="Google Ads">Google Ads</option>
                        <option value="Referral">Referral</option>
                        <option value="Direct">Direct</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Service</label>
                    <select name="service" class="form-control">
                        <option value="Web Development">Web Development</option>
                        <option value="App Development">App Development</option>
                        <option value="Testing Service">Testing Service</option>
                        <option value="Digital Marketing">Digital Marketing</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="New">New</option>
                        <option value="Contacted">Contacted</option>
                        <option value="Interested">Interested</option>
                        <option value="Not Interested">Not Interested</option>
                        <option value="Converted">Converted</option>
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn"
                        style="background: transparent; border: 1px solid var(--text-muted); color: var(--text-muted);"
                        onclick="closeModal('addLeadModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Lead</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Lead Status Modal -->
    <div class="modal" id="updateLeadModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('updateLeadModal')">&times;</button>
            <h2 style="margin-bottom: 1.5rem;">Update Lead Status</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Updating: <strong id="updateLeadName"
                    style="color: var(--text-main);"></strong></p>
            <form id="updateLeadForm">
                <input type="hidden" name="id" id="updateLeadId">

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="updateStatusSelect" class="form-control" onchange="togglePaymentField()">
                        <option value="New">New</option>
                        <option value="Contacted">Contacted</option>
                        <option value="Interested">Interested</option>
                        <option value="Not Interested">Not Interested</option>
                        <option value="Converted">Converted</option>
                    </select>
                </div>

                <div class="form-group" id="paymentStatusGroup" style="display: none;">
                    <label>Payment Status</label>
                    <select name="payment_status" class="form-control">
                        <option value="Pending">Pending</option>
                        <option value="Upfront Paid">Upfront Paid</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn"
                        style="border: 1px solid var(--text-muted); color: var(--text-muted); background: transparent;"
                        onclick="closeModal('updateLeadModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="js/main.js"></script>
</body>

</html>