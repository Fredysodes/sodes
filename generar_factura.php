<?php
require('../../libs/fpdf/fpdf.php');
include('../../config/db.php');

// Obtener ID de la factura
$id_factura = $_GET['id_factura'] ?? null;
if (!$id_factura) {
    die("Error: No se proporcionó el ID de la factura.");
}

// Obtener datos de la factura
$stmt = $conn->prepare("SELECT f.id_factura, f.fecha, f.total, c.nombre AS cliente
                        FROM facturacion f
                        JOIN clientes c ON f.id_cliente = c.id_cliente
                        WHERE f.id_factura = ?");
$stmt->bind_param("i", $id_factura);
$stmt->execute();
$factura = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Obtener detalles de la factura
$stmt = $conn->prepare("SELECT p.nombre AS producto, df.cantidad, df.precio_unitario, df.total
                        FROM detalles_factura df
                        JOIN productos p ON df.id_producto = p.id_producto
                        WHERE df.id_factura = ?");
$stmt->bind_param("i", $id_factura);
$stmt->execute();
$detalles = $stmt->get_result();
$stmt->close();

// Generar PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Encabezado
$pdf->Cell(0, 10, 'Factura #' . $factura['id_factura'], 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Fecha: ' . $factura['fecha'], 0, 1);
$pdf->Cell(0, 10, 'Cliente: ' . $factura['cliente'], 0, 1);

// Detalles de la factura
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(100, 10, 'Producto', 1);
$pdf->Cell(20, 10, 'Cant.', 1);
$pdf->Cell(30, 10, 'P. Unit.', 1);
$pdf->Cell(30, 10, 'Total', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);
while ($row = $detalles->fetch_assoc()) {
    $pdf->Cell(100, 10, $row['producto'], 1);
    $pdf->Cell(20, 10, $row['cantidad'], 1, 0, 'C');
    $pdf->Cell(30, 10, number_format($row['precio_unitario'], 2), 1, 0, 'R');
    $pdf->Cell(30, 10, number_format($row['total'], 2), 1, 0, 'R');
    $pdf->Ln();
}

// Total
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Total: $' . number_format($factura['total'], 2), 0, 1, 'R');

// Mostrar PDF
$pdf->Output();
?>