<div class="sidebar">
    <h2 class="sidebar-title">Student Panel</h2>
    <ul class="sidebar-menu">
        <li>
            <a href="index.php" onclick="showDashboard()">
                <i class="fas fa-tachometer-alt"></i> <span>Student Dashboard</span>
            </a>
        </li>
        
        <!-- Section Management with Dropdown -->
        <li class="dropdown">
            <a href="#" class="dropdown-toggle">
                <i class="fas fa-th-list"></i> <span>Section Management</span>
                <i class="fas fa-angle-right dropdown-arrow"></i>
            </a>
            <ul class="submenu">
                <li class="submenu-item">
                    <a href="SectionManagment.php" class="submenu-link">
                        <i class="fas fa-eye submenu-icon"></i> <span>Preview Registered Sections</span>
                    </a>
                </li>
                <li class="submenu-item">
                    <a href="AddSection.php" class="submenu-link">
                        <i class="fas fa-plus-circle submenu-icon"></i> <span>Add Section</span>
                    </a>
                </li>
                <li class="submenu-item">
                    <a href="ChangeSection.php" class="submenu-link">
                        <i class="fas fa-exchange-alt submenu-icon"></i> <span>Change Section</span>
                    </a>
                </li>
                <li class="submenu-item">
                    <a href="DropSection.php" class="submenu-link">
                        <i class="fas fa-minus-circle submenu-icon"></i> <span>Drop Section</span>
                    </a>
                </li>
            </ul>
        </li>
        
        <li>
            <a href="PreviewPlan.php">
                <i class="fas fa-list-alt"></i> <span>Preview Plan</span>
            </a>
        </li>
        <li>
            <a href="PreviewTimeTable.php">
                <i class="fas fa-calendar-alt"></i> <span>Preview Time Table</span>
            </a>
        </li>
        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</div>
