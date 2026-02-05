<?php
// Expects $user array and optional $currentPage variable to be available
$currentPage = $currentPage ?? '';
?>
<aside class="sidebar">
    <div class="brand">
        <img src="assets/images/turtle_logo.png" alt="Turtle Dot Logo" class="brand-logo">
        <span class="brand-text">TURTLE DOT</span>
    </div>
    <ul class="nav-links">
        <?php if ($user['role'] === 'admin'): ?>
            <li class="nav-item">
                <a href="admin_dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_associates.php"
                    class="<?php echo $currentPage === 'marketing_associates' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-group"></i> Marketing Associates
                </a>
            </li>
            <li class="nav-item">
                <a href="technical_associates.php"
                    class="<?php echo $currentPage === 'technical_associates' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-laptop-code"></i> Technical Associates
                </a>
            </li>
            <li class="nav-item">
                <a href="#" onclick="openSecurityModal()">
                    <i class="fa-solid fa-shield-alt"></i> Security
                </a>
            </li>
        <?php elseif ($user['role'] === 'technical'): ?>
            <li class="nav-item">
                <a href="technical_dashboard.php" class="<?php echo $currentPage === 'projects' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-project-diagram"></i> My Projects
                </a>
            </li>
        <?php else: ?>
            <li class="nav-item">
                <a href="index.php" class="<?php echo $currentPage === 'leads' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users"></i> Leads
                </a>
            </li>
            <li class="nav-item">
                <a href="converted.php" class="<?php echo $currentPage === 'converted' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-check-circle"></i> Converted
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="user-profile">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <div class="user-name">
                    <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                </div>
                <div class="user-role">
                    <?php echo htmlspecialchars($user['role']); ?>
                </div>
            </div>
        </div>
        <button onclick="logout()" class="btn-logout">
            <i class="fa-solid fa-sign-out-alt"></i> Logout
        </button>
    </div>
</aside>