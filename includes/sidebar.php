<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="dashboard.php">
                    <span data-feather="home"></span>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="new_loan.php">
                    <span data-feather="file-plus"></span>
                    New Loan Application
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_loans.php">
                    <span data-feather="file-text"></span>
                    Manage Loans
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php">
                    <span data-feather="bar-chart-2"></span>
                    Reports
                </a>
            </li>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span data-feather="users"></span>
                    User Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span data-feather="settings"></span>
                    Settings
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    feather.replace()
</script>
