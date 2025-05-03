<?php
include('../../config/db.php');

if (isset($_GET['codigo'])) {
    $codigo = $_GET['codigo'];

    // Buscar producto por CodBarras para compras
    $stmt = $conn->prepare("SELECT CodBarras, nombre, Pdcompra, Iva FROM productos WHERE CodBarras = ?");
    if (!$stmt) {
        die("Error en prepare: " . $conn->error);
    }
    $stmt->bind_param("i", $codigo); // "s" porque CodBarras puede ser texto
    $stmt->execute();
    $result = $stmt->get_result();

    if ($producto = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'producto' => $producto
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Producto no encontrado para compra.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'No se recibió código de barras para compra.'
    ]);
}
?>