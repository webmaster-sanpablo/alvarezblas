<?php
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'galeria_fotos';

    $conn = new mysqli($host, $user, $password, $database);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
?>