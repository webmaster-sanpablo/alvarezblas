<?php
    $host = 'localhost';
    $user = 'backend_developer';
    $password = 'Casita123!';
    $database = 'galeria_fotos';

    $conn = new mysqli($host, $user, $password, $database);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
?>