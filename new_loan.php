<?php
require_once 'includes/auth.php';
check_login();
require_once 'db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Begin transaction
    $conn->begin_transaction();

    try {
        // 1. Insert Client
        $client_name = $_POST['client_name'];
        $contact_number = $_POST['contact_number'];
        $address = $_POST['address'];
        $id_proof_type = $_POST['id_proof_type'];
        $id_proof_number = $_POST['id_proof_number'];

        // Handle ID Proof Upload
        $id_proof_path = '';
        if (isset($_FILES['id_proof_file']) && $_FILES['id_proof_file']['error'] == 0) {
            $target_dir = "uploads/id_proofs/";

            // Create directory if it doesn't exist
            if (!file_exists($target_dir) && !mkdir($target_dir, 0777, true) && !is_dir($target_dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $target_dir));
            }

            $file_info = pathinfo($_FILES["id_proof_file"]["name"]);
            $file_extension = strtolower($file_info['extension']);
            $safe_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $safe_filename;

            // Check file size (e.g., 5MB limit)
            if ($_FILES["id_proof_file"]["size"] > 5000000) {
                throw new Exception("Sorry, your file is too large. Maximum size is 5MB.");
            }

            // Allow certain file formats
            $allowed_extensions = ["jpg", "jpeg", "png", "pdf"];
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception("Sorry, only JPG, JPEG, PNG & PDF files are allowed.");
            }

            if (move_uploaded_file($_FILES["id_proof_file"]["tmp_name"], $target_file)) {
                $id_proof_path = $target_file;
            } else {
                throw new Exception("Sorry, there was an error uploading your file.");
            }
        } else {
            throw new Exception("ID proof file is required and must be uploaded without errors.");
        }

        $client_sql = "INSERT INTO clients (name, contact_number, address, id_proof_type, id_proof_number, id_proof_path) VALUES ('$client_name', '$contact_number', '$address', '$id_proof_type', '$id_proof_number', '$id_proof_path')";
        if (!$conn->query($client_sql)) {
            throw new Exception("Error creating client: " . $conn->error);
        }
        $client_id = $conn->insert_id;

        // 2. Insert Invoice (Loan)
        $loan_number = 'GL-' . date('Ymd') . '-' . $client_id;
        $loan_date = $_POST['loan_date'];
        $due_date = $_POST['due_date'];
        $principal_amount = $_POST['principal_amount'];
        $interest_rate = $_POST['interest_rate'];
        $total_repayable = $_POST['total_repayable'];
        $branch_id = $_SESSION['role'] === 'admin' ? $_POST['branch_id'] : 1; // Example branch logic
        $officer_id = $_SESSION['user_id'];

        $invoice_sql = "INSERT INTO invoices (loan_number, client_id, loan_date, due_date, principal_amount, interest_rate, total_repayable, outstanding_amount, status, branch_id, officer_id) VALUES ('$loan_number', $client_id, '$loan_date', '$due_date', $principal_amount, $interest_rate, $total_repayable, $total_repayable, 'active', $branch_id, $officer_id)";
        if (!$conn->query($invoice_sql)) {
            throw new Exception("Error creating invoice: " . $conn->error);
        }
        $loan_id = $conn->insert_id;

        // 3. Insert Collateral Items
        if (isset($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                $desc = $conn->real_escape_string($item['description']);
                $weight = $item['weight'];
                $purity = $item['purity'];
                $location = $conn->real_escape_string($item['location']);
                $locker_number = isset($item['locker_number']) && !empty($item['locker_number']) ? "'".$conn->real_escape_string($item['locker_number'])."'" : "NULL";

                $collateral_sql = "INSERT INTO collateral_items (loan_id, item_description, weight_grams, purity_karat, storage_location, locker_number) VALUES ($loan_id, '$desc', $weight, $purity, '$location', $locker_number)";
                if (!$conn->query($collateral_sql)) {
                    throw new Exception("Error adding collateral: " . $conn->error);
                }
            }
        }

        // 4. Record Disbursement Transaction
        $trans_sql = "INSERT INTO transactions (loan_id, transaction_type, amount, transaction_date) VALUES ($loan_id, 'disbursement', $principal_amount, NOW())";
        if (!$conn->query($trans_sql)) {
            throw new Exception("Error recording transaction: " . $conn->error);
        }

        // If all queries succeeded, commit the transaction
        $conn->commit();
        $message = "Loan application created successfully! Loan Number: " . $loan_number;

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

                <form id="loan-form" action="new_loan.php" method="post" enctype="multipart/form-data">
                    <!-- Step 1: Customer Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Step 1: Customer Details</h4>
                        </div>
                        <div class="card-body">
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
                                <div class="col-md-4 mb-3">
                                    <label for="id_proof_type" class="form-label">ID Proof Type</label>
                                    <select class="form-control" id="id_proof_type" name="id_proof_type" required>
                                        <option value="Aadhar">Aadhar</option>
                                        <option value="Driving License">Driving License</option>
                                        <option value="Voters ID card">Voters ID card</option>
                                        <option value="Passport">Passport</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="id_proof_number" class="form-label">ID Proof Number</label>
                                    <input type="text" class="form-control" id="id_proof_number" name="id_proof_number" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="id_proof_file" class="form-label">Upload ID Proof</label>
                                    <input type="file" class="form-control" id="id_proof_file" name="id_proof_file" required>
                                </div>
                            </div>
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
                                <div class="col-md-6 mb-3">
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
                                            <option value="<?php echo $branch['id']; ?>"><?php echo $branch['branch_name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>
                            <hr>
                            <h4>Calculated Repayable Amount: <span id="total-repayable-display">$0.00</span></h4>
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
