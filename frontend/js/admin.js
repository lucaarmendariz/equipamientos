document.addEventListener("DOMContentLoaded", () => {
  const ekipamenduakURL = CONFIG.BASE_URL + "backend/controladores/ekipamenduakController.php";
  const inbentarioURL = CONFIG.BASE_URL + "backend/controladores/inbentarioController.php";
  const erabiltzaileakURL = CONFIG.BASE_URL + "backend/controladores/erabiltzaileController.php";

  // --- Helper para escapar HTML ---
  function escapeHtml(str) {
    if (typeof str !== "string") return str;
    return str.replace(/[&<>"']/g, (m) => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[m]);
  }

  // --- Función general para cargar datos ---
  async function cargarDatos(url, contenedorId, columnas, action = "list", method = "GET", payload = null, placeholder = "No hay datos disponibles") {
    const container = document.getElementById(contenedorId);
    if (!container) return;

    let fetchURL = url;
    let fetchOptions = {
      method,
      headers: { "Content-Type": "application/json" }
    };

    if (method === "GET") {
      fetchURL += `?action=${encodeURIComponent(action)}`;
    } else {
      fetchOptions.body = JSON.stringify({ action, ...payload });
    }

    try {
      const res = await fetch(fetchURL, fetchOptions);
      if (!res.ok) throw new Error(`Respuesta del servidor: ${res.status}`);
      const data = await res.json();

      if (data.success && Array.isArray(data.data) && data.data.length > 0) {
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
    } catch (err) {
      console.error(`Error al obtener ${contenedorId} desde ${url}:`, err);
      container.innerHTML = `<div class="data-row empty">Error al cargar los datos.</div>`;
    }
  }

  // --- Cargar listas ---
  cargarDatos(ekipamenduakURL, "equipamientos-list", [
    { label: "Izena", key: "izena" },
    { label: "Stock", key: "stock" },
    { label: "Marka", key: "marka" },
    { label: "Modelo", key: "modelo" }
  ]);

  cargarDatos(inbentarioURL, "inbentario-list", [
    { label: "Etiqueta", key: "etiketa" },
    { label: "Equipo", key: "equipo" },
    { label: "Erosketa Data", key: "erosketaData" }
  ]);

  cargarDatos(erabiltzaileakURL, "usuarios-list", [
    { label: "NAN", key: "nan" },
    { label: "Nombre", key: "izena" },
    { label: "Apellido", key: "abizena" },
    { label: "Usuario", key: "erabiltzailea" },
    { label: "Rol", key: "rola" }
  ]);
});
