document.addEventListener("DOMContentLoaded", () => {
  const equipContainer = document.getElementById("equipamientos-list");

  fetch("../backend/equipos.php")
    .then((response) => response.json())
    .then((data) => {
      console.log(data);

      // Vaciar el contenedor
      equipContainer.innerHTML = "";

      // Crear cabecera
      const header = document.createElement("div");
      header.classList.add("data-row", "data-header");
      header.innerHTML = `
        <span class="col-nombre">Nombre</span>
        <span class="col-stock">Stock</span>
        <span class="col-marca">Marca</span>
        <span class="col-modelo">Modelo</span>
      `;
      equipContainer.appendChild(header);

      // Agregar filas de datos
      if (data.success) {
        data.data.forEach((equipo) => {
          const div = document.createElement("div");
          div.classList.add("data-row");
          div.innerHTML = `
            <span class="col-nombre">${equipo.izena}</span>
            <span class="col-stock">${equipo.stock}</span>
            <span class="col-marca">${equipo.marka || "N/A"}</span>
            <span class="col-modelo">${equipo.modelo || "N/A"}</span>
          `;
          equipContainer.appendChild(div);
        });
      } else {
        const errorDiv = document.createElement("div");
        errorDiv.classList.add("data-row");
        errorDiv.textContent = "No se pudieron cargar los equipos: " + data.message;
        equipContainer.appendChild(errorDiv);
      }
    })
    .catch((error) => {
      console.error("Error al obtener equipamientos:", error);
      equipContainer.innerHTML = `<div class="data-row">Error en el servidor. Inténtalo más tarde.</div>`;
    });
});
