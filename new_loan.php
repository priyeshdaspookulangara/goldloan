<?php
require_once 'includes/auth.php';
check_login();
require_once 'db_connect.php';
require_once 'includes/calculator.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Begin transaction
    $conn->begin_transaction();

    try {
        $client_id = 0;
        // 1. Determine Client ID (Existing or New)
        if (!empty($_POST['client_id'])) {
            // Use existing client
            $client_id = (int)$_POST['client_id'];
        } else {
            // Create a new client
            $client_stmt = $conn->prepare("INSERT INTO clients (name, contact_number, address, id_proof_type, id_proof_number) VALUES (?, ?, ?, ?, ?)");
            if (!$client_stmt) throw new Exception("Client statement preparation failed: " . $conn->error);

            $client_stmt->bind_param("sssss", $_POST['client_name'], $_POST['contact_number'], $_POST['address'], $_POST['id_proof_type'], $_POST['id_proof_number']);
            if (!$client_stmt->execute()) {
                throw new Exception("Error creating client: " . $client_stmt->error);
            }
            $client_id = $conn->insert_id;
            $client_stmt->close();
        }

        if ($client_id <= 0) {
            throw new Exception("Invalid Client ID.");
        }

        // 2. Insert Invoice (Loan)
        $loan_number = 'GL-' . date('Ymd') . '-' . $client_id;
        $branch_id = $_SESSION['role'] === 'admin' ? $_POST['branch_id'] : 1;
        $officer_id = $_SESSION['user_id'];
        $repayment_type = $_POST['repayment_type'];

        $invoice_stmt = $conn->prepare("INSERT INTO invoices (loan_number, client_id, loan_date, due_date, principal_amount, interest_rate, repayment_type, total_repayable, outstanding_amount, status, branch_id, officer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)");
        if (!$invoice_stmt) throw new Exception("Invoice statement preparation failed: " . $conn->error);

        $invoice_stmt->bind_param("sissddsdsii", $loan_number, $client_id, $_POST['loan_date'], $_POST['due_date'], $_POST['principal_amount'], $_POST['interest_rate'], $repayment_type, $_POST['total_repayable'], $_POST['total_repayable'], $branch_id, $officer_id);

        if (!$invoice_stmt->execute()) {
            throw new Exception("Error creating invoice: " . $invoice_stmt->error);
        }
        $loan_id = $conn->insert_id;
        $invoice_stmt->close();

        // 3. Generate Repayment Schedule if EMI
        if ($repayment_type === 'emi') {
            $schedule = LoanCalculator::generateEMISchedule((float)$_POST['principal_amount'], (float)$_POST['interest_rate'], (int)$_POST['loan_tenure'], $_POST['loan_date']);

            $schedule_stmt = $conn->prepare("INSERT INTO repayment_schedule (loan_id, due_date, installment_amount, principal_component, interest_component, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            if (!$schedule_stmt) throw new Exception("Schedule statement preparation failed: " . $conn->error);

            foreach ($schedule as $installment) {
                $schedule_stmt->bind_param("isddd", $loan_id, $installment['due_date'], $installment['installment_amount'], $installment['principal_component'], $installment['interest_component']);
                if (!$schedule_stmt->execute()) {
                    throw new Exception("Error adding repayment schedule: " . $schedule_stmt->error);
                }
            }
            $schedule_stmt->close();
        }

        // 4. Insert Collateral Items
        if (isset($_POST['items'])) {
            $collateral_stmt = $conn->prepare("INSERT INTO collateral_items (loan_id, item_description, weight_grams, purity_karat, storage_location, locker_number) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$collateral_stmt) throw new Exception("Collateral statement preparation failed: " . $conn->error);

            foreach ($_POST['items'] as $item) {
                $locker_number = !empty($item['locker_number']) ? $item['locker_number'] : NULL;
                $collateral_stmt->bind_param("isdiss", $loan_id, $item['description'], $item['weight'], $item['purity'], $item['location'], $locker_number);
                if (!$collateral_stmt->execute()) {
                    throw new Exception("Error adding collateral: " . $collateral_stmt->error);
                }
            }
            $collateral_stmt->close();
        }

        // 5. Record Disbursement Transaction
        $trans_stmt = $conn->prepare("INSERT INTO transactions (loan_id, transaction_type, amount, transaction_date) VALUES (?, 'disbursement', ?, NOW())");
        if (!$trans_stmt) throw new Exception("Transaction statement preparation failed: " . $conn->error);

        $trans_stmt->bind_param("id", $loan_id, $_POST['principal_amount']);
        if (!$trans_stmt->execute()) {
            throw new Exception("Error recording transaction: " . $trans_stmt->error);
        }
        $trans_stmt->close();

        // If all queries succeeded, commit the transaction
        $conn->commit();
        $message = "Loan application created successfully! Loan Number: " . htmlspecialchars($loan_number);

    } catch (Exception $e) {
        // If any query fails, roll back the transaction
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch branches for dropdown
$branches_result = $conn->query("SELECT id, branch_name FROM branches");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Loan Application - Gold Loan Management</title>
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
                    <h1 class="h2">New Loan Application</h1>
                </div>

                <?php if($message): ?>
                    <div class="alert <?php echo (strpos($message, 'Error') === false) ? 'alert-success' : 'alert-danger'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form id="loan-form" action="new_loan.php" method="post">
                    <!-- Step 1: Customer Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Step 1: Customer Details</h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                     <label for="search_contact" class="form-label">Search Existing Client by Contact Number</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="search_contact" placeholder="Enter contact number...">
                                        <button class="btn btn-outline-secondary" type="button" id="search-client-btn">Search</button>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button class="btn btn-outline-primary" type="button" id="new-client-btn">Or, Create New Client</button>
                                </div>
                            </div>
                            <div id="client-details-form" style="display: none;">
                                <input type="hidden" id="client_id" name="client_id">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="client_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="client_name" name="client_name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contact_number" class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" id="contact_number" name="contact_number" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="id_proof_type" class="form-label">ID Proof Type</label>
                                        <input type="text" class="form-control" id="id_proof_type" name="id_proof_type" placeholder="e.g., Passport, Driver's License" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="id_proof_number" class="form-label">ID Proof Number</label>
                                        <input type="text" class="form-control" id="id_proof_number" name="id_proof_number" required>
                                    </div>
                                </div>
                            </div>
                             <div id="client-search-result" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Step 2: Gold Collateral Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Step 2: Gold Collateral Details</h4>
                        </div>
                        <div class="card-body">
                            <table class="table" id="collateral-table">
                                <thead>
                                    <tr>
                                        <th>Item Description</th>
                                        <th>Weight (grams)</th>
                                        <th>Purity (karat)</th>
                                        <th>Storage Location</th>
                                        <th>Locker No. (Optional)</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="collateral-items-body">
                                    <!-- Dynamic rows will be added here -->
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-secondary" id="add-item-btn">Add Item</button>
                        </div>
                    </div>

                    <!-- Step 3: Loan Terms -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Step 3: Loan Terms</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="repayment_type" class="form-label">Repayment Type</label>
                                    <select class="form-control" id="repayment_type" name="repayment_type" required>
                                        <option value="bullet">Bullet Repayment</option>
                                        <option value="emi">Equated Monthly Installment (EMI)</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="principal_amount" class="form-label">Principal Amount ($)</label>
                                    <input type="number" step="0.01" class="form-control" id="principal_amount" name="principal_amount" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="interest_rate" class="form-label">Interest Rate (%)</label>
                                    <input type="number" step="0.01" class="form-control" id="interest_rate" name="interest_rate" required>
                                </div>
                                 <div class="col-md-4 mb-3">
                                    <label for="loan_tenure" class="form-label">Loan Tenure (Months)</label>
                                    <input type="number" class="form-control" id="loan_tenure" name="loan_tenure" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="loan_date" class="form-label">Loan Date</label>
                                    <input type="date" class="form-control" id="loan_date" name="loan_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="due_date" class="form-label">Due Date</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" readonly>
                                </div>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="branch_id" class="form-label">Branch</label>
                                    <select class="form-control" id="branch_id" name="branch_id" required>
                                        <?php while($branch = $branches_result->fetch_assoc()): ?>
                                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>
                            <hr>
                            <div id="calculation-results">
                                <h4>Calculated Repayable Amount: <span id="total-repayable-display">$0.00</span></h4>
                                <h5 id="emi-display-container" style="display: none;">Monthly EMI: <span id="emi-display">$0.00</span></h5>
                            </div>
                            <input type="hidden" id="total_repayable" name="total_repayable">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">Submit Loan Application</button>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>
</html>
