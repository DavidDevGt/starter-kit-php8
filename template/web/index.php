<!DOCTYPE html>
<html lang="en">

<head>
    <!-- --------------------- INICIO DE HEADERS DE LA PAGINA --------------------- -->

    <!-- Jala todos los estilos y scripts necesarios para iniciar la pagina (jquery, bootstrap), titulo e icono de la pagina -->
    <?php require("../../components/header/default.php"); ?>

</head>

<body class="bg-body-secondary">


    <?php include("../../components/menu/component.php"); ?>

    <!-- --------------------- INICIO MODALES DE BUSQUEDA --------------------- -->



    <!-- --------------------- FIN MODALES DE BUSQUEDA --------------------- -->

    <!-- --------------------- INICIO CONTENIDO DE LA PAGINA --------------------- -->


    <div>
        <div class="row mt-3 m-4">
            <div class="col-3 mt-2 pt-3">
                <!-- Breadcrumb -->
                <?php include("../../components/breadcrumb/component.php"); ?>
            </div>
            <div class="col-6 mt-2 pt-3 text-center"></div>
            <div class="col-3 mt-2 pt-3 text-end">

                <!-- Area para botones en la parte de arriba -->
            </div>
        </div>
    </div>

    <!-- Card o contenedor blanco para diferenciarlo con el fondo y se vea bien -->
    <div class="p-4 m-4 bg-white rounded">
    </div>

    <!-- --------------------- FIN CONTENIDO DE LA PAGINA --------------------- -->

</body>

<!-- --------------------- INICIO FOOTER PAGINA --------------------- -->

<!-- Scripts no necesarios en el inicio de la pagina pero que si se utilizan -->
<?php include('../../components/footer/default.php') ?>

<!-- --------------------- FIN FOOTER PAGINA --------------------- -->

</html>