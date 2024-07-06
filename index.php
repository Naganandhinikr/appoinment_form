<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and collect input data
    $firstname = htmlspecialchars($_POST['firstname']);
    $lastname = htmlspecialchars($_POST['lastname']);
    $email = htmlspecialchars($_POST['email']);
    $mobile_number = htmlspecialchars($_POST['mobile']);
    $location = htmlspecialchars($_POST['location']);
    $date = htmlspecialchars($_POST['date']);
    $slots = htmlspecialchars($_POST['slots']);
    $looking_for = htmlspecialchars($_POST['looking']);
    $image = $_FILES['image'];
    $message = htmlspecialchars($_POST['message']);

    // Validate and process image upload
    $target_dir = "images/";
    $target_file = $target_dir . basename($image["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is an actual image or fake image
    $check = getimagesize($image["tmp_name"]);
    if ($check === false) {
        die("File is not an image.");
    }

    // Check file size (5MB max)
    if ($image["size"] > 5000000) {
        die("Sorry, your file is too large.");
    }

    // Allow certain file formats
    $allowed_types = array("jpg", "jpeg", "png", "gif");
    if (!in_array($imageFileType, $allowed_types)) {
        die("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
    }

    // Check if file already exists
    if (file_exists($target_file)) {
        die("Sorry, file already exists.");
    }

    // Attempt to upload file
    if (!move_uploaded_file($image["tmp_name"], $target_file)) {
        die("Sorry, there was an error uploading your file.");
    }

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

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO form_submissions (firstname, lastname, email, mobile_number, location, date, slots, looking_for, image, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $firstname, $lastname, $email, $mobile_number, $location, $date, $slots, $looking_for, $target_file, $message);

    // Execute the statement
    if ($stmt->execute()) {
        echo "Data inserted successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appoinment Booking</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="shortcut icon" type="img" href="img/EVVI_Icon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script>
    $(function() {
        var disabledDates = [];

        function fetchAndDisableSlots(date) {
            $.ajax({
                url: 'get-booked-slots.php',
                method: 'POST',
                data: { date: date },
                success: function(response) {
                    let data = JSON.parse(response);
                    let bookedSlots = data.bookedSlots;
                    let isFullyBooked = data.isFullyBooked;

                    if (isFullyBooked) {
                        // Add the fully booked date to the disabledDates array
                        if (!disabledDates.includes(date)) {
                            disabledDates.push(date);
                        }
                    } else {
                        // Enable the date and disable only the booked slots
                        $('select[name="slots"] option').each(function() {
                            let slotValue = $(this).val();
                            if (bookedSlots.includes(slotValue)) {
                                $(this).prop('disabled', true);
                            } else {
                                $(this).prop('disabled', false);
                            }
                        });
                    }

                    // Reinitialize the datepicker to reflect disabled dates
                    $("#datepicker").datepicker("refresh");
                },
                error: function(error) {
                    console.error('Error fetching booked slots:', error);
                }
            });
        }

        function fetchFullyBookedDates() {
            $.ajax({
                url: 'get-fully-booked-dates.php', // Endpoint to fetch all fully booked dates
                method: 'GET',
                success: function(response) {
                    let data = JSON.parse(response);
                    disabledDates = data;

                    // Reinitialize the datepicker to reflect disabled dates
                    $("#datepicker").datepicker("refresh");
                },
                error: function(error) {
                    console.error('Error fetching fully booked dates:', error);
                }
            });
        }

        // Initialize datepicker
        $("#datepicker").datepicker({
            dateFormat: "yy-mm-dd",
            beforeShowDay: function(date) {
                let dateString = $.datepicker.formatDate("yy-mm-dd", date);
                // Disable the date if it's in the disabledDates array
                return [disabledDates.indexOf(dateString) === -1];
            },
            onSelect: function(dateText) {
                fetchAndDisableSlots(dateText);
            }
        });

        // Fetch fully booked dates when the page loads
        fetchFullyBookedDates();

        // Form submission handling
        $('form').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            let date = $('#datepicker').val();
            let slots = $('select[name="slots"]').val();

            // Check slot availability before submitting
            $.ajax({
                url: 'check-slot-availability.php',
                method: 'POST',
                data: { date: date, slots: slots },
                success: function(response) {
                    if (response === 'available') {
                        // Proceed with form submission including image upload
                        let formData = new FormData($('form')[0]); // Get all form data including the image

                        $.ajax({
                            url: '', // Your PHP script to handle form submission
                            method: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(response) {
                                alert('Data inserted successfully');
                                location.reload(); // Reload the page to clear the form
                            },
                            error: function(error) {
                                console.error('Error submitting the form:', error);
                            }
                        });
                    } else {
                        alert('The selected slot is already booked. Please choose a different slot.');
                    }
                },
                error: function(error) {
                    console.error('Error checking slot availability:', error);
                }
            });
        });

        // Enable submit button only when a valid slot is selected
        $('select[name="slots"]').on('change', function() {
            let selectedSlot = $(this).val();
            if (selectedSlot) {
                $('button[type="submit"]').prop('disabled', false);
            } else {
                $('button[type="submit"]').prop('disabled', true);
            }
        });
    });
</script>


 

</head>
<body class="m-5">
    <section class="bg-p">
        <div class="container py-3">
            <div class="contact-details text-center text-light">
                <h3>Contact Details</h3>
                <p><strong>Mail Us:</strong> enrich.evvi@gmail.com</p>
                <p><strong>Location:</strong> Bangalore , Karnataka</p>
                <p><strong>Work hours:</strong> Monday-Friday 8:00AM-8:00PM<br>Sunday-closed</p>
            </div>
        </div>
    </section>
    <section class="form-details bg-war pt-2 pb-2">
        <div class="container mt-5">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="validationFirstname" class="form-label"><b>First name <span class="text-danger">*</span></b></label>
                        <input type="text" class="form-control rounded-input" id="validationFirstname" name="firstname" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationLastname" class="form-label"><b>Last name <span class="text-danger">*</span></b></label>
                        <input type="text" class="form-control rounded-input" id="validationLastname" name="lastname" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationEmail" class="form-label"><b>Email <span class="text-danger">*</span></b></label>
                        <input type="email" class="form-control rounded-input" id="validationEmail" name="email" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationdob" class="form-label"><b>Mobile Number <span class="text-danger">*</span></b></label>
                        <input type="text" class="form-control rounded-input" id="validationdob" name="mobile" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label" for="location"><b>Enter Location <span class="text-danger">*</span></b></label>
                        <input type="text" class="form-control rounded-input" name="location" required />
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="datepicker" class="form-label"><b>Select Date <span class="text-danger">*</span></b></label>
                        <input type="text" class="form-control rounded-input" id="datepicker" name="date" required>
                    </div>
                    <div class="col-md-3 mb-4">
                        <label class="form-label" for="slots"><b>Available Slots <span class="text-danger">*</span></b></label>
                        <select class="form-select form-control rounded-input" name="slots" required>
                            <option value="" selected disabled></option>
                            <option value="10am to 11am">10am to 11am</option>
                            <option value="11am to 12pm">11am to 12pm</option>
                            <option value="12pm to 1pm">12pm to 1pm</option>
                            <option value="1pm to 2pm">1pm to 2pm</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label" for="looking"><b>Looking for? <span class="text-danger">*</span></b></label>
                        <select class="form-select form-control rounded-input" name="looking" required>
                            <option value="" selected disabled></option>
                            <option value="Corporate training">Corporate training</option>
                            <option value="Psychometric Counselling">Psychometric Counselling</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label for="image" class="form-label"><b>Image Upload <span class="text-danger">*</span></b></label>
                        <div class="custom-file">
                            <label class="custom-file-label" for="image">Choose file</label>
                            <input type="file" class="custom-file-input rounded-input" id="image" name="image" required>
                        </div>
                        <!-- <label for="image" class="form-label"><b>Image Upload <span class="text-danger">*</span></b></label>
                        <input class="form-control rounded-input" type="file" id="image" name="image" required /> -->
                    </div>
                    <div class="col-sm-12 mb-4">
                        <label for="message" class="form-label"><b>Message <span class="text-danger">*</span></b></label>
                        <textarea class="form-control" id="message" name="message" rows="3" required placeholder="What purpose"></textarea>
                    </div>
                    <div class="col-md-12">
                        <button class="btn bg-p text-light" type="submit">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
    <footer class="footer">
        <div class="container">
            <div class="row">
            <div class="col-md-6">
                <h5>Contact Details</h5>
                <p><strong>Call Us:</strong> +2 392 3929 210</p>
                <p><strong>Location:</strong> San Francisco, California, USA</p>
                <p><strong>Work hours:</strong> Monday-Friday 8:00AM-8:00PM<br>Sunday-closed</p>
            </div>
            <div class="col-md-6">
                <h5>Subscribe to our Newsletter</h5>
                <form>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Enter your email" required>
                        <div class="input-group-append">
                            <button class="btn btn-war" type="submit">Subscribe</button>
                        </div>
                    </div>
                </form>
                <div class="social-icons mt-3">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>
    </div>
    </footer>
    <!-- <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> -->
<!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script> -->
</body>
</html>
