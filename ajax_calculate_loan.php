<?php
require_once 'includes/calculator.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid parameters.'];

if (isset($_POST['principal'], $_POST['rate'], $_POST['tenure'], $_POST['type'])) {
    $principal = (float)$_POST['principal'];
    $annualRate = (float)$_POST['rate'];
    $tenureMonths = (int)$_POST['tenure'];
    $repaymentType = $_POST['type'];

    $totalRepayable = 0;
    $emiAmount = 0;

    try {
        if ($repaymentType === 'bullet') {
            $totalRepayable = LoanCalculator::calculateBulletRepayment($principal, $annualRate, $tenureMonths);
        } elseif ($repaymentType === 'emi') {
            $emiAmount = LoanCalculator::calculateEMI($principal, $annualRate, $tenureMonths);
            // For EMI, the total repayable is simply the sum of all EMIs
            $totalRepayable = $emiAmount * $tenureMonths;
        } else {
             throw new Exception("Invalid repayment type specified.");
        }

        $response = [
            'status' => 'success',
            'total_repayable' => round($totalRepayable, 2),
            'emi_amount' => round($emiAmount, 2)
        ];

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);
?>
