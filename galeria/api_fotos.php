<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Datos de conexión
$host = 'localhost';
// $user = 'backend_developer';
// $pass = '@Casita123!';
$user = 'root';
$pass = '';
$db   = 'galeria_fotos';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        throw new Exception("Conexión fallida: " . $conn->connect_error);
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $buscar = trim($_GET['buscar'] ?? '');
    $pagina = max(1, intval($_GET['pagina'] ?? 1));
    $porPagina = 6;
    $offset = ($pagina - 1) * $porPagina;
    $aleatorias = isset($_GET['exclude']) || isset($_GET['aleatorias']);

    // --- 1. Consulta por ID específico ---
    if ($id) {
        $stmt = $conn->prepare("SELECT id, ruta, nombre, descripcion, precio, estado FROM fotos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $foto = $res->fetch_assoc();

        if ($foto) {
            foreach ($foto as $key => $value) {
                if (is_string($value)) {
                    $foto[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
                }
            }
            $foto['url_completa'] = $foto['ruta'];

            $json = json_encode(['success' => true, 'data' => [$foto]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Error al codificar JSON',
                    'json_error' => json_last_error_msg()
                ]);
            } else {
                echo $json;
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Foto no encontrada']);
        }
        exit;
    }


    // --- 2. Recomendaciones aleatorias excluyendo una ID ---
    if ($aleatorias) {
        $limit = intval($_GET['aleatorias'] ?? 10);
        $exclude = intval($_GET['exclude'] ?? 0);

        $sql = "SELECT id, ruta, nombre, descripcion, precio, estado FROM fotos";
        if ($exclude > 0) {
            $sql .= " WHERE id != ?";
        }
        $sql .= " ORDER BY RAND() LIMIT ?";

        $stmt = $conn->prepare($exclude > 0 ? "$sql" : str_replace(" WHERE id != ?", "", $sql));
        if ($exclude > 0) {
            $stmt->bind_param("ii", $exclude, $limit);
        } else {
            $stmt->bind_param("i", $limit);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $fotos = $res->fetch_all(MYSQLI_ASSOC);

        foreach ($fotos as &$foto) {
            foreach ($foto as $key => $value) {
                if (is_string($value)) {
                    $foto[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
                }
            }
            $foto['url_completa'] = $foto['ruta'];
        }

        echo json_encode(['success' => true, 'data' => $fotos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // --- 3. Búsqueda general con paginación ---
    $params = [];
    $types = "";
    $sql = "SELECT id, ruta, nombre, descripcion, precio, estado FROM fotos";

    if (!empty($buscar)) {
        $sql .= " WHERE nombre LIKE ? OR descripcion LIKE ?";
        $search = "%$buscar%";
        $params = [$search, $search];
        $types = "ss";
    }

    $sql .= " LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $porPagina;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $fotos = $res->fetch_all(MYSQLI_ASSOC);

    foreach ($fotos as &$foto) {
        foreach ($foto as $key => $value) {
            if (is_string($value)) {
                $foto[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        }
        $foto['url_completa'] = $foto['ruta'];
    }

    // Conteo total
    $countSql = "SELECT COUNT(*) FROM fotos";
    if (!empty($buscar)) {
        $countSql .= " WHERE nombre LIKE ? OR descripcion LIKE ?";
        $stmtCount = $conn->prepare($countSql);
        $stmtCount->bind_param("ss", $search, $search);
    } else {
        $stmtCount = $conn->prepare($countSql);
    }

    $stmtCount->execute();
    $stmtCount->bind_result($total);
    $stmtCount->fetch();
    $stmtCount->close();

    echo json_encode([
        'success' => true,
        'data' => $fotos,
        'paginacion' => [
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>