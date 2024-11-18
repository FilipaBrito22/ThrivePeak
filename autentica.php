<?php
// Configurar exibição de erros (somente para debug, remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sempre inicie a sessão no topo
session_start();

// Inclua a conexão PDO
include 'db_connection.php';

// Verificar se o logout foi solicitado
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Inicialize a mensagem de erro
$error_msg = "";

// Verifique se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtenha os dados do formulário
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepara a consulta SQL
    $sql = "SELECT * FROM employee WHERE email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':email', $email);

    try {
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verifica a senha
            if (password_verify($password, $user['Password'])) {
                // Configura as variáveis de sessão
                $_SESSION['user_id'] = $user['EmployeeID'];
                $_SESSION['email'] = $user['Email'];

                // Redireciona para o dashboard
                header("Location: dashboard_company.php");
                exit();
            } else {
                $error_msg = "Senha inválida.";
            }
        } else {
            $error_msg = "Usuário não encontrado.";
        }
    } catch (PDOException $e) {
        $error_msg = "Erro ao acessar o banco de dados: " . $e->getMessage();
    }

    // Redireciona de volta para o login com a mensagem de erro
    $_SESSION['error_msg'] = $error_msg;
    header("Location: index.php");
    exit();
}

// Feche a conexão PDO
$conn = null;
?>
