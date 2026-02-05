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

// Fetch technical team members for assignment dropdown
$techStmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'technical' ORDER BY username");
$techStmt->execute();
$technicalTeam = $techStmt->fetchAll();
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spreadsheet Prep -
        <?php echo htmlspecialchars($pageTitle); ?>
    </title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Handsontable -->
    <link href="https://cdn.jsdelivr.net/npm/handsontable@12.3.1/dist/handsontable.full.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/handsontable@12.3.1/dist/handsontable.full.min.js"></script>

    <!-- SweetAlert2 for toast notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --surface: #ffffff;
            --background: #f1f5f9;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        body {
            background-color: var(--background);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
        }

        .main-content {
            padding: 2rem;
            max-width: 1600px;
            /* margin: 0 auto;  Avoid centering if sidebar exists */
            margin-left: 260px;
            /* Assume sidebar width approx 250px */
            width: calc(100% - 280px);
            /* Adjust width */
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                margin: 0 auto;
                padding-top: 80px;
                /* Mobile header spacing */
            }
        }

        /* Header Styling */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            padding: 1.5rem 2rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: var(--shadow);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .page-title span {
            color: var(--text-muted);
            font-weight: 400;
        }

        /* Controls & Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
        }

        .btn-outline {
            background: white;
            border-color: var(--border);
            color: var(--text);
            box-shadow: var(--shadow-sm);
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.4);
        }

        /* Sheet Wrapper */
        .sheet-wrapper {
            background: white;
            /* border-radius: 16px; */
            box-shadow: var(--shadow-lg);
            /* overflow: hidden; Removed to prevent clipping scrollbars */
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }

        /* Toolbar */
        .formatting-toolbar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-right: 1rem;
            margin-right: 0.5rem;
            border-right: 1px solid var(--border);
        }

        .toolbar-group:last-child {
            border-right: none;
            padding-right: 0;
        }

        .toolbar-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .toolbar-select {
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            background-color: white;
            cursor: pointer;
            transition: all 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
        }

        .toolbar-select:hover {
            border-color: var(--primary);
        }

        .toolbar-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .toolbar-btn {
            height: 36px;
            padding: 0 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            /* Ensure square icon-only buttons stay square-ish if needed, but text buttons grow */
            border: 1px solid var(--border);
            border-radius: 8px;
            background: white;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            gap: 0.5rem;
        }

        .toolbar-btn:hover {
            background: #f1f5f9;
            color: var(--primary);
            border-color: var(--primary);
        }

        .color-picker-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .color-picker-wrapper:hover {
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        .color-preview {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        input[type="color"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            top: 0;
            left: 0;
        }

        #spreadsheet-container {
            height: 500px;
            /* Reduced fixed height */
            height: calc(100vh - 340px);
            /* Responsive height */
            min-height: 400px;
            overflow: auto;
            /* Allow scrolling if HT fails to virtualize */
            background: #fff;
            border-bottom: 1px solid var(--border);
            position: relative;
            /* Ensure z-index works */
        }

        /* Tabs */
        .sheet-tabs {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid var(--border);
        }

        .tabs-container {
            display: flex;
            gap: 0.5rem;
            flex: 1;
            overflow-x: auto;
            padding-bottom: 2px;
            /* Hide scrollbar */
            scrollbar-width: none;
        }

        .tabs-container::-webkit-scrollbar {
            display: none;
        }

        .sheet-tab {
            padding: 0.625rem 1.25rem;
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-muted);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .sheet-tab:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .sheet-tab.active {
            background: var(--text);
            color: white;
            border-color: var(--text);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .sheet-tab-name {
            outline: none;
        }

        .sheet-tab-input {
            background: transparent;
            border: none;
            border-bottom: 1px solid currentColor;
            color: inherit;
            font: inherit;
            width: 80px;
            padding: 0;
        }

        .sheet-tab-input:focus {
            outline: none;
        }

        .sheet-tab-delete {
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            opacity: 0.6;
            transition: all 0.2s;
            font-size: 1.1rem;
            line-height: 1;
        }

        .sheet-tab-delete:hover {
            background: rgba(255, 255, 255, 0.2);
            opacity: 1;
            color: #ef4444;
        }

        .sheet-tab.active .sheet-tab-delete:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .add-sheet-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: white;
            border: 1px solid var(--border);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }

        .add-sheet-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: scale(1.05);
        }

        /* Assign Dropdown */
        .assign-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: white;
            padding: 0.25rem 0.25rem 0.25rem 1rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .assign-dropdown {
            border: none;
            padding: 0.5rem 2rem 0.5rem 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text);
            background: transparent;
            cursor: pointer;
            min-width: 200px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
        }

        .assign-dropdown:focus {
            outline: none;
        }

        /* Handsontable Design Enhancements - Excel Style */
        .handsontable th,
        .handsontable td {
            box-sizing: border-box !important;
            vertical-align: middle;
            line-height: normal !important;
            /* Let HT manage line height or use simple consistent value */
            padding: 0 4px !important;
        }

        .handsontable th {
            background-color: #f8f9fa;
            color: #4b5563;
            font-weight: 600;
            border-bottom: 1px solid #c0c0c0 !important;
            border-right: 1px solid #c0c0c0 !important;
            text-align: center;
            border-radius: 0 !important;
            white-space: nowrap;
            /* height removed to allow resize */
        }

        /* Column headers specifically (thead) might need more height */
        .handsontable thead th {
            height: 30px !important;
            /* Keep column headers fixed height usually acceptable, but row headers must be flexible */
            background-color: #f1f5f9;
        }

        .handsontable td {
            border-bottom: 1px solid #d4d4d4 !important;
            border-right: 1px solid #d4d4d4 !important;
            color: var(--text);
            background-color: #fff;
            /* height removed to allow resize */
        }

        /* Custom Selection Styles */
        .handsontable .wtBorder.current,
        .handsontable .wtBorder.area {
            background-color: var(--primary) !important;
            width: 2px !important;
        }

        .handsontable .area.highlight {
            background-color: rgba(16, 185, 129, 0.1) !important;
        }

        /* Header Active State */
        .handsontable th.ht__highlight {
            background-color: #e2e8f0;
            color: #1f2937;
        }

        /* Selection tint */
        .handsontable .htSelection {
            background-color: rgba(16, 185, 129, 0.08) !important;
        }

        .handsontable .htSelection.current {
            background-color: transparent !important;
        }

        /* Handsontable Overrides */
        .handsontable {
            font-family: 'Outfit', sans-serif !important;
            font-size: 13px !important;
        }

        .handsontable th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            border-bottom-color: var(--border);
            border-right-color: var(--border);
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
                <h1 class="page-title">Spreadsheet Prep: <span style="font-weight: 400;">
                        <?php echo htmlspecialchars($pageTitle); ?>
                    </span></h1>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <div class="assign-container">
                    <span class="toolbar-label" style="display:none">Assign:</span>
                    <select class="assign-dropdown" id="assigneeDropdown" onchange="updateAssignee()">
                        <option value="">Select Technical Associate</option>
                        <?php foreach ($technicalTeam as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>">
                                <?php echo htmlspecialchars($tech['username']); ?> (ID: <?php echo $tech['id']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary" onclick="saveSheet()">
                    <i class="fa-solid fa-save"></i> Save
                </button>
            </div>
        </header>

        <div class="sheet-wrapper">
            <!-- Formatting Toolbar -->
            <div class="formatting-toolbar">
                <div class="toolbar-group">
                    <span class="toolbar-label">Font Size:</span>
                    <select class="toolbar-select" id="fontSizeSelect" onchange="applyFontSize()">
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12" selected>12</option>
                        <option value="14">14</option>
                        <option value="16">16</option>
                        <option value="18">18</option>
                        <option value="20">20</option>
                        <option value="24">24</option>
                        <option value="28">28</option>
                        <option value="32">32</option>
                    </select>
                </div>

                <div class="toolbar-group">
                    <span class="toolbar-label">Text Color:</span>
                    <div class="color-picker-wrapper">
                        <div class="color-preview" id="textColorPreview" style="background-color: #000000;"
                            onclick="document.getElementById('textColorPicker').click()"></div>
                        <input type="color" id="textColorPicker" value="#000000" onchange="applyTextColor()">
                    </div>
                </div>

                <div class="toolbar-group">
                    <span class="toolbar-label">Cell Color:</span>
                    <div class="color-picker-wrapper">
                        <div class="color-preview" id="cellColorPreview" style="background-color: #ffffff;"
                            onclick="document.getElementById('cellColorPicker').click()"></div>
                        <input type="color" id="cellColorPicker" value="#ffffff" onchange="applyCellColor()">
                    </div>
                </div>

                <div class="toolbar-group">
                    <button class="toolbar-btn" onclick="clearFormatting()" title="Clear Formatting">
                        <i class="fa-solid fa-eraser"></i> Clear Format
                    </button>
                </div>
            </div>

            <div id="spreadsheet-container"></div>

            <div class="sheet-tabs">
                <div class="tabs-container" id="sheetTabs"></div>
                <button class="add-sheet-btn" onclick="addNewSheet()" title="Add Sheet">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </div>
    </main>

    <script>
        const IS_PROJECT = <?php echo $isProject ? 'true' : 'false'; ?>;
        const LEAD_ID = <?php echo $lead ? $lead['id'] : 'null'; ?>;
        const PROJECT_ID = <?php echo $project ? $project['id'] : 'null'; ?>;
        const CURRENT_USER_ID = <?php echo $user['user_id']; ?>;

        let hotInstance = null;
        let sheets = [];
        let currentSheetIndex = 0;
        let currentAssignee = null;
        let cellMeta = {}; // Store cell formatting metadata
        let lastSelectedRange = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadData();
        });

        async function loadData(assigneeId = null) {
            try {
                let url = IS_PROJECT
                    ? `/api/doc_prep.php?project_id=${PROJECT_ID}`
                    : `/api/doc_prep.php?lead_id=${LEAD_ID}`;

                if (assigneeId) {
                    url += `&assignee_id=${assigneeId}`;
                }

                const response = await fetch(url, { credentials: 'include' });
                const result = await response.json();

                if (result.data && result.data.sheets) {
                    sheets = result.data.sheets;
                    // Dont overwrite currentAssignee if we explicitly requested one, 
                    // unless we want to sync with DB. 
                    // But if we are switching TO a user, we want to see their data.
                    if (assigneeId) {
                        currentAssignee = assigneeId;
                    } else {
                        currentAssignee = result.data.assignee || null;
                    }
                    cellMeta = result.data.cellMeta || {};
                } else {
                    // Initialize with one empty sheet (200 rows x 200 columns)
                    sheets = [{
                        name: 'Sheet 1',
                        data: Array(200).fill().map(() => Array(200).fill(''))
                    }];
                    cellMeta = {};
                }

                // Set assignee dropdown
                if (currentAssignee) {
                    document.getElementById('assigneeDropdown').value = currentAssignee;
                }

                renderSheetTabs();
                loadSheet(0);
            } catch (error) {
                console.error('Error loading data:', error);
                showToast('Failed to load spreadsheet data', 'error');
                // Initialize with empty sheet on error (200 rows x 200 columns)
                sheets = [{
                    name: 'Sheet 1',
                    data: Array(200).fill().map(() => Array(200).fill(''))
                }];
                cellMeta = {};
                renderSheetTabs();
                loadSheet(0);
            }
        }

        function loadSheet(index) {
            currentSheetIndex = index;
            const sheet = sheets[index];

            if (hotInstance) {
                try {
                    hotInstance.destroy(); // Destroy previous instance
                } catch (e) {
                    console.warn("Error destroying Handsontable instance:", e);
                }
                hotInstance = null;
            }

            const container = document.getElementById('spreadsheet-container');
            // Clear container content explicitly to be safe
            container.innerHTML = '';

            hotInstance = new Handsontable(container, {
                data: sheet.data,
                rowHeaders: true,
                colHeaders: true,
                contextMenu: true,
                minRows: 200,
                minCols: 200,
                manualColumnResize: true,
                manualRowResize: true,
                colWidths: sheet.colWidths || 100, // Default width
                rowHeights: sheet.rowHeights || 23, // Default height
                outsideClickDeselects: false, // Keep selection when clicking outside
                licenseKey: 'non-commercial-and-evaluation',
                cells: function (row, col) {
                    const cellProperties = {};
                    const key = `${currentSheetIndex}_${row}_${col}`;

                    if (cellMeta[key]) {
                        const meta = cellMeta[key];
                        cellProperties.renderer = function (instance, td, row, col, prop, value, cellProperties) {
                            Handsontable.renderers.TextRenderer.apply(this, arguments);
                            if (meta.fontSize) td.style.fontSize = meta.fontSize + 'px';
                            if (meta.color) td.style.color = meta.color;
                            if (meta.backgroundColor) td.style.backgroundColor = meta.backgroundColor;
                        };
                    }

                    return cellProperties;
                },
                afterSelection: (r, c, r2, c2) => {
                    lastSelectedRange = [r, c, r2, c2];
                },
                afterChange: (changes, source) => {
                    if (source === 'loadData') return;
                    saveCurrentSheetData();
                },
                afterColumnResize: () => saveCurrentSheetData(),
                afterRowResize: () => saveCurrentSheetData()
            });

            renderSheetTabs();
        }

        function saveCurrentSheetData() {
            if (hotInstance && !hotInstance.isDestroyed && sheets[currentSheetIndex]) {
                sheets[currentSheetIndex].data = hotInstance.getData();

                // Save manual resize data
                const manualColPlugin = hotInstance.getPlugin('manualColumnResize');
                const manualRowPlugin = hotInstance.getPlugin('manualRowResize');

                sheets[currentSheetIndex].colWidths = manualColPlugin.manualColumnWidths;
                sheets[currentSheetIndex].rowHeights = manualRowPlugin.manualRowHeights;
            }
        }

        function renderSheetTabs() {
            const tabsContainer = document.getElementById('sheetTabs');
            tabsContainer.innerHTML = '';

            sheets.forEach((sheet, index) => {
                const tab = document.createElement('button');
                tab.className = 'sheet-tab' + (index === currentSheetIndex ? ' active' : '');
                tab.innerHTML = `
                    <span class="sheet-tab-name" ondblclick="renameSheet(${index}, event)">${sheet.name}</span>
                    ${sheets.length > 1 ? `<span class="sheet-tab-delete" onclick="deleteSheet(${index}, event)">×</span>` : ''}
                `;
                tab.onclick = (e) => {
                    if (!e.target.classList.contains('sheet-tab-delete') && !e.target.classList.contains('sheet-tab-input')) {
                        if (currentSheetIndex !== index) {
                            saveCurrentSheetData();
                            loadSheet(index);
                        }
                    }
                };
                tabsContainer.appendChild(tab);
            });
        }

        async function renameSheet(index, event) {
            event.stopPropagation();
            const nameSpan = event.target;
            const currentName = sheets[index].name;

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'sheet-tab-input';
            input.value = currentName;
            input.onclick = (e) => e.stopPropagation();

            nameSpan.replaceWith(input);
            input.focus();
            input.select();

            const finishRename = () => {
                const newName = input.value.trim() || currentName;
                sheets[index].name = newName;
                renderSheetTabs();
            };

            input.onblur = finishRename;
            input.onkeydown = (e) => {
                if (e.key === 'Enter') {
                    finishRename();
                } else if (e.key === 'Escape') {
                    renderSheetTabs();
                }
            };
        }

        function addNewSheet() {
            saveCurrentSheetData();
            const newSheet = {
                name: `Sheet ${sheets.length + 1}`,
                data: Array(30).fill().map(() => Array(200).fill(''))
            };
            sheets.push(newSheet);
            loadSheet(sheets.length - 1);
        }

        async function deleteSheet(index, event) {
            event.stopPropagation();

            if (sheets.length === 1) {
                showToast('Cannot delete the last sheet', 'error');
                return;
            }

            const result = await Swal.fire({
                title: 'Delete Sheet?',
                text: `Are you sure you want to delete "${sheets[index].name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it'
            });

            if (result.isConfirmed) {
                // Remove cell metadata for this sheet
                Object.keys(cellMeta).forEach(key => {
                    if (key.startsWith(`${index}_`)) {
                        delete cellMeta[key];
                    }
                });

                sheets.splice(index, 1);
                if (currentSheetIndex >= sheets.length) {
                    currentSheetIndex = sheets.length - 1;
                }
                loadSheet(currentSheetIndex);
                showToast('Sheet deleted successfully', 'success');
            }
        }

        function applyFontSize() {
            const fontSize = document.getElementById('fontSizeSelect').value;
            // Use current selection or fallback to last known selection
            const selected = hotInstance.getSelected() || (lastSelectedRange ? [lastSelectedRange] : null);

            if (!selected) {
                showToast('Please select cells first', 'warning');
                return;
            }

            selected.forEach(([startRow, startCol, endRow, endCol]) => {
                // Determine min/max to handle selection in any direction
                const r1 = Math.min(startRow, endRow);
                const r2 = Math.max(startRow, endRow);
                const c1 = Math.min(startCol, endCol);
                const c2 = Math.max(startCol, endCol);

                for (let row = r1; row <= r2; row++) {
                    for (let col = c1; col <= c2; col++) {
                        const key = `${currentSheetIndex}_${row}_${col}`;
                        if (!cellMeta[key]) cellMeta[key] = {};
                        cellMeta[key].fontSize = fontSize;
                    }
                }
            });

            hotInstance.render();
            showToast('Font size applied', 'success');
        }

        function applyTextColor() {
            const color = document.getElementById('textColorPicker').value;
            document.getElementById('textColorPreview').style.backgroundColor = color;

            const selected = hotInstance.getSelected() || (lastSelectedRange ? [lastSelectedRange] : null);

            if (!selected) {
                showToast('Please select cells first', 'warning');
                return;
            }

            selected.forEach(([startRow, startCol, endRow, endCol]) => {
                const r1 = Math.min(startRow, endRow);
                const r2 = Math.max(startRow, endRow);
                const c1 = Math.min(startCol, endCol);
                const c2 = Math.max(startCol, endCol);

                for (let row = r1; row <= r2; row++) {
                    for (let col = c1; col <= c2; col++) {
                        const key = `${currentSheetIndex}_${row}_${col}`;
                        if (!cellMeta[key]) cellMeta[key] = {};
                        cellMeta[key].color = color;
                    }
                }
            });

            hotInstance.render();
            showToast('Text color applied', 'success');
        }

        function applyCellColor() {
            const color = document.getElementById('cellColorPicker').value;
            document.getElementById('cellColorPreview').style.backgroundColor = color;

            const selected = hotInstance.getSelected() || (lastSelectedRange ? [lastSelectedRange] : null);

            if (!selected) {
                showToast('Please select cells first', 'warning');
                return;
            }

            selected.forEach(([startRow, startCol, endRow, endCol]) => {
                const r1 = Math.min(startRow, endRow);
                const r2 = Math.max(startRow, endRow);
                const c1 = Math.min(startCol, endCol);
                const c2 = Math.max(startCol, endCol);

                for (let row = r1; row <= r2; row++) {
                    for (let col = c1; col <= c2; col++) {
                        const key = `${currentSheetIndex}_${row}_${col}`;
                        if (!cellMeta[key]) cellMeta[key] = {};
                        cellMeta[key].backgroundColor = color;
                    }
                }
            });

            hotInstance.render();
            showToast('Cell color applied', 'success');
        }

        function clearFormatting() {
            const selected = hotInstance.getSelected() || (lastSelectedRange ? [lastSelectedRange] : null);

            if (!selected) {
                showToast('Please select cells first', 'warning');
                return;
            }

            selected.forEach(([startRow, startCol, endRow, endCol]) => {
                const r1 = Math.min(startRow, endRow);
                const r2 = Math.max(startRow, endRow);
                const c1 = Math.min(startCol, endCol);
                const c2 = Math.max(startCol, endCol);

                for (let row = r1; row <= r2; row++) {
                    for (let col = c1; col <= c2; col++) {
                        const key = `${currentSheetIndex}_${row}_${col}`;
                        delete cellMeta[key];
                    }
                }
            });

            hotInstance.render();
            showToast('Formatting cleared', 'success');
        }

        async function updateAssignee() {
            const newAssignee = document.getElementById('assigneeDropdown').value;

            if (newAssignee !== currentAssignee) {
                const result = await Swal.fire({
                    title: 'Switch Assignee View?',
                    text: 'Do you want to save current changes before switching?',
                    icon: 'question',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Save & Switch',
                    denyButtonText: 'Switch without Saving',
                    confirmButtonColor: '#10b981',
                    denyButtonColor: '#f59e0b'
                });

                if (result.isConfirmed) {
                    await saveSheet(); // Save to OLD assignee
                    loadData(newAssignee); // Load NEW assignee
                } else if (result.isDenied) {
                    loadData(newAssignee);
                } else {
                    // Cancel - revert dropdown
                    document.getElementById('assigneeDropdown').value = currentAssignee || "";
                }
            }
        }

        async function saveSheet() {
            saveCurrentSheetData();

            try {
                const url = '/api/doc_prep.php';
                const payload = {
                    data: {
                        sheets: sheets,
                        assignee: currentAssignee,
                        cellMeta: cellMeta
                    }
                };

                if (currentAssignee) {
                    payload.assignee_id = currentAssignee;
                }

                if (IS_PROJECT) {
                    payload.project_id = PROJECT_ID;
                } else {
                    payload.lead_id = LEAD_ID;
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Spreadsheet saved successfully', 'success');
                } else {
                    showToast(result.error || 'Failed to save spreadsheet', 'error');
                }
            } catch (error) {
                console.error('Error saving:', error);
                showToast('Error saving spreadsheet', 'error');
            }
        }

        function downloadPDF() {
            saveCurrentSheetData();

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');

            sheets.forEach((sheet, index) => {
                if (index > 0) {
                    doc.addPage();
                }

                doc.setFontSize(16);
                doc.text(sheet.name, 14, 15);

                // Get only non-empty rows and columns for PDF
                const nonEmptyData = sheet.data.filter(row => row.some(cell => cell !== ''));

                if (nonEmptyData.length > 0) {
                    doc.autoTable({
                        head: [nonEmptyData[0] || []],
                        body: nonEmptyData.slice(1),
                        startY: 25,
                        theme: 'grid',
                        styles: { fontSize: 8 }
                    });
                }
            });

            const filename = IS_PROJECT
                ? `project_${PROJECT_ID}_spreadsheet.pdf`
                : `lead_${LEAD_ID}_spreadsheet.pdf`;

            doc.save(filename);
            showToast('PDF downloaded successfully', 'success');
        }

        function showToast(message, type = 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: type,
                title: message
            });
        }

        async function logout() {
            try {
                await fetch('/api/logout.php');
                window.location.href = '/login.php';
            } catch (e) {
                window.location.href = '/login.php';
            }
        }
    </script>
</body>

</html>