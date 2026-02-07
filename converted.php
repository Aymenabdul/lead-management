<!DOCTYPE html>
<?php
// Require authentication
require_once __DIR__ . '/auth_middleware.php';
$user = AuthMiddleware::requireAuth();
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Maintenance | Converted</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <!-- Sidebar -->
    <!-- Sidebar -->
    <?php $currentPage = 'converted';
    include __DIR__ . '/components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div>
                <h1 class="page-title">Converted Leads</h1>
                <p style="color: var(--text-muted)">Manage your successful conversions and payments.</p>
            </div>
            <button class="btn"
                style="background: var(--card-bg); color: var(--text-muted); border: 1px solid var(--border);">
                <i class="fa-solid fa-download"></i> Export CSV
            </button>
        </header>

        <!-- Converted Table -->
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact Info</th>
                            <th>Assignee</th>
                            <th>Service</th>
                            <th>Payment Status</th>
                            <th>Converted Date</th>
                            <th>Doc Prep</th>
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
    </main>

    <!-- Update Payment Modal -->
    <div class="modal" id="updatePaymentModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('updatePaymentModal')">&times;</button>
            <h2 style="margin-bottom: 1.5rem;">Update Payment Status</h2>
            <form id="updatePaymentForm">
                <input type="hidden" name="id" id="updatePaymentId">
                <div class="form-group">
                    <label>Payment Status</label>
                    <select name="payment_status" id="paymentStatusSelect" class="form-control">
                        <option value="Pending">Pending</option>
                        <option value="Upfront Paid">Upfront Paid</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn"
                        style="border: 1px solid var(--text-muted); color: var(--text-muted); background: transparent;"
                        onclick="closeModal('updatePaymentModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadConvertedLeads();
            document.getElementById('updatePaymentForm').addEventListener('submit', handleUpdatePayment);
        });

        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        async function loadConvertedLeads() {
            try {
                const response = await fetch('api/converted.php');
                const data = await response.json();

                const tbody = document.getElementById('convertedTableBody');
                tbody.innerHTML = '';

                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No converted leads found.</td></tr>';
                    return;
                }

                data.forEach(lead => {
                    const tr = document.createElement('tr');
                    // Simple logic to determine badge color based on status text
                    let badgeClass = 'badge-pending';
                    if (lead.payment_status === 'Paid' || lead.payment_status === 'Completed') badgeClass = 'badge-paid';
                    if (lead.payment_status === 'Upfront Paid') badgeClass = 'badge-upfront';

                    tr.innerHTML = `
                        <td style="font-weight: 500; color: var(--text-main);">${lead.name}</td>
                        <td>
                            <div style="font-size: 0.9em; color: var(--text-main);">${lead.phone}</div>
                            <div style="font-size: 0.8em; color: var(--text-muted);">${lead.email || '-'}</div>
                        </td>
                        <td>
                            ${lead.assignee_name
                            ? `<span class="badge badge-success" style="font-size: 0.8em;"><i class="fa-solid fa-user-check" style="margin-right: 4px;"></i>${lead.assignee_name}</span>`
                            : `<span class="badge badge-secondary" style="font-size: 0.8em; opacity: 0.7;">Unassigned</span>`
                        }
                        </td>
                        <td>${lead.service || '-'}</td>
                        <td>
                            <span class="badge ${badgeClass}" 
                                  style="cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;" 
                                  onclick="initiateUpdate(${lead.id}, '${lead.payment_status}')"
                                  title="Click to update status">
                                ${lead.payment_status} <i class="fa-solid fa-pen" style="font-size: 0.7em; opacity: 0.7;"></i>
                            </span>
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.9em;">${new Date(lead.converted_at).toLocaleDateString()}</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="doc_prep.php?id=${lead.id}" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; text-decoration: none;">
                                    <i class="fa-solid fa-table"></i> Sheet
                                </a>
                                <a href="word_prep.php?id=${lead.id}" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; text-decoration: none;">
                                    <i class="fa-solid fa-file-word"></i> Word
                                </a>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (error) {
                console.error('Error loading converted leads:', error);
            }
        }

        function initiateUpdate(id, currentStatus) {
            document.getElementById('updatePaymentId').value = id;
            document.getElementById('paymentStatusSelect').value = currentStatus;
            openModal('updatePaymentModal');
        }

        async function handleUpdatePayment(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('api/converted.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    // Simple alert or toast - copying toast logic is verbose, let's just reload
                    closeModal('updatePaymentModal');
                    loadConvertedLeads();
                } else {
                    alert(result.error || 'Update failed');
                }
            } catch (error) {
                console.error('Error updating payment:', error);
            }
        }

        // Logout function
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