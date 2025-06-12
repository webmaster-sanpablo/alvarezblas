<?php
    $host = 'localhost';
    $user = 'backend_developer';
    $password = 'UMS$H]2&94HV';
    $database = 'galeria_fotos';

    $conn = new mysqli($host, $user, $password, $database);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
?>