document.addEventListener("DOMContentLoaded", () => {
  // --- CONTROL DE SESIÓN Y ROLES ---
  const role = sessionStorage.getItem("userRole");

  if (!role) {
    alert("Sesión no válida. Por favor, inicia sesión de nuevo.");
    window.location.href = "login.html";
    return;
  }

  // Si NO es admin, ocultamos la columna y el botón de "Erabiltzaileak"
  if (role.toLowerCase() !== "a") {
    const usuariosContainer = document.getElementById("usuarios-container");
    const usuariosButton = document.querySelector("nav button:last-child");

    if (usuariosContainer) usuariosContainer.style.display = "none";
    if (usuariosButton) usuariosButton.style.display = "none";
  }

  // --- FETCH DE EQUIPAMIENTOS ---
  const equipContainer = document.getElementById("equipamientos-list");

  fetch("../backend/equipos.php")
    .then((response) => {
      if (!response.ok) throw new Error("Error en la respuesta del servidor");
      return response.json();
    })
    .then((data) => {
      console.log("Datos recibidos:", data);

      if (data.success && data.data.length > 0) {
        equipContainer.innerHTML = "";

        // Añadir cabecera de la tabla/filas
        const header = document.createElement("div");
        header.classList.add("data-row", "data-header");
        header.innerHTML = `
          <span>Izena</span>
          <span>Stock</span>
          <span>Marka</span>
          <span>Modelo</span>
        `;
        equipContainer.appendChild(header);

        // Crear filas con los datos
        data.data.forEach((equipo, index) => {
          const row = document.createElement("div");
          row.classList.add("data-row");
          if (index % 2 === 0) row.classList.add("even"); // alternar color

          row.innerHTML = `
            <span>${equipo.izena}</span>
            <span>${equipo.stock}</span>
            <span>${equipo.marka || "—"}</span>
            <span>${equipo.modelo || "—"}</span>
          `;
          equipContainer.appendChild(row);
        });
      } else {
        equipContainer.innerHTML = `<div class="data-row empty">No hay datos disponibles</div>`;
      }
    })
    .catch((error) => {
      console.error("Error al obtener equipamientos:", error);
      equipContainer.innerHTML = `<div class="data-row empty">Error al cargar los equipos.</div>`;
    });
});
