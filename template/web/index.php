<!-- --------------------- INICIO DE LA SEGURIDAD DE LA PAGINA --------------------- -->

<!-- Seguridad de la sesion, valida el usuario, el modulo y si tiene permiso de lectura -->
<?php require("../../components/security_session/component.php"); ?>

<!-- --------------------- INICIO DE LA SEGURIDAD DE LA PAGINA --------------------- -->

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- --------------------- INICIO DE HEADERS DE LA PAGINA --------------------- -->

    <!-- Jala todos los estilos y scrips necesarios para iniciar la pagina (jquery, bootstrap), titulo e icono de la pagina -->
    <?php require("../../components/header/default.php"); ?>

    <!-- Jala los estilos y scripts para las tablas dinamicas (OPCIONAL) -->
    <?php require("../../components/header/tables.php"); ?>

    <!-- --------------------- COMPONENTE THEME SWITCHER --------------------- -->
    <link rel="stylesheet" href="../../components/night_mode/theme-switcher.css">
    <script src="../../components/night_mode/theme-switcher.js"></script>
</head>

<body class="bg-body-secondary">

    <!-- --------------------- INICIO CONTENIDO DE LA PAGINA --------------------- -->

    <?php include("../../components/menu/component.php"); ?>

    <!-- --------------------- FIN CONTENIDO DE LA PAGINA --------------------- -->

    <!-- --------------------- INICIO MODALES DE BUSQUEDA --------------------- -->

    <!-- Buscadores para cualquier modulo -->
    <?php include("../../components/buscar_factura/component.php"); ?>
    <?php include("../../components/buscar_egreso/component.php"); ?>
    <?php include("../../components/buscar_pedido/component.php"); ?>

    <!-- Buscador de clientes solo funciona en pedidos por el momento -->
    <?php include("../../components/buscar_cliente/component.php"); ?>

    <!-- --------------------- FIN MODALES DE BUSQUEDA --------------------- -->

    <!-- --------------------- INICIO CONTENIDO DE LA PAGINA --------------------- -->


    <div>
        <div class="row mt-3 m-4">
            <div class="col-3 mt-2 pt-3">
                <!-- Breadcrumb nuevo y sus respectivos links -->
                <?php include("../../components/breadcrumb/component.php"); ?>
            </div>
            <div class="col-6 mt-2 pt-3 text-center"></div>
            <div class="col-3 mt-2 pt-3 text-end">
            <?php include("../../components/night_mode/theme2.php"); ?>

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