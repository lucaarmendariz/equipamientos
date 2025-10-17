document.addEventListener("DOMContentLoaded", () => {

  


  // --- FUNCIONES GENERALES PARA CARGAR DATOS ---
  function cargarDatos(url, contenedorId, columnas, placeholder = "No hay datos disponibles") {
    const container = document.getElementById(contenedorId);

    fetch(url)
      .then(res => {
        if (!res.ok) throw new Error("Error en la respuesta del servidor");
        return res.json();
      })
      .then(data => {
        if (data.success && data.data.length > 0) {
          container.innerHTML = "";

          // Cabecera
          const header = document.createElement("div");
          header.classList.add("data-row", "data-header");
          header.innerHTML = columnas.map(col => `<span>${col.label}</span>`).join("");
          container.appendChild(header);

          // Filas
          data.data.forEach((item, index) => {
            const row = document.createElement("div");
            row.classList.add("data-row");
            if (index % 2 === 0) row.classList.add("even");

            row.innerHTML = columnas.map(col => `<span>${item[col.key] ?? "—"}</span>`).join("");
            container.appendChild(row);
          });
        } else {
          container.innerHTML = `<div class="data-row empty">${placeholder}</div>`;
        }
      })
      .catch(err => {
        console.error(`Error al obtener ${contenedorId}:`, err);
        container.innerHTML = `<div class="data-row empty">Error al cargar los datos.</div>`;
      });
  }


  // --- CARGAR DATOS POR CADA COLUMNA ---
  cargarDatos("../backend/equipos.php", "equipamientos-list", [
    { label: "Izena", key: "izena" },
    { label: "Stock", key: "stock" },
    { label: "Marka", key: "marka" },
    { label: "Modelo", key: "modelo" }
  ]);

  cargarDatos("../backend/inventario.php", "inbentario-list", [
    { label: "Etiqueta", key: "etiketa" },
    { label: "Equipo", key: "equipo" },
    { label: "Erosketa Data", key: "erosketaData" }
  ]);

  cargarDatos("../backend/usuarios.php", "usuarios-list", [
    { label: "NAN", key: "nan" },
    { label: "Nombre", key: "izena" },
    { label: "Apellido", key: "abizena" },
    { label: "Usuario", key: "erabiltzailea" },
    { label: "Rol", key: "rola" }
  ]);
});
