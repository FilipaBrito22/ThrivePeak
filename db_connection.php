<?php
$serverName = "companythrivepeak.database.windows.net"; // Your Azure SQL server name
$database = "company"; // The name of your database
$username = "FilipaBrito"; // The username you set for your Azure SQL database
$password = "Filipa1602#"; // The password you set for your Azure SQL database

try {
    $conn = new PDO("sqlsrv:server=$serverName;Database=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>

