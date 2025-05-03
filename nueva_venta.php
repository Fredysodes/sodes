            <?php
            session_start();
            if (!isset($_SESSION['usuario'])) {
                header("Location: ../../login/login.php");
                exit();
            }
            include('../../config/db.php');
            ?>

            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Nueva Venta</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
            </head>
            <body>

            <div class="container mt-4">
                <h2 class="text-center mb-4">Registrar Nueva Venta</h2>

                <form id="formVenta" action="guardar_venta.php" method="POST">
                    <!-- Cliente -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="nit_cliente" class="form-label">NIT Cliente</label>
                            <input type="text" id="nit_cliente" class="form-control" placeholder="Digite NIT y Enter" required>
                            <input type="hidden" name="id_cliente" id="id_cliente">
                            <div id="clienteInfo" class="mt-2 text-success fw-bold"></div>
                        </div>
                    </div>

                    <!-- Producto -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="codigo_barras" class="form-label">Código de Barras</label>
                            <input type="text" id="codigo_barras" class="form-control" placeholder="Digite o escanee y presione Enter">
                        </div>
                    </div>

                    <!-- Tabla -->
                    <table class="table table-bordered" id="tablaProductos">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>IVA %</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <!-- Totalizar -->
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-success btn-lg" id="btnTotalizar">F2 - Totalizar</button>
                    </div>

                    <!-- Mostrar total y pagar -->
                    <div id="seccionPago" style="display:none;" class="text-center mt-4">
                        <h3>Total a Pagar: $<span id="totalMostrar">0.00</span></h3>
                        <button type="button" class="btn btn-primary btn-lg mt-3" id="btnPagar">PAGAR</button>
                    </div>

                    <input type="hidden" name="detalles" id="detalles">
                    <input type="hidden" name="total_venta" id="total_venta">
                </form>
            </div>

            <!-- Modal de pago -->
            <div class="modal fade" id="modalPago" tabindex="-1" aria-labelledby="modalPagoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPagoLabel">Realizar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="formMetodoPago">
                    <div class="mb-3">
                        <label class="form-label">Método de Pago</label>
                        <select class="form-select" id="metodo_pago" required>
                        <option value="">Seleccione método de pago</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="mixto">Mixto</option>
                        </select>
                    </div>

                    <div id="seccionMontos" style="display:none;">
                        <div class="mb-3">
                        <label class="form-label">Monto en Efectivo</label>
                        <input type="number" step="0.01" class="form-control" id="monto_efectivo" value="0">
                        </div>
                        <div class="mb-3">
                        <label class="form-label">Monto en Tarjeta</label>
                        <input type="number" step="0.01" class="form-control" id="monto_tarjeta" value="0">
                        </div>
                        <div class="mb-3">
                        <label class="form-label">Devolución</label>
                        <input type="text" class="form-control" id="devolucion" readonly>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="button" class="btn btn-success" id="btnConfirmarPago">Confirmar Pago</button>
                    </div>
                    </form>
                </div>
                </div>
            </div>
            </div>

            <script>
            // Buscar cliente por NIT
            document.getElementById('nit_cliente').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const nit = this.value;
                    fetch('buscar_cliente.php?nit=' + nit)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('clienteInfo').innerHTML = 'Cliente: ' + data.cliente.nombre;
                                document.getElementById('id_cliente').value = data.cliente.id_cliente;
                            } else {
                                if (confirm("Cliente no encontrado. ¿Desea registrar uno nuevo?")) {
                                    window.location.href = '../../modulos/clientes/Ncliente.php';
                                }
                            }
                        });
                }
            });

            // Buscar producto
            document.getElementById('codigo_barras').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const codigo = this.value;
                    fetch('buscar_producto_venta.php?codigo=' + codigo)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                agregarProducto(data.producto);
                                document.getElementById('codigo_barras').value = '';
                            } else {
                                alert('Producto no encontrado.');
                            }
                        });
                }
            });

            function agregarProducto(producto) {
                const tbody = document.querySelector('#tablaProductos tbody');
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${producto.CodBarras}</td>
                    <td>${producto.nombre}</td>
                    <td><input type="number" value="1" min="1" class="form-control cantidad"></td>
                    <td><input type="text" value="${producto.Iva}" class="form-control" disabled></td>
                    <td><input type="number" value="${parseFloat(producto.precio).toFixed(2)}" step="0.01" class="form-control precio"></td>
                    <td class="subtotal">${parseFloat(producto.precio).toFixed(2)}</td>
                `;
                tbody.appendChild(row);
            }

            // Subtotal en tiempo real
            document.getElementById('tablaProductos').addEventListener('input', function(e) {
                if (e.target.classList.contains('cantidad') || e.target.classList.contains('precio')) {
                    const fila = e.target.closest('tr');
                    const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
                    const precio = parseFloat(fila.querySelector('.precio').value) || 0;
                    fila.querySelector('.subtotal').innerText = (cantidad * precio).toFixed(2);
                }
            });

            // Calcular total
            function calcularTotal() {
                let total = 0;
                const filas = document.querySelectorAll('#tablaProductos tbody tr');
                filas.forEach(fila => {
                    const cantidad = parseFloat(fila.querySelector('.cantidad').value);
                    const precio = parseFloat(fila.querySelector('.precio').value);
                    const subtotal = cantidad * precio;
                    fila.querySelector('.subtotal').innerText = subtotal.toFixed(2);
                    total += subtotal;
                });

                document.getElementById('total_venta').value = total.toFixed(2);
                document.getElementById('totalMostrar').innerText = total.toFixed(2);
                document.getElementById('seccionPago').style.display = 'block';
            }

            // Evento para totalizar
            document.getElementById('btnTotalizar').addEventListener('click', calcularTotal);
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F2') {
                    e.preventDefault();
                    calcularTotal();
                }
            });

            // Abrir modal de pago
            document.getElementById('btnPagar').addEventListener('click', function() {
                new bootstrap.Modal(document.getElementById('modalPago')).show();
            });

            // Mostrar campos según método
            document.getElementById('metodo_pago').addEventListener('change', function() {
                const metodo = this.value;
                document.getElementById('seccionMontos').style.display = metodo ? 'block' : 'none';
            });

            // Confirmar pago
            document.getElementById('btnConfirmarPago').addEventListener('click', function() {
                const metodo = document.getElementById('metodo_pago').value;
                const efectivo = parseFloat(document.getElementById('monto_efectivo').value) || 0;
                const tarjeta = parseFloat(document.getElementById('monto_tarjeta').value) || 0;
                const total = parseFloat(document.getElementById('total_venta').value);

                if (!metodo) return alert('Seleccione un método de pago.');
                if (metodo === 'efectivo' && efectivo < total) return alert('El efectivo no cubre el total.');
                if (metodo === 'tarjeta' && tarjeta < total) return alert('La tarjeta no cubre el total.');
                if (metodo === 'mixto' && efectivo + tarjeta < total) return alert('La suma no cubre el total.');

                const devolucion = (efectivo + tarjeta - total).toFixed(2);
                document.getElementById('devolucion').value = devolucion;

                const detalles = [];
                const filas = document.querySelectorAll('#tablaProductos tbody tr');
                filas.forEach(fila => {
                    detalles.push({
                        codigo: fila.children[0].innerText,
                        cantidad: fila.querySelector('.cantidad').value,
                        precio: fila.querySelector('.precio').value,
                        subtotal: fila.querySelector('.subtotal').innerText
                    });
                });

                document.getElementById('detalles').value = JSON.stringify(detalles);

                document.getElementById('formVenta').submit();
            });
            </script>

            </body>
            </html>

