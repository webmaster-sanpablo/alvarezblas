<?php
include 'cn/conexion.php';
header('Content-Type: application/json');

// Buscar fotos con paginación (para la galería principal)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buscar'])) {
    $busqueda = $_GET['buscar'];
    $pagina = max(1, intval($_GET['pagina'] ?? 1)); // Asegurar que sea número positivo
    $porPagina = 6;

    // Consulta para obtener las fotos
    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM fotos 
            WHERE (nombre LIKE ? OR descripcion LIKE ?)
            LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $paramBusqueda = "%".$conn->real_escape_string($busqueda)."%";
    $offset = ($pagina - 1) * $porPagina;
    $stmt->bind_param("ssii", $paramBusqueda, $paramBusqueda, $offset, $porPagina);
    $stmt->execute();
    $fotos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Obtener el total de fotos (para paginación)
    $totalFotos = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];

    echo json_encode([
        'fotos' => $fotos,
        'totalFotos' => $totalFotos,
        'encontrado' => !empty($fotos) // true/false si hay resultados
    ]);
}

// Obtener foto por ID (para la página de detalle)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM fotos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Foto no encontrada']);
    } else {
        echo json_encode($result->fetch_assoc());
    }
}

// Obtener fotos aleatorias (para la sección "Recomendaciones")
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['aleatorias'])) {
    $limit = min(10, intval($_GET['aleatorias'])); //Acepta hasta 10 imágenes
    $excludeId = isset($_GET['exclude']) ? intval($_GET['exclude']) : 0;
    $sql = "SELECT * FROM fotos WHERE estado = 'disponible'";
    if ($excludeId > 0) {
        $sql .= " AND id != $excludeId";
    }
    $sql .= " ORDER BY RAND() LIMIT $limit";
    $result = $conn->query($sql);
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
}

// Si no coincide ninguna ruta
else {
    http_response_code(400);
    echo json_encode(['error' => 'Petición inválida']);
}
?>