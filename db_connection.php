<?php
// Configurações de conexão com o banco de dados
$servername = "companythrivepeak.database.windows.net";
$username = "FilipaBrito";
$password = "Filipa1602#";
$dbname = "company";

try {
    // Conexão PDO
    $conn = new PDO("sqlsrv:server=$servername;database=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Sem saída ou mensagens aqui
} catch (PDOException $e) {
    // Use die() ou echo somente se necessário (para debug)
    die("Connection failed: " . $e->getMessage());
}
?>


