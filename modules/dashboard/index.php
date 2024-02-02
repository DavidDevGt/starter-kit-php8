<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// Puedes agregar lógica del controlador aquí o incluir un archivo que la contenga
use App\Controllers\SessionController;

$sessionController = new SessionController();
if (!$sessionController->verify()) {
    die("No estás autenticado. Por favor, inicia sesión para ver este contenido.");
}

require_once __DIR__ . '/../../components/header/default.php';
require_once __DIR__ . '/../../components/header/extend_libraries.php';
?>

<body class="bg-body-secondary">
    <!-- --------------------- INICIO MENU DE LA PAGINA --------------------- -->

    <?php include("../../components/menu/component.php"); ?>

    <!-- --------------------- FIN MENU DE LA PAGINA --------------------- -->




    <!-- --------------------- INICIO CONTENIDO DE LA PAGINA --------------------- -->

    <div>
        <div class="row mt-3 m-4">
            <div class="col-3 mt-2 pt-3">
                <!-- Breadcrumb deberia ir aca -->
            </div>
            <div class="col-6 mt-2 pt-3 text-center"></div>
            <div class="col-3 mt-2 pt-3 text-end">
                <!-- Area para botones en la parte de arriba -->
            </div>
        </div>
    </div>

    <!-- Aquí va el contenido específico del módulo -->
    <div class="p-4 m-4 bg-white rounded shadow">
        <!-- Encabezado del Dashboard -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">Dashboard</h3>
            <button class="btn btn-primary" type="button">Acción</button>
        </div>

        <!-- Sección de Estadísticas Rápidas -->
        <div class="row mb-4 text-center">
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-0 shadow bg-success">
                    <div class="card-body">
                        <i class="las la-users la-3x text-white"></i>
                        <h5 class="card-title mt-2 text-white">Clientes</h5>
                        <p class="card-text h3 text-white">250</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-0 shadow bg-primary">
                    <div class="card-body">
                        <i class="las la-boxes la-3x text-white"></i>
                        <h5 class="card-title mt-2 text-white">Productos</h5>
                        <p class="card-text h3 text-white">1,200</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-0 shadow bg-danger">
                    <div class="card-body">
                        <i class="las la-truck-loading la-3x text-white"></i>
                        <h5 class="card-title mt-2 text-white">Pedidos Pendientes</h5>
                        <p class="card-text h3 text-white">45</p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Sección de Gráficos -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm text-center">
                    <div class="card-body">
                        <h5 class="card-title">Ventas Mensuales</h5>
                        <div class="chart-container">
                            <canvas id="ventasMensualesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm text-center">
                    <div class="card-body">
                        <h5 class="card-title">Categorías Más Vendidas</h5>
                        <div class="chart-container">
                            <canvas id="categoriasChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Esperar a que el documento esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Datos de ejemplo para la gráfica de Ventas Mensuales
            const ventasMensualesData = {
                labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                datasets: [{
                    label: 'Ventas',
                    data: [12000, 15000, 3000, 5000, 5000, 10000, 5000, 4000, 9000, 2500, 11000, 15000],
                    backgroundColor: 'rgba(0, 123, 255, 0.5)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
                }]
            };

            // Configuración de la gráfica de Ventas Mensuales
            const ventasMensualesConfig = {
                type: 'bar',
                data: ventasMensualesData,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            };

            // Creación de la gráfica de Ventas Mensuales
            new Chart(document.getElementById('ventasMensualesChart'), ventasMensualesConfig);

            // Datos de ejemplo para la gráfica de Categorías Más Vendidas
            const categoriasData = {
                labels: ['Herramientas', 'Pinturas', 'Electricidad'],
                datasets: [{
                    label: 'Categorías',
                    data: [50, 35, 40],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            };

            // Configuración de la gráfica de Categorías Más Vendidas
            const categoriasConfig = {
                type: 'pie',
                data: categoriasData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Categorías'
                        }
                    }
                },
            };

            // Creación de la gráfica de Categorías Más Vendidas
            new Chart(document.getElementById('categoriasChart'), categoriasConfig);
        });
    </script>

    <style>
        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            max-height: 400px;
            width: 100%;
        }
    </style>

    <!-- --------------------- FIN MENU DE LA PAGINA --------------------- -->

</body>

<?php
require_once __DIR__ . '/../../components/footer/default.php';
?>