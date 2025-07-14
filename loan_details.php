<?php
require_once 'includes/auth.php';
check_login();
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: manage_loans.php");
    exit();
}

$loan_id = $_GET['id'];

// Fetch Loan Details
$loan_sql = "SELECT i.*, c.name as client_name, c.contact_number, c.address, b.branch_name, u.username as officer_name
             FROM invoices i
             JOIN clients c ON i.client_id = c.id
             JOIN branches b ON i.branch_id = b.id
             JOIN users u ON i.officer_id = u.id
             WHERE i.id = $loan_id";
$loan_result = $conn->query($loan_sql);
if ($loan_result->num_rows == 0) {
    die("Loan not found.");
}
$loan = $loan_result->fetch_assoc();

// Fetch Collateral Items
$collateral_sql = "SELECT * FROM collateral_items WHERE loan_id = $loan_id";
$collateral_result = $conn->query($collateral_sql);

// Fetch Transactions
$transactions_sql = "SELECT * FROM transactions WHERE loan_id = $loan_id ORDER BY transaction_date DESC";
$transactions_result = $conn->query($transactions_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Details - <?php echo $loan['loan_number']; ?></title>
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
                    <h1 class="h2">Loan Details: <?php echo $loan['loan_number']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-success">Record Payment</button>
                        <button class="btn btn-warning ms-2">Generate Statement</button>
                        <button class="btn btn-danger ms-2">Initiate Foreclosure</button>
                    </div>
                </div>

                <!-- Loan Summary -->
                <div class="card mb-4">
                    <div class="card-header">Loan & Client Summary</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Loan Information</h5>
                                <p><strong>Principal:</strong> $<?php echo number_format($loan['principal_amount'], 2); ?></p>
                                <p><strong>Interest Rate:</strong> <?php echo $loan['interest_rate']; ?>%</p>
                                <p><strong>Total Repayable:</strong> $<?php echo number_format($loan['total_repayable'], 2); ?></p>
                                <p><strong>Outstanding:</strong> $<?php echo number_format($loan['outstanding_amount'], 2); ?></p>
                                <p><strong>Status:</strong> <span class="badge bg-primary"><?php echo ucfirst($loan['status']); ?></span></p>
                                <p><strong>Loan Date:</strong> <?php echo $loan['loan_date']; ?></p>
                                <p><strong>Due Date:</strong> <?php echo $loan['due_date']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Client Information</h5>
                                <p><strong>Name:</strong> <?php echo $loan['client_name']; ?></p>
                                <p><strong>Contact:</strong> <?php echo $loan['contact_number']; ?></p>
                                <p><strong>Address:</strong> <?php echo $loan['address']; ?></p>
                                <p><strong>Branch:</strong> <?php echo $loan['branch_name']; ?></p>
                                <p><strong>Loan Officer:</strong> <?php echo $loan['officer_name']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Collateral Details -->
                <div class="card mb-4">
                    <div class="card-header">Collateral Details</div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Weight (g)</th>
                                    <th>Purity (K)</th>
                                    <th>Hallmark Verified</th>
                                    <th>Storage Location</th>
                                    <th>Locker Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($item = $collateral_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $item['item_description']; ?></td>
                                    <td><?php echo $item['weight_grams']; ?></td>
                                    <td><?php echo $item['purity_karat']; ?></td>
                                    <td><?php echo $item['hallmark_verified'] ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo $item['storage_location']; ?></td>
                                    <td><?php echo $item['locker_number'] ?? 'N/A'; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Transaction History -->
                <div class="card">
                    <div class="card-header">Transaction History</div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($trans = $transactions_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $trans['id']; ?></td>
                                    <td><?php echo $trans['transaction_date']; ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $trans['transaction_type'])); ?></td>
                                    <td>$<?php echo number_format($trans['amount'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
