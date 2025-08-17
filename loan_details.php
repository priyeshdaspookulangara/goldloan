<?php
require_once 'includes/auth.php';
check_login();
require_once 'db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_loans.php");
    exit();
}

$loan_id = (int)$_GET['id'];

// Fetch Loan Details using Prepared Statement
$loan_sql = "SELECT i.*, c.name as client_name, c.contact_number, c.address, b.branch_name, u.username as officer_name
             FROM invoices i
             JOIN clients c ON i.client_id = c.id
             JOIN branches b ON i.branch_id = b.id
             JOIN users u ON i.officer_id = u.id
             WHERE i.id = ?";
$stmt = $conn->prepare($loan_sql);
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$loan_result = $stmt->get_result();

if ($loan_result->num_rows == 0) {
    die("Loan not found.");
}
$loan = $loan_result->fetch_assoc();
$stmt->close();

// Fetch Repayment Schedule if it's an EMI loan
$schedule_result = null;
if ($loan['repayment_type'] === 'emi') {
    $schedule_sql = "SELECT * FROM repayment_schedule WHERE loan_id = ? ORDER BY due_date ASC";
    $stmt = $conn->prepare($schedule_sql);
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $schedule_result = $stmt->get_result();
    $stmt->close();
}

// Fetch Collateral Items
$collateral_sql = "SELECT * FROM collateral_items WHERE loan_id = ?";
$stmt = $conn->prepare($collateral_sql);
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$collateral_result = $stmt->get_result();
$stmt->close();

// Fetch Transactions
$transactions_sql = "SELECT * FROM transactions WHERE loan_id = ? ORDER BY transaction_date DESC";
$stmt = $conn->prepare($transactions_sql);
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$transactions_result = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Details - <?php echo htmlspecialchars($loan['loan_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php if (isset($_GET['payment_status'])): ?>
                    <div class="alert alert-<?php echo $_GET['payment_status'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        Payment recorded <?php echo $_GET['payment_status'] == 'success' ? 'successfully' : 'with an error'; ?>.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Loan Details: <?php echo htmlspecialchars($loan['loan_number']); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">Record Payment</button>
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
                                <p><strong>Interest Rate:</strong> <?php echo htmlspecialchars($loan['interest_rate']); ?>%</p>
                                <p><strong>Repayment Type:</strong> <?php echo ucfirst(htmlspecialchars($loan['repayment_type'])); ?></p>
                                <p><strong>Total Repayable:</strong> $<?php echo number_format($loan['total_repayable'], 2); ?></p>
                                <p><strong>Outstanding:</strong> $<?php echo number_format($loan['outstanding_amount'], 2); ?></p>
                                <p><strong>Status:</strong> <span class="badge bg-primary"><?php echo ucfirst(htmlspecialchars($loan['status'])); ?></span></p>
                                <p><strong>Loan Date:</strong> <?php echo htmlspecialchars($loan['loan_date']); ?></p>
                                <p><strong>Due Date:</strong> <?php echo htmlspecialchars($loan['due_date']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Client Information</h5>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($loan['client_name']); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($loan['contact_number']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($loan['address']); ?></p>
                                <p><strong>Branch:</strong> <?php echo htmlspecialchars($loan['branch_name']); ?></p>
                                <p><strong>Loan Officer:</strong> <?php echo htmlspecialchars($loan['officer_name']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EMI Repayment Schedule -->
                <?php if ($loan['repayment_type'] === 'emi' && $schedule_result->num_rows > 0): ?>
                <div class="card mb-4">
                    <div class="card-header">EMI Repayment Schedule</div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Due Date</th>
                                    <th>Installment Amt</th>
                                    <th>Principal</th>
                                    <th>Interest</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while($inst = $schedule_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($inst['due_date']); ?></td>
                                    <td>$<?php echo number_format($inst['installment_amount'], 2); ?></td>
                                    <td>$<?php echo number_format($inst['principal_component'], 2); ?></td>
                                    <td>$<?php echo number_format($inst['interest_component'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $inst['status'] == 'paid' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($inst['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($inst['status'] == 'pending'): ?>
                                            <button class="btn btn-xs btn-success">Pay</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>


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
                                    <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                                    <td><?php echo htmlspecialchars($item['weight_grams']); ?></td>
                                    <td><?php echo htmlspecialchars($item['purity_karat']); ?></td>
                                    <td><?php echo $item['hallmark_verified'] ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo htmlspecialchars($item['storage_location']); ?></td>
                                    <td><?php echo htmlspecialchars($item['locker_number'] ?? 'N/A'); ?></td>
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
                                    <td><?php echo htmlspecialchars($trans['id']); ?></td>
                                    <td><?php echo htmlspecialchars($trans['transaction_date']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($trans['transaction_type']))); ?></td>
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

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="paymentModalLabel">Record a Payment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="payment-form" action="record_payment.php" method="post">
                <input type="hidden" name="loan_id" value="<?php echo $loan_id; ?>">
                <div class="mb-3">
                    <label for="payment_amount" class="form-label">Amount</label>
                    <input type="number" step="0.01" class="form-control" id="payment_amount" name="payment_amount" required>
                </div>
                <div class="mb-3">
                    <label for="payment_date" class="form-label">Payment Date</label>
                    <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                 <div class="mb-3">
                    <label for="payment_type" class="form-label">Payment Type</label>
                    <select class="form-control" id="payment_type" name="payment_type">
                        <option value="partial_payment">Partial Payment</option>
                        <option value="installment_payment">Installment Payment</option>
                        <option value="foreclosure">Foreclosure</option>
                    </select>
                </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" form="payment-form" class="btn btn-primary">Save Payment</button>
          </div>
        </div>
      </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
