// js/dashboard.js
document.addEventListener("DOMContentLoaded", () => {

  // --- FUNCIONES GENERALES PARA CARGAR DATOS ---
  /**
   * cargarDatos envía siempre { action: "list" } al backend vía POST y pinta la respuesta.
   * @param {string} url - endpoint (ej: ../backend/equipos.php)
   * @param {string} contenedorId - id del elemento donde renderizar
   * @param {Array} columnas - [{label: 'Izena', key: 'izena'}, ...]
   * @param {string} placeholder - texto si no hay datos
   */
  function cargarDatos(url, contenedorId, columnas, placeholder = "No hay datos disponibles") {
    const container = document.getElementById(contenedorId);
    if (!container) return; // evita errores si la sección no existe en la página

    // Enviamos siempre action=list para pedir la lista
    fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "list" })
    })
      .then(res => {
        if (!res.ok) throw new Error(`Respuesta del servidor: ${res.status}`);
        return res.json();
      })
      .then(data => {
        if (data && data.success && Array.isArray(data.data) && data.data.length > 0) {
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

            row.innerHTML = columnas.map(col => `<span>${escapeHtml(item[col.key] ?? "—")}</span>`).join("");
            container.appendChild(row);
          });
        } else {
          container.innerHTML = `<div class="data-row empty">${placeholder}</div>`;
        }
      })
      .catch(err => {
        console.error(`Error al obtener ${contenedorId} desde ${url}:`, err);
        container.innerHTML = `<div class="data-row empty">Error al cargar los datos.</div>`;
      });
  }

  // pequeño helper para evitar inyección de HTML al mostrar texto
  function escapeHtml(str) {
    if (typeof str !== "string") return str;
    return str.replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);
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
