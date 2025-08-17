<?php
require_once 'includes/auth.php';
check_login();
require_once 'db_connect.php';

$message = '';
$loan_id = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['loan_id'], $_POST['payment_amount'], $_POST['payment_date'], $_POST['payment_type'])) {

    $loan_id = (int)$_POST['loan_id'];
    $payment_amount = (float)$_POST['payment_amount'];
    $payment_date = $_POST['payment_date'];
    $payment_type = $_POST['payment_type'];

    $conn->begin_transaction();

    try {
        // Step 1: Record the transaction for all payment types
        $trans_stmt = $conn->prepare("INSERT INTO transactions (loan_id, transaction_type, amount, transaction_date) VALUES (?, ?, ?, ?)");
        $trans_type = 'payment'; // Generic payment type
        $trans_stmt->bind_param("isds", $loan_id, $trans_type, $payment_amount, $payment_date);
        if (!$trans_stmt->execute()) {
            throw new Exception("Error recording transaction: " . $trans_stmt->error);
        }
        $trans_stmt->close();

        // Step 2: Update the loan's outstanding balance
        $update_loan_stmt = $conn->prepare("UPDATE invoices SET outstanding_amount = outstanding_amount - ? WHERE id = ?");
        $update_loan_stmt->bind_param("di", $payment_amount, $loan_id);
        if (!$update_loan_stmt->execute()) {
            throw new Exception("Error updating loan balance: " . $update_loan_stmt->error);
        }
        $update_loan_stmt->close();

        // Step 3: Handle EMI-specific logic
        if ($payment_type === 'installment_payment') {
            // Find the oldest pending installment and mark it as paid
            // For simplicity, this assumes one full installment is paid at a time.
            // A more complex system would handle partial installment payments.
            $update_schedule_stmt = $conn->prepare("UPDATE repayment_schedule SET status = 'paid' WHERE loan_id = ? AND status = 'pending' ORDER BY due_date ASC LIMIT 1");
            $update_schedule_stmt->bind_param("i", $loan_id);
             if (!$update_schedule_stmt->execute()) {
                throw new Exception("Error updating schedule: " . $update_schedule_stmt->error);
            }
            $update_schedule_stmt->close();
        }

        // Step 4: Check if the loan is fully paid
        $check_loan_stmt = $conn->prepare("SELECT outstanding_amount FROM invoices WHERE id = ?");
        $check_loan_stmt->bind_param("i", $loan_id);
        $check_loan_stmt->execute();
        $result = $check_loan_stmt->get_result();
        $loan_data = $result->fetch_assoc();
        $check_loan_stmt->close();

        if ($loan_data && $loan_data['outstanding_amount'] <= 0) {
            $close_loan_stmt = $conn->prepare("UPDATE invoices SET status = 'closed', outstanding_amount = 0 WHERE id = ?");
            $close_loan_stmt->bind_param("i", $loan_id);
            $close_loan_stmt->execute();
            $close_loan_stmt->close();
        }

        $conn->commit();
        $message = "success";

    } catch (Exception $e) {
        $conn->rollback();
        $message = "error";
    }

    header("Location: loan_details.php?id=" . $loan_id . "&payment_status=" . $message);
    exit();

} else {
    // Redirect if accessed directly or with invalid parameters
    header("Location: manage_loans.php");
    exit();
}
?>
