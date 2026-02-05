document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('leadsTableBody')) {
        loadLeads();
    }

    // Form Listeners
    document.getElementById('addLeadForm').addEventListener('submit', handleAddLead);
    document.getElementById('updateLeadForm').addEventListener('submit', handleUpdateLead);
});

// State
let leadsData = [];

// UI Helpers
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function togglePaymentField() {
    const status = document.getElementById('updateStatusSelect').value;
    const paymentGroup = document.getElementById('paymentStatusGroup');
    if (status === 'Converted') {
        paymentGroup.style.display = 'block';
    } else {
        paymentGroup.style.display = 'none';
    }
}

// Data Fetching
async function loadLeads() {
    try {
        const response = await fetch('api/leads.php');
        const data = await response.json();
        leadsData = data;
        renderLeads(data);
        updateStats(data);
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to load leads', 'error');
    }
}

function renderLeads(leads) {
    const tbody = document.getElementById('leadsTableBody');
    tbody.innerHTML = '';

    if (leads.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No leads found. Add one to get started!</td></tr>';
        return;
    }

    leads.forEach(lead => {
        if (lead.status === 'Converted') return;

        const tr = document.createElement('tr');

        let badgeClass = 'badge-new';
        if (lead.status === 'Contacted') badgeClass = 'badge-contacted';
        if (lead.status === 'Converted') badgeClass = 'badge-converted';

        tr.innerHTML = `
            <td style="font-weight: 500; color: var(--text-main);">${lead.name}</td>
            <td>
                <div style="font-size: 0.9em; color: var(--text-main);">${lead.phone}</div>
                <div style="font-size: 0.8em; color: var(--text-muted);">${lead.email || '-'}</div>
            </td>
            <td>${lead.platform}</td>
            <td>${lead.service || '-'}</td>
            <td><span class="badge ${badgeClass}">${lead.status}</span></td>
            <td>
                <button class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: rgba(99, 102, 241, 0.1); color: var(--primary); border: 1px solid var(--primary);" 
                        onclick="initiateUpdate(${lead.id})">
                    Update Status
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function updateStats(leads) {
    const total = leads.filter(l => l.status !== 'Converted').length;
    document.getElementById('totalLeadsCount').innerText = leads.length;
    document.getElementById('activeLeadsCount').innerText = total;
}

// Actions
async function handleAddLead(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('api/leads.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast('Lead added successfully', 'success');
            closeModal('addLeadModal');
            e.target.reset();
            loadLeads();
        } else {
            showToast(result.error || 'Failed to add lead', 'error');
        }
    } catch (error) {
        console.error(error);
        showToast('Error connecting to server', 'error');
    }
}

function initiateUpdate(id) {
    const lead = leadsData.find(l => l.id == id);
    if (!lead) return;

    document.getElementById('updateLeadId').value = id;
    document.getElementById('updateLeadName').innerText = lead.name;
    document.getElementById('updateStatusSelect').value = lead.status;
    togglePaymentField(); // Initialize visibility
    openModal('updateLeadModal');
}

async function handleUpdateLead(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('api/leads.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast('Lead updated successfully!', 'success');
            closeModal('updateLeadModal');
            loadLeads();
        } else {
            showToast(result.error || 'Update failed', 'error');
        }
    } catch (error) {
        showToast('Error updating lead', 'error');
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

// Logout function
async function logout() {
    try {
        await fetch('/api/logout.php');
        localStorage.removeItem('user');
        window.location.href = '/login.php';
    } catch (error) {
        console.error('Logout error:', error);
        // Force redirect anyway
        window.location.href = '/login.php';
    }
}

