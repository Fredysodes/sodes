<?php
session_start();
include('../../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'] ?? null;
    $total_venta = $_POST['total_venta'] ?? null;
    $metodo_pago = $_POST['metodo_pago'] ?? null;
    $detalles = json_decode($_POST['detalles'] ?? '[]', true);
    $id_usuario = $_SESSION['id_usuario'] ?? 1;
    $id_dian = 1;
    $fecha = date('Y-m-d H:i:s');

    if (!$id_cliente || !$total_venta || !$metodo_pago || empty($detalles)) {
        header("Location: nueva_venta.php?error=missing_data");
        exit();
    }

    $conn->begin_transaction();
    try {
        // Insertar factura
        $stmt = $conn->prepare("INSERT INTO facturacion (id_cliente, fecha, total, id_usuario, id_dian, id_metodo_pago) VALUES (?, ?, ?, ?, ?, ?)");
        $id_metodo_pago = ($metodo_pago === 'efectivo') ? 1 : (($metodo_pago === 'tarjeta') ? 2 : 3);
        $stmt->bind_param("isdiii", $id_cliente, $fecha, $total_venta, $id_usuario, $id_dian, $id_metodo_pago);
        $stmt->execute();
        $id_factura = $stmt->insert_id;
        $stmt->close();

        // Detalles de factura y actualización de stock
        foreach ($detalles as $d) {
            $codigo = $d['codigo'];
            $cantidad = (int)$d['cantidad'];
            $precio_unitario = (float)$d['precio'];
            $total = (float)$d['subtotal'];

            // Obtener información del producto
            $q = $conn->prepare("SELECT id_producto, Iva, stock FROM productos WHERE CodBarras = ?");
            $q->bind_param("s", $codigo);
            $q->execute();
            $r = $q->get_result()->fetch_assoc();
            $q->close();

            if ($r) {
                $id_producto = $r['id_producto'];
                $iva = $r['Iva'];
                $stock = $r['stock'];

                if ($cantidad > $stock) {
                    throw new Exception("Stock insuficiente para el producto ID $id_producto.");
                }

                // Insertar detalle en la factura
                $ins = $conn->prepare("INSERT INTO detalles_factura (id_factura, id_producto, cantidad, precio_unitario, id_impuesto, total) VALUES (?, ?, ?, ?, ?, ?)");
                $ins->bind_param("iiidid", $id_factura, $id_producto, $cantidad, $precio_unitario, $iva, $total);
                $ins->execute();
                $ins->close();

                // Actualizar stock
                $upd = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
                $upd->bind_param("ii", $cantidad, $id_producto);
                $upd->execute();
                $upd->close();
            } else {
                throw new Exception("Producto no encontrado: código $codigo.");
            }
        }

        $conn->commit();

        // Redirigir al archivo que genera la factura
        header("Location: generar_factura.php?id_factura=$id_factura");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transacción fallida: " . $e->getMessage());
        header("Location: nueva_venta.php?error=transaction_failed");
        exit();
    }
}
?>