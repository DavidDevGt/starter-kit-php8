<?php

require_once __DIR__ . '/../lib/vendor/autoload.php';

session_start();
session_destroy();

?>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./src/favicon.png" type="image/x-icon">
    <title>Starter Kit</title>
    <link href="../src/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../src/style-login.css">
</head>

<body>
    <div class="limiter">
        <div class="container-login100">
            <div class="wrap-login100">
                <div class="login100-form-title" style="background-image: url(../src//assets/images/bg-login.jpg);">
                    <span class="login100-form-title-1">
                        LOGIN
                    </span>
                </div>

                <div class="login100-form mt-3">
                    <div class="wrap-input100 mb-3">
                        <span class="label-input100">Usuario</span>
                        <input class="input100" type="text" id="usuario" placeholder="Ingrese su usuario">
                        <span class="focus-input100"></span>
                    </div>

                    <div class="wrap-input100 mb-5">
                        <span class="label-input100">Contraseña</span>
                        <input class="input100" type="password" id="password" placeholder="Ingrese su contraseña">
                        <span class="focus-input100"></span>
                    </div>

                    <div class="container-login100-form-btn">
                        <button class="login100-form-btn" id="btn_login">
                            Iniciar Sesión
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../src/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/assets/jquery/jquery-3.7.1.min.js"></script>
    <script src="../src/assets/sweetalert/sweetalert2@11.js"></script>
    <script src="index.js"></script>
</body>

</html>