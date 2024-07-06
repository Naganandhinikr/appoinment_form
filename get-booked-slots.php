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

// Retrieve date from POST request
$date = htmlspecialchars($_POST['date']);

// Prepare and execute the query to get booked slots for the date
$stmt = $conn->prepare("SELECT slots FROM form_submissions WHERE date = ?");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$bookedSlots = [];
while ($row = $result->fetch_assoc()) {
    $bookedSlots[] = $row['slots'];
}

$allSlots = ["10am to 11am", "11am to 12pm", "12pm to 1pm", "1pm to 2pm"];
$isFullyBooked = count(array_intersect($allSlots, $bookedSlots)) === count($allSlots);

$response = [
    'bookedSlots' => $bookedSlots,
    'isFullyBooked' => $isFullyBooked
];

echo json_encode($response);

// Close the statement and connection
$stmt->close();
$conn->close();
?>
