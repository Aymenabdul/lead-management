<!DOCTYPE html>
<?php
require_once __DIR__ . '/auth_middleware.php';
$user = AuthMiddleware::requireAuth();

require_once __DIR__ . '/config.php';

// Determine if this is a project or converted lead
$isProject = isset($_GET['project_id']);
$lead = null;
$project = null;
$pageTitle = '';

if ($isProject) {
    // Handle project sheet
    $projectId = (int) $_GET['project_id'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();

    if (!$project) {
        die("Project not found");
    }

    // Check access - technical users can only access their own projects
    if ($user['role'] === 'technical' && $project['user_id'] != $user['user_id']) {
        die("Unauthorized - You can only access your own projects");
    }

    $pageTitle = $project['project_name'];
} else {
    // Handle converted lead sheet
    if (!isset($_GET['id'])) {
        header('Location: converted.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM converted_leads WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $lead = $stmt->fetch();

    if (!$lead) {
        die("Lead not found");
    }

    // Check access
    if ($user['role'] !== 'admin' && $lead['user_id'] != $user['user_id']) {
        die("Unauthorized");
    }

    $pageTitle = $lead['name'];
}

// Fetch technical team members
$techStmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'technical' ORDER BY username");
$techStmt->execute();
$technicalTeam = $techStmt->fetchAll();
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Doc Prep - <?php echo htmlspecialchars($pageTitle); ?>
    </title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Quill Theme -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Serif:ital,opsz,wght@0,8..144,100..900;1,8..144,100..900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .sheet-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 0;
            overflow: hidden;
            margin-top: 1.5rem;
            border: 1px solid #e5e7eb;
            margin-bottom: 1rem;
        }

        /* Editor Container Styles */
        #editor-container {
            height: 600px;
            background: white;
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
        }

        /* Hide doc furniture on screen */
        .doc-header,
        .doc-footer {
            display: none;
        }

        .sheet-wrapper {
            padding: 1rem 0;
            background: #f3f4f6;
        }

        /* Quill Toolbar */
        .ql-toolbar.ql-snow {
            border: none;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            padding: 12px 16px;
        }

        .ql-container.ql-snow {
            border: none;
            font-family: 'Outfit', sans-serif;
        }

        .ql-editor {
            padding: 32px 48px;
            min-height: 100%;
            font-size: 15px;
            line-height: 1.6;
            color: #1f2937;
        }

        /* Print Styles - Transformation to A4 */
        @media print {
            @page {
                margin: 0;
            }

            body {
                background: white;
                margin: 0;
                padding: 0;
                display: block !important;
            }

            /* Hide UI elements (added .header) */
            .sidebar,
            .top-header,
            .header,
            .sheet-tabs,
            .ql-toolbar,
            .btn,
            #saveStatus,
            .swal2-container,
            .modal-overlay {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: auto !important;
            }

            .sheet-wrapper {
                padding: 0;
                margin: 0;
                overflow: visible !important;
                background: white !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
            }

            .page-container {
                width: 100%;
                max-width: 210mm;
                /* A4 */
                margin: 0 auto;
                padding: 20mm;
                box-shadow: none;
                border: none;
                display: flex;
                flex-direction: column;
                min-height: 297mm;
                /* Full A4 Height */
            }

            /* Show Header/Footer explicitly in print */
            .doc-header {
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #10b981;
                padding-bottom: 10px;
            }

            .doc-logo img {
                height: 40px;
                /* Adjust logo size */
                width: auto;
            }

            .doc-banner {
                font-size: 24px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 2px;
                font-family: 'Roboto Serif', serif;
            }

            .doc-banner span {
                background: linear-gradient(135deg, #10b981 0%, #000000 100%) !important;
                background-repeat: no-repeat !important;
                background-size: 98% 100% !important;
                -webkit-background-clip: text !important;
                background-clip: text !important;
                -webkit-text-fill-color: transparent !important;
                color: #10b981 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                display: inline-block;
                padding-bottom: 2px;
                padding-right: 5px;
                border: 1px solid transparent;
            }

            .doc-footer {
                display: block !important;
                margin-top: auto;
                /* Push to bottom */
                padding-top: 50px;
                page-break-inside: avoid;
            }

            .signatures {
                display: flex;
                justify-content: space-between;
                margin-bottom: 20px;
                gap: 20px;
            }

            .signature-box {
                flex: 1;
                text-align: center;
            }

            .signature-line {
                border-bottom: 1px solid #000;
                height: 40px;
                margin-bottom: 5px;
            }

            .signature-label {
                font-size: 10px;
                text-transform: uppercase;
                color: #666;
            }

            .sheet-number {
                text-align: center;
                font-size: 10px;
                color: #999;
                margin-top: 20px;
            }

            #editor-container {
                border: none;
                height: auto !important;
                min-height: auto !important;
                flex: 1;
                /* Grow to fill space */
            }

            .ql-editor {
                padding: 0;
                overflow: visible;
            }

            /* Bulk Printing Support */
            body.printing-mode>*:not(#bulk-print-container) {
                display: none !important;
            }

            body.printing-mode #bulk-print-container {
                display: block !important;
            }

            .print-editor-body {
                flex: 1;
                border: none;
            }
        }

        /* Sheet tabs styling - Modern Pill Design */
        .sheet-tabs {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
        }

        .tabs-container {
            display: flex;
            gap: 8px;
            flex: 1;
            overflow-x: auto;
            padding: 4px;
            background: #f8fafc;
            border-radius: 10px;
            padding: 6px;
        }

        .tabs-container::-webkit-scrollbar {
            height: 4px;
        }

        .tabs-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .tabs-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }

        .tabs-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .sheet-tab {
            padding: 10px 20px;
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sheet-tab:hover {
            background: #e0f2fe;
            color: #0369a1;
        }

        .sheet-tab.active {
            background: linear-gradient(135deg, #10b981 0%, #1f2937 100%);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .sheet-tab-name {
            display: inline-block;
            min-width: 50px;
        }

        .sheet-tab-delete {
            margin-left: 4px;
            color: #94a3b8;
            cursor: pointer;
            font-size: 18px;
            font-weight: normal;
            transition: all 0.2s ease;
            opacity: 0.6;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
        }

        .sheet-tab-delete:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            opacity: 1;
        }

        .sheet-tab.active .sheet-tab-delete {
            color: white;
            opacity: 0.8;
        }

        .sheet-tab.active .sheet-tab-delete:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            opacity: 1;
        }

        .add-sheet-btn {
            padding: 10px 18px;
            background: linear-gradient(135deg, #10b981 0%, #1f2937 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .add-sheet-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #111827 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.5);
        }

        .add-sheet-btn:active {
            transform: translateY(0);
        }

        /* Assign Dropdown Styles */
        .assign-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .assign-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .assign-dropdown {
            padding: 10px 36px 10px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Outfit', sans-serif;
            font-weight: 500;
            color: #1f2937;
            background: white;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 200px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Cpath fill='%2364748b' d='M7 10L2 5h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .assign-dropdown:hover {
            border-color: #10b981;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1);
            transform: translateY(-1px);
        }

        .assign-dropdown:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
            transform: translateY(-1px);
        }

        .assign-dropdown:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f9fafb;
        }

        /* Custom Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            animation: fadeIn 0.2s ease;
        }

        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 0;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .modal-header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin: 0;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-message {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .modal-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Outfit', sans-serif;
            transition: all 0.2s ease;
        }

        .modal-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .modal-footer {
            padding: 16px 24px 24px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            font-family: 'Outfit', sans-serif;
        }

        .modal-btn-cancel {
            background: #f3f4f6;
            color: #6b7280;
        }

        .modal-btn-cancel:hover {
            background: #e5e7eb;
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }

        .modal-btn-confirm:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
        }

        .modal-btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }

        .modal-btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.4);
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
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <?php
    $currentPage = $isProject ? 'projects' : 'converted';
    include __DIR__ . '/components/sidebar.php';
    ?>

    <main class="main-content">
        <header class="header">
            <div>
                <?php if ($isProject): ?>
                    <a href="technical_dashboard.php"
                        style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; margin-bottom: 0.5rem; display: inline-block;">
                        <i class="fa-solid fa-arrow-left"></i> Back to My Projects
                    </a>
                <?php else: ?>
                    <a href="converted.php"
                        style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; margin-bottom: 0.5rem; display: inline-block;">
                        <i class="fa-solid fa-arrow-left"></i> Back to Converted
                    </a>
                <?php endif; ?>
                <h1 class="page-title">Doc Prep: <span style="font-weight: 400;">
                        <?php echo htmlspecialchars($pageTitle); ?>
                    </span></h1>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <span id="saveStatus" style="display: none;"></span>
                <button class="btn" onclick="downloadPDF()"
                    style="background: white; border: 1px solid #e5e7eb; color: #1f2937;">
                    <i class="fa-solid fa-download"></i> Download PDF
                </button>
                <button class="btn btn-primary" onclick="saveSheet()">
                    <i class="fa-solid fa-save"></i> Save Document
                </button>
            </div>
        </header>

        <div class="sheet-wrapper">
            <div class="page-container" id="printableArea">
                <header class="doc-header">
                    <div class="doc-logo">
                        <img src="assets/images/turtle_logo.png" alt="Turtledot Logo">
                    </div>
                    <div class="doc-banner"><span>TURTLEDOT</span></div>
                </header>

                <div id="editor-container"></div>

                <footer class="doc-footer">
                    <div class="signatures">
                        <div class="signature-box">
                            <div class="signature-line"></div>
                            <div class="signature-label">Founder</div>
                        </div>
                        <div class="signature-box">
                            <div class="signature-line"></div>
                            <div class="signature-label">Co-Founder</div>
                        </div>
                        <div class="signature-box">
                            <div class="signature-line"></div>
                            <div class="signature-label">Client</div>
                        </div>
                    </div>
                    <div class="sheet-number" id="currentSheetNum">Sheet 1</div>
                </footer>
            </div>

            <div class="sheet-tabs">
                <div class="tabs-container" id="sheetTabs"></div>
                <button class="add-sheet-btn" onclick="addNewSheet()" title="Add Document">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </div>
    </main>

    <!-- Custom Modal -->
    <div class="modal-overlay" id="customModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Confirm</h3>
            </div>
            <div class="modal-body">
                <p class="modal-message" id="modalMessage"></p>
                <input type="text" class="modal-input" id="modalInput" style="display: none;">
            </div>
            <div class="modal-footer" id="modalFooter">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal(false)">Cancel</button>
                <button class="modal-btn modal-btn-confirm" id="modalConfirmBtn"
                    onclick="closeModal(true)">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        const LEAD_ID = <?php echo $lead['id']; ?>;
        const CURRENT_USER_ID = <?php echo $user['user_id']; ?>;
        const USER_ROLE = '<?php echo $user['role']; ?>';
        const LEAD_CREATOR_ID = <?php echo $lead['user_id']; ?>;
        let editorInstance = null;
        let sheets = [];
        let currentSheetIndex = 0;

        document.addEventListener('DOMContentLoaded', () => {
            loadData();
        });

        // Download PDF (Print All Pages)
        function downloadPDF() {
            // Save current work
            if (editorInstance) saveCurrentSheetData();

            // Create container
            const printContainer = document.createElement('div');
            printContainer.id = 'bulk-print-container';
            printContainer.style.display = 'none'; // Hidden on screen

            // Generate HTML for all sheets
            sheets.forEach((sheet, index) => {
                const page = document.createElement('div');
                page.className = 'page-container';
                page.style.cssText = 'display: flex; flex-direction: column; min-height: 297mm;';
                if (index < sheets.length - 1) {
                    page.style.pageBreakAfter = 'always';
                }

                page.innerHTML = `
                <header class="doc-header">
                    <div class="doc-logo">
                        <img src="assets/images/turtle_logo.png" alt="Turtledot Logo">
                    </div>
                    <div class="doc-banner"><span>TURTLEDOT</span></div>
                </header>
                
                <div class="print-editor-body ql-container ql-snow" style="border: none; flex: 1;">
                    <div class="ql-editor" style="padding: 0; overflow: visible;">${sheet.data || ''}</div>
                </div>
                
                <footer class="doc-footer" style="margin-top: auto;">
                    <div class="signatures">
                        <div class="signature-box">
                            <div class="signature-line"></div>
                            <div class="signature-label">Founder</div>
                        </div>
                        <div class="signature-box">
                            <div class="signature-line"></div>
                            <div class="signature-label">Co-Founder</div>
                        </div>
                        <div class="signature-box">
                            <div class="signature-line"></div>
                            <div class="signature-label">Client</div>
                        </div>
                    </div>
                    <div class="sheet-number">Sheet ${index + 1}</div>
                </footer>
                `;
                printContainer.appendChild(page);
            });

            document.body.appendChild(printContainer);
            document.body.classList.add('printing-mode');

            setTimeout(() => {
                window.print();
                // Cleanup after print dialog closes (code resumes)
                document.body.classList.remove('printing-mode');
                if (document.body.contains(printContainer)) {
                    document.body.removeChild(printContainer);
                }
            }, 100);
        }

        // Custom Modal Functions
        let modalResolve = null;

        function showModal(title, message, type = 'confirm', inputValue = '', confirmText = null) {
            return new Promise((resolve) => {
                modalResolve = resolve;
                const modal = document.getElementById('customModal');
                const modalTitle = document.getElementById('modalTitle');
                const modalMessage = document.getElementById('modalMessage');
                const modalInput = document.getElementById('modalInput');
                const confirmBtn = document.getElementById('modalConfirmBtn');

                modalTitle.textContent = title;
                modalMessage.textContent = message;

                if (type === 'prompt') {
                    modalInput.style.display = 'block';
                    modalInput.value = inputValue;
                    modalInput.focus();
                    setTimeout(() => modalInput.select(), 100);
                } else {
                    modalInput.style.display = 'none';
                }

                if (type === 'delete') {
                    confirmBtn.className = 'modal-btn modal-btn-danger';
                    confirmBtn.textContent = confirmText || 'Delete';
                } else {
                    confirmBtn.className = 'modal-btn modal-btn-confirm';
                    confirmBtn.textContent = confirmText || (type === 'prompt' ? 'Save' : 'Confirm');
                }

                modal.classList.add('active');
            });
        }

        function closeModal(confirmed) {
            const modal = document.getElementById('customModal');
            const modalInput = document.getElementById('modalInput');

            modal.classList.remove('active');

            if (modalResolve) {
                if (confirmed && modalInput.style.display !== 'none') {
                    modalResolve(modalInput.value);
                } else {
                    modalResolve(confirmed);
                }
                modalResolve = null;
            }
        }

        // Close modal on overlay click
        document.addEventListener('click', (e) => {
            if (e.target.id === 'customModal') {
                closeModal(false);
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.getElementById('customModal').classList.contains('active')) {
                closeModal(false);
            }
        });

        async function loadData() {
            try {
                const response = await fetch(`api/word_prep.php?lead_id=${LEAD_ID}`);
                const result = await response.json();

                // Check if we have sheets data
                if (result.data && result.data.sheets && Array.isArray(result.data.sheets)) {
                    sheets = result.data.sheets;
                    // Ensure all sheets have assigned_to field
                    sheets.forEach(sheet => {
                        if (!sheet.hasOwnProperty('assigned_to')) {
                            sheet.assigned_to = null;
                        }
                    });
                } else {
                    // Legacy format or first time - create default doc
                    sheets = [{
                        name: 'Document1',
                        data: '',
                        assigned_to: null
                    }];
                }

                if (sheets.length === 0) {
                    sheets = [{
                        name: 'Document1',
                        data: '',
                        assigned_to: null
                    }];
                }

                renderSheetTabs();
                switchToSheet(0);

            } catch (error) {
                console.error("Error loading data", error);
                sheets = [{
                    name: 'Sheet1',
                    data: [],
                    style: {},
                    colWidths: []
                }];
                renderSheetTabs();
                switchToSheet(0);
            }
        }

        function renderSheetTabs() {
            const container = document.getElementById('sheetTabs');
            container.innerHTML = '';

            sheets.forEach((sheet, index) => {
                const tab = document.createElement('div');
                tab.className = 'sheet-tab' + (index === currentSheetIndex ? ' active' : '');

                const nameSpan = document.createElement('span');
                nameSpan.className = 'sheet-tab-name';
                nameSpan.textContent = sheet.name;
                nameSpan.ondblclick = () => renameSheet(index);

                tab.appendChild(nameSpan);

                if (sheets.length > 1) {
                    const deleteBtn = document.createElement('span');
                    deleteBtn.className = 'sheet-tab-delete';
                    deleteBtn.innerHTML = '×';
                    deleteBtn.onclick = (e) => {
                        e.stopPropagation();
                        deleteSheet(index);
                    };
                    tab.appendChild(deleteBtn);
                }

                tab.onclick = () => switchToSheet(index);
                container.appendChild(tab);
            });
        }

        function switchToSheet(index) {
            if (index < 0 || index >= sheets.length) return;

            // Save current sheet data before switching
            if (editorInstance && currentSheetIndex >= 0 && currentSheetIndex < sheets.length) {
                saveCurrentSheetData();
            }

            currentSheetIndex = index;
            const sheet = sheets[index];

            // Update Page Number in Footer (Print View)
            const sheetNumEl = document.getElementById('currentSheetNum');
            if (sheetNumEl) {
                sheetNumEl.textContent = `Sheet ${index + 1}`;
            }

            initEditor(sheet.data || '');
            if (editorInstance) {
                editorInstance.enable();
            }

            renderSheetTabs();
        }

        function saveCurrentSheetData() {
            if (!editorInstance) return;
            // Save HTML content
            sheets[currentSheetIndex].data = editorInstance.root.innerHTML;
        }

        async function addNewSheet() {
            saveCurrentSheetData();

            const newSheetNumber = sheets.length + 1;
            const sheetName = await showModal(
                'Create New Document',
                'Enter a name for the new document:',
                'prompt',
                `Document${newSheetNumber}`,
                'Create'
            );

            if (!sheetName || !sheetName.trim()) {
                return;
            }

            sheets.push({
                name: sheetName.trim(),
                data: '',
                assigned_to: null
            });

            await new Promise(resolve => setTimeout(resolve, 100));

            const newIndex = sheets.length - 1;
            switchToSheet(newIndex);
        }

        async function renameSheet(index) {
            const newName = await showModal(
                'Rename Document',
                'Enter a new name for this document:',
                'prompt',
                sheets[index].name
            );
            if (newName && newName.trim()) {
                sheets[index].name = newName.trim();
                renderSheetTabs();
            }
        }

        async function deleteSheet(index) {
            if (sheets.length <= 1) {
                await showModal(
                    'Cannot Delete',
                    'You cannot delete the last remaining document!',
                    'confirm'
                );
                return;
            }

            const confirmed = await showModal(
                'Delete Document',
                `Are you sure you want to delete "${sheets[index].name}"? This action cannot be undone.`,
                'delete'
            );

            if (confirmed) {
                sheets.splice(index, 1);

                if (currentSheetIndex >= sheets.length) {
                    currentSheetIndex = sheets.length - 1;
                } else if (currentSheetIndex > index) {
                    currentSheetIndex--;
                }

                switchToSheet(currentSheetIndex);
            }
        }

        function initEditor(content = '') {
            const container = document.getElementById('editor-container');
            if (!container) return; // Guard

            if (!editorInstance) {
                editorInstance = new Quill('#editor-container', {
                    theme: 'snow',
                    placeholder: 'Start writing your document...',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            [{ 'align': [] }],
                            ['link', 'clean']
                        ]
                    }
                });
            }

            // Set content
            editorInstance.root.innerHTML = content || '';
        }

        async function saveSheet() {
            saveCurrentSheetData(); // Save current editor content to sheets array

            const status = document.getElementById('saveStatus');
            const btn = document.querySelector('.btn-primary');
            const originalText = btn.innerHTML;

            status.textContent = 'Saving...';
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            try {
                const payload = {
                    lead_id: LEAD_ID,
                    data: {
                        sheets: sheets
                    }
                };

                // Use word_prep.php API
                const response = await fetch('api/word_prep.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Document Saved',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Saving',
                        text: result.error || 'Unknown error',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000
                    });
                }
            } catch (error) {
                console.error('Error saving:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Failed to save document',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000
                });
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function logout() {
            try {
                await fetch('/api/logout.php');
                localStorage.removeItem('user');
                window.location.href = '/login.php';
            } catch (error) {
                window.location.href = '/login.php';
            }
        }
    </script>
</body>

</html>