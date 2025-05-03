<?php
include('../../config/db.php');

// Recibir NIT del cliente
if (isset($_GET['nit'])) {
    $nit = $_GET['nit'];

    // Buscar cliente por NIT
    $stmt = $conn->prepare("SELECT id_cliente, nombre FROM clientes WHERE id_cliente = ?");
    if (!$stmt) {
        die(json_encode(['success' => false, 'mensaje' => 'Error al preparar consulta: ' . $conn->error]));
    }
    $stmt->bind_param("i", $nit); // NIT es número entero
    $stmt->execute();
    $result = $stmt->get_result();

    if ($cliente = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'cliente' => $cliente
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Cliente no encontrado'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'No se recibió NIT para búsqueda'
    ]);
}
?>
