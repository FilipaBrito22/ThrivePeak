if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Debug: Display the captured form data
    var_dump($_POST); // Show the submitted form data
    exit(); // Stop execution to analyze

    if (!empty($email) && !empty($password)) {
        // Query database for the user
        $sql = "SELECT * FROM employee WHERE email = '$email'";
        $result = $conn->query($sql);

        // Debug: Display the number of rows returned
        var_dump($result->num_rows); // Show the number of rows
        exit(); // Stop execution to analyze

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

    // Redirect back with an error message
    header("Location: index.php");
    exit();
}
