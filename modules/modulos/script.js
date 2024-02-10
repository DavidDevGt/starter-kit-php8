function modulosApp() {
  return {
    // Atributos iniciales
    modulos: [],
    mostrarModal: false,
    modalTitulo: "Crear Módulo",
    moduloActual: {},

    // Métodos
    abrirModalCrear() {
      this.modalTitulo = "Crear Módulo";
      this.moduloActual = {};
      this.mostrarModal = true;
    },
    cerrarModal() {
      this.mostrarModal = false;
    },
    guardarModulo() {
      const url = "./ajax.php";
      const metodo = this.moduloActual.id ? "PUT" : "POST";

      fetch(url, {
        method: metodo,
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(this.moduloActual),
      })
        .then((response) => response.json())
        .then((data) => {
          Swal.fire({
            icon: "success",
            title: "¡Éxito!",
            text: data.message,
          });
          this.cerrarModal();
          this.cargarModulos();
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Hubo un problema con la operación.",
          });
        });
    },
    eliminarModulo(id) {
      Swal.fire({
        title: "¿Estás seguro?",
        text: "No podrás revertir esto!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Sí, eliminarlo!",
      }).then((result) => {
        if (result.isConfirmed) {
          const url = `./ajax.php?action=delete&id=${id}`;

          fetch(url, { method: "DELETE" })
            .then((response) => response.json())
            .then((data) => {
              Swal.fire("¡Eliminado!", data.message, "success");
              this.cargarModulos();
            })
            .catch((error) => {
              console.error("Error:", error);
              Swal.fire({
                icon: "error",
                title: "Error",
                text: "Hubo un problema con la operación.",
              });
            });
        }
      });
    },
    cargarModulos() {
      const url = "./ajax.php";

      fetch(url)
        .then((response) => response.json())
        .then((data) => {
          this.modulos = data.data;
        })
        .catch((error) => console.error("Error:", error));
    },
    init() {
      this.cargarModulos();
    },
  };
}
