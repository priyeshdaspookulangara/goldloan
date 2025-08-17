<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request'];

if (isset($_GET['contact_number'])) {
    $contact_number = $_GET['contact_number'];

    $stmt = $conn->prepare("SELECT * FROM clients WHERE contact_number = ?");
    $stmt->bind_param("s", $contact_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($client = $result->fetch_assoc()) {
        $response = [
            'status' => 'success',
            'client' => $client
        ];
    } else {
        $response = ['status' => 'error', 'message' => 'Client not found.'];
    }
    $stmt->close();
}

echo json_encode($response);
?>
