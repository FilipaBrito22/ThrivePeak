<?php
// Configurações de conexão com o banco de dados
$servername = "companythrivepeak.database.windows.net";
$username = "FilipaBrito";
$password = "Filipa1602#";
$dbname = "company";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
?>


