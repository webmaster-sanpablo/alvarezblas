<?php
// Mostrar errores en entorno de desarrollo (quítalo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cabecera para indicar respuesta JSON
header('Content-Type: application/json');

// Datos de conexión
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'galeria_fotos';

try {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        throw new Exception("Conexión fallida: " . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buscar'])) {
        $busqueda = trim($_GET['buscar']);
        $pagina = max(1, intval($_GET['pagina'] ?? 1));
        $porPagina = 6;
        $offset = ($pagina - 1) * $porPagina;

        // Base de la consulta
        $sql = "SELECT id, ruta, nombre, descripcion, precio, estado FROM fotos";
        $params = [];
        $types = "";

        if (!empty($busqueda)) {
            $sql .= " WHERE (nombre LIKE ? OR descripcion LIKE ?)";
            $searchTerm = "%$busqueda%";
            $params = [$searchTerm, $searchTerm];
            $types = "ss";
        }

        $sql .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $porPagina;
        $types .= "ii";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $fotos = $result->fetch_all(MYSQLI_ASSOC);

        // Contar total
        $countSql = "SELECT COUNT(*) FROM fotos";
        if (!empty($busqueda)) {
            $countSql .= " WHERE (nombre LIKE ? OR descripcion LIKE ?)";
            $stmtCount = $conn->prepare($countSql);
            $stmtCount->bind_param("ss", $searchTerm, $searchTerm);
        } else {
            $stmtCount = $conn->prepare($countSql);
        }

        $stmtCount->execute();
        $stmtCount->bind_result($total);
        $stmtCount->fetch();
        $stmtCount->close();

        // Construir URLs completas y forzar UTF-8 para evitar errores en json_encode
        foreach ($fotos as &$foto) {
            foreach ($foto as $key => $value) {
                if (is_string($value)) {
                    $foto[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
                }
            }
            $foto['url_completa'] = $foto['ruta'];
        }

        // Codificar y mostrar JSON
        $json = json_encode([
            'success' => true,
            'data' => $fotos,
            'paginacion' => [
                'total' => $total,
                'pagina' => $pagina,
                'por_pagina' => $porPagina
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            echo json_encode([
                'success' => false,
                'error' => 'Fallo en json_encode',
                'json_error' => json_last_error_msg()
            ]);
        } else {
            echo $json;
        }

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
