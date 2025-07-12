<?php
require_once 'includes/auth.php';
check_login();
require_once 'db_connect.php';

// Example data fetching for a report
$loan_portfolio_query = "SELECT b.branch_name, COUNT(i.id) as number_of_loans, SUM(i.principal_amount) as total_principal, SUM(i.outstanding_amount) as total_outstanding
                         FROM invoices i
                         JOIN branches b ON i.branch_id = b.id
                         GROUP BY b.branch_name";
$loan_portfolio_result = $conn->query($loan_portfolio_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Gold Loan Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reports & Analytics</h1>
                </div>

                <!-- Report Tabs -->
                <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="portfolio-tab" data-bs-toggle="tab" data-bs-target="#portfolio" type="button" role="tab">Loan Portfolio</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">Gold Inventory</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="compliance-tab" data-bs-toggle="tab" data-bs-target="#compliance" type="button" role="tab">Compliance</button>
                    </li>
                </ul>

                <div class="tab-content" id="reportTabsContent">
                    <!-- Loan Portfolio Report -->
                    <div class="tab-pane fade show active" id="portfolio" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header d-flex justify-content-between">
                                <span>Loan Portfolio by Branch</span>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary">Download PDF</button>
                                    <button class="btn btn-sm btn-outline-secondary">Download CSV</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Branch Name</th>
                                            <th>Number of Loans</th>
                                            <th>Total Principal</th>
                                            <th>Total Outstanding</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $loan_portfolio_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['branch_name']; ?></td>
                                            <td><?php echo $row['number_of_loans']; ?></td>
                                            <td>$<?php echo number_format($row['total_principal'], 2); ?></td>
                                            <td>$<?php echo number_format($row['total_outstanding'], 2); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                         <div class="card mt-4">
                            <div class="card-header">Analytics Chart Placeholder</div>
                            <div class="card-body text-center p-5">
                                <p class="text-muted">A chart showing overdue loans trend would be displayed here.</p>
                                <canvas id="overdue-trends-chart" style="display: none;"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gold Inventory Report -->
                    <div class="tab-pane fade" id="inventory" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">Gold Inventory Placeholder</div>
                            <div class="card-body">
                                <p>This section will contain reports on total gold in custody, broken down by purity, branch, and storage location.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Compliance Report -->
                    <div class="tab-pane fade" id="compliance" role="tabpanel">
                         <div class="card mt-3">
                            <div class="card-header">Compliance Overview Placeholder</div>
                            <div class="card-body">
                                <p>This section will contain reports related to KYC compliance, loan-to-value ratios, and other regulatory requirements.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
