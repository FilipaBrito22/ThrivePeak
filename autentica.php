<?php
include 'db_connection.php';

// Start session
session_start();

// Check if logout is requested
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (!empty($email) && !empty($password)) {
        // Query database for the user
        $sql = "SELECT * FROM employee WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['Password'])) {
                $_SESSION['user_id'] = $user['EmployeeID'];
                $_SESSION['email'] = $user['Email'];
                header("Location: dashboard_company.php");
                exit();
            } else {
                $_SESSION['error_msg'] = "Invalid password.";
            }
        } else {
            $_SESSION['error_msg'] = "User not found.";
        }
    } else {
        $_SESSION['error_msg'] = "Email and password are required.";
    }

    // Redirect back with error
    header("Location: index.php");
    exit();
}

// Close connection
$conn->close();
?>
