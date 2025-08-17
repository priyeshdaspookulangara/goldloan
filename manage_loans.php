<?php
require_once 'includes/auth.php';
check_login();
require_once 'db_connect.php';

// Handle Delete Action First
if (isset($_GET['delete'])) {
    $loan_id_to_delete = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
    $stmt->bind_param("i", $loan_id_to_delete);
    if ($stmt->execute()) {
        header("Location: manage_loans.php?deleted=true");
        exit();
    } else {
        // It's good practice to handle potential errors
        die("Error deleting record: " . $stmt->error);
    }
    $stmt->close();
}

// Base query
$sql = "SELECT i.id, i.loan_number, c.name as client_name, i.principal_amount, i.outstanding_amount, i.due_date, i.status, b.branch_name, i.repayment_type
        FROM invoices i
        JOIN clients c ON i.client_id = c.id
        JOIN branches b ON i.branch_id = b.id";

// Dynamic Filtering
$where_clauses = [];
$params = [];
$types = '';

if (!empty($_GET['loan_number'])) {
    $where_clauses[] = "i.loan_number LIKE ?";
    $params[] = "%" . $_GET['loan_number'] . "%";
    $types .= 's';
}
if (!empty($_GET['client_name'])) {
    $where_clauses[] = "c.name LIKE ?";
    $params[] = "%" . $_GET['client_name'] . "%";
    $types .= 's';
}
if (!empty($_GET['status'])) {
    $where_clauses[] = "i.status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY i.loan_date DESC";

$stmt = $conn->prepare($sql);

if (!empty($types) && count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Loans - Gold Loan Management</title>
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
                    <h1 class="h2">Manage Loans</h1>
                </div>

                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-header">Filter Loans</div>
                    <div class="card-body">
                        <form action="manage_loans.php" method="get" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="loan_number" class="form-control" placeholder="Loan Number" value="<?php echo htmlspecialchars($_GET['loan_number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="client_name" class="form-control" placeholder="Client Name" value="<?php echo htmlspecialchars($_GET['client_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?php echo (($_GET['status'] ?? '') == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="closed" <?php echo (($_GET['status'] ?? '') == 'closed') ? 'selected' : ''; ?>>Closed</option>
                                    <option value="overdue" <?php echo (($_GET['status'] ?? '') == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if(isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">Loan successfully deleted.</div>
                <?php endif; ?>

                <!-- Loans Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Loan Number</th>
                                <th>Client Name</th>
                                <th>Principal Amount</th>
                                <th>Outstanding</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Repayment Type</th>
                                <th>Branch</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['loan_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                        <td>$<?php echo number_format($row['principal_amount'], 2); ?></td>
                                        <td>$<?php echo number_format($row['outstanding_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                                        <td><span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : ($row['status'] == 'overdue' ? 'danger' : 'secondary'); ?>"><?php echo ucfirst(htmlspecialchars($row['status'])); ?></span></td>
                                        <td><?php echo ucfirst(htmlspecialchars($row['repayment_type'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                                        <td>
                                            <a href="loan_details.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">View</a>
                                            <a href="#" class="btn btn-success btn-sm">Payment</a>
                                            <a href="manage_loans.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this loan?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No loans found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
