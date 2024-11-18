if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Debug: Exibir os valores capturados do formulário
    var_dump($_POST); // Exibe os dados enviados pelo formulário
    exit(); // Interrompe a execução aqui para análise

    if (!empty($email) && !empty($password)) {
        // Query database for the user
        $sql = "SELECT * FROM employee WHERE email = '$email'";
        $result = $conn->query($sql);

        // Debug: Exibir resultado da consulta
        var_dump($result->num_rows); // Exibe o número de linhas retornadas
        exit(); // Interrompe a execução aqui para análise

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
