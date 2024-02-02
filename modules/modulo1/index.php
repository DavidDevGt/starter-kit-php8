<?php
// Incluir el autoload para cargar las clases
require_once __DIR__ . '/../../vendor/autoload.php';

// Puedes agregar lógica del controlador aquí o incluir un archivo que la contenga

// Incluir header
require_once __DIR__ . '/../../components/header/default.php';
?>

<body class="bg-body-secondary">
    <!-- --------------------- INICIO MENU DE LA PAGINA --------------------- -->

    <?php include("../../components/menu/component.php"); ?>

    <!-- --------------------- FIN MENU DE LA PAGINA --------------------- -->

    <!-- Aquí va el contenido específico del módulo -->
    <div class="fluid-container">
        <!-- Contenido ... -->
        <h1 class="text-center mt-5">Bienvenido</h1>
    </div>

</body>

<?php
// Incluir footer
require_once __DIR__ . '/../../components/footer/default.php';
?>