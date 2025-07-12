<?php
require_once 'includes/auth.php';
check_login();
require_once 'db_connect.php';

// Fetching summary data
$total_disbursed_query = "SELECT SUM(principal_amount) as total_disbursed FROM invoices";
$total_disbursed_result = $conn->query($total_disbursed_query);
$total_disbursed = $total_disbursed_result->fetch_assoc()['total_disbursed'] ?? 0;

$total_gold_query = "SELECT SUM(weight_grams) as total_gold FROM collateral_items";
$total_gold_result = $conn->query($total_gold_query);
$total_gold = $total_gold_result->fetch_assoc()['total_gold'] ?? 0;

$overdue_loans_query = "SELECT COUNT(*) as overdue_count FROM invoices WHERE due_date < CURDATE() AND status = 'active'";
$overdue_loans_result = $conn->query($overdue_loans_query);
$overdue_loans = $overdue_loans_result->fetch_assoc()['overdue_count'] ?? 0;

// Gold prices from history
$gold_price_query = "SELECT price_24k_per_gram, price_22k_per_gram FROM gold_prices_history ORDER BY date DESC LIMIT 1";
$gold_price_result = $conn->query($gold_price_query);
$gold_prices = $gold_price_result->fetch_assoc();

// Chart data
$loans_by_branch_query = "SELECT b.branch_name, COUNT(i.id) as loan_count FROM invoices i JOIN branches b ON i.branch_id = b.id GROUP BY b.branch_name";
$loans_by_branch_result = $conn->query($loans_by_branch_query);
$branch_labels = [];
$branch_data = [];
while($row = $loans_by_branch_result->fetch_assoc()) {
    $branch_labels[] = $row['branch_name'];
    $branch_data[] = $row['loan_count'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gold Loan Management</title>
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
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- Quick Stats -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-header">Total Loans Disbursed</div>
                            <div class="card-body">
                                <h5 class="card-title">$<?php echo number_format($total_disbursed, 2); ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-header">Total Gold in Custody (grams)</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo number_format($total_gold, 2); ?> g</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger mb-3">
                            <div class="card-header">Overdue Loans</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $overdue_loans; ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info mb-3">
                             <div class="card-header">Gold Prices (per gram)</div>
                            <div class="card-body">
                                <p class="card-text mb-0">24K: $<?php echo $gold_prices['price_24k_per_gram'] ?? 'N/A'; ?></p>
                                <p class="card-text">22K: $<?php echo $gold_prices['price_22k_per_gram'] ?? 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mb-4">
                    <h2>Quick Actions</h2>
                    <a href="new_loan.php" class="btn btn-primary">New Loan Application</a>
                    <a href="manage_loans.php" class="btn btn-secondary">Manage Loans</a>
                    <a href="reports.php" class="btn btn-info">View Reports</a>
                </div>


                <!-- Analytics Placeholder -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Loans per Branch
                            </div>
                            <div class="card-body">
                                <canvas id="loansByBranchChart"></canvas>
                            </div>
                        </div>
                    </div>
                     <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Loan Status Overview
                            </div>
                            <div class="card-body">
                                <div class="text-center p-5">CHART PLACEHOLDER</div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart.js for Loans per Branch
        const ctx = document.getElementById('loansByBranchChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($branch_labels); ?>,
                datasets: [{
                    label: '# of Loans',
                    data: <?php echo json_encode($branch_data); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
