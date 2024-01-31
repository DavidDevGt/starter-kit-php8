<?php

session_start(); //para las variables de sesion
include("../../app/conexion.php");
include("../../include/funciones.php");
$usuario_id = $_SESSION['id_user'];
$iva = $_SESSION['iva'];

if (isset($_POST['fnc'])) {
    $op = $_POST['fnc'];

    switch ($op) {

    }
}else {
    echo '0|Ha ocurrido un error interno';
}
