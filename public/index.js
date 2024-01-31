"use strict";

$(document).ready(function () {
  const username = $("#username");
  const password = $("#password");
  const login_btn = $("#btn_login");

  const login = () => {
    if (username.val() === "") {
      Swal.fire({
        icon: "error",
        title: "Campos vacíos",
        text: "Ingresa tu nombre de usuario",
        timer: 1500,
        showConfirmButton: false,
      });
      return;
    }
    if (password.val() === "") {
      Swal.fire({
        icon: "error",
        title: "Campos vacíos",
        text: "Ingresa tu contraseña",
        timer: 1500,
        showConfirmButton: false,
      });
      return;
    }

    // AJAX request
    $.ajax({
      url: "ajax.php",
      type: "POST",
      data: {
        username: username.val(),
        password: password.val(),
      },
      success: function (response) {
        const data = JSON.parse(response);
        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "Inicio de sesión exitoso",
            text: data.message,
            timer: 1500,
            showConfirmButton: false,
          }).then(() => {
            window.location.href = "./modules/dashboard/index.php";
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message,
            timer: 1500,
            showConfirmButton: false,
          });
        }
      },
      error: function () {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error en el servidor, intenta más tarde",
          timer: 1500,
          showConfirmButton: false,
        });
      },
    });
  };

  // Event listener for the login button
  login_btn.on("click", function (event) {
    event.preventDefault();
    login();
  });

  // Event listener for the enter key
  username.add(password).on("keyup", function (e) {
    if (e.keyCode === 13) {
      login();
    }
  });
});
