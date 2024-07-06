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

// Query to get fully booked dates
$allSlots = ["10am to 11am", "11am to 12pm", "12pm to 1pm", "1pm to 2pm"];
$fullyBookedDates = [];

$query = "SELECT date FROM form_submissions GROUP BY date HAVING COUNT(DISTINCT slots) = ?";
$stmt = $conn->prepare($query);
$numSlots = count($allSlots);
$stmt->bind_param("i", $numSlots);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $fullyBookedDates[] = $row['date'];
}

echo json_encode($fullyBookedDates);

// Close the statement and connection
$stmt->close();
$conn->close();
?>
