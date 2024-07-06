<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appoinment_form";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve date and slot from POST request
$date = htmlspecialchars($_POST['date']);
$slot = htmlspecialchars($_POST['slots']);

// Prepare and execute the query to check slot availability
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM form_submissions WHERE date = ? AND slots = ?");
$stmt->bind_param("ss", $date, $slot);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo 'unavailable';
} else {
    echo 'available';
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
