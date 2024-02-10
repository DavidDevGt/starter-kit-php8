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

<body class="bg-body-secondary" x-data="modulosApp()">

    <!-- --------------------- INICIO MODALES --------------------- -->

<div x-show="mostrarModal" class="modal fade" id="modalModulo" tabindex="-1" role="dialog" aria-labelledby="modalModuloLabel" aria-hidden="true" x-transition:enter="fade" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="fade" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalModuloLabel" x-text="modalTitulo"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="cerrarModal()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="guardarModulo()">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" x-model="moduloActual.name" required>
                    </div>
                    <div class="form-group">
                        <label for="ruta">Ruta</label>
                        <input type="text" class="form-control" id="ruta" x-model="moduloActual.route" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="cerrarModal()">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- --------------------- FIN MODALES --------------------- -->


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
                <button class="btn btn-primary" @click="abrirModalCrear()">Crear Módulo <i class="bi bi-plus-circle"></i></button>

            </div>
        </div>
    </div>

    <!-- Aquí va el contenido específico del módulo -->
    <div class="p-4 m-4 bg-white rounded">
        <h1 class="text-center mt-3">Bienvenido</h1>
        <div class="table-responsive mt-3">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Ruta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="modulo in modulos" :key="modulo.id">
                        <tr>
                            <td x-text="modulo.name"></td>
                            <td x-text="modulo.route"></td>
                            <td>
                                <button class="btn btn-sm btn-info" @click="editarModulo(modulo)"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-danger" @click="eliminarModulo(modulo.id)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

    </div>
</body>

<?php
// Incluir footer
require_once __DIR__ . '/../../components/footer/default.php';
?>