<?php
$servername = "thrivepeakcompany.database.windows.net"; // Your Azure SQL server name
$username = "CloudSAc843b116"; // The username you set for your Azure SQL database
$password = "Filipa1602#"; // The password you set for your Azure SQL database
$dbname = "company"; // The name of your database

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
