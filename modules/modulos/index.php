<?php
// Incluir el autoload para cargar las clases
require_once __DIR__ . '/../../vendor/autoload.php';

// Puedes agregar lógica del controlador aquí o incluir un archivo que la contenga
use App\Controllers\SessionController;

$sessionController = new SessionController();
if (!$sessionController->verify()) {
    die("No estás autenticado. Por favor, inicia sesión para ver este contenido.");
}

// Incluir header
require_once __DIR__ . '/../../components/header/default.php';
?>

<body class="bg-body-secondary">
    <!-- --------------------- INICIO MENU DE LA PAGINA --------------------- -->

    <?php include("../../components/menu/component.php"); ?>

    <!-- --------------------- FIN MENU DE LA PAGINA --------------------- -->

    <div>
        <div class="row mt-3 m-4">
            <div class="col-3 mt-2 pt-3">
                <!-- Breadcrumb deberia ir aca -->
            </div>
            <div class="col-6 mt-2 pt-3 text-center"></div>
            <div class="col-3 mt-2 pt-3 text-end">
                <!-- Area para botones en la parte de arriba -->
                <button class="btn btn-primary" id="crearModulo"><i class="bi bi-plus-circle"></i></button>
                
            </div>
        </div>
    </div>

    <!-- Aquí va el contenido específico del módulo -->
    <div class="p-4 m-4 bg-white rounded">
        <h1 class="text-center mt-3">Bienvenido</h1>
    </div>
</body>

<?php
// Incluir footer
require_once __DIR__ . '/../../components/footer/default.php';
?>