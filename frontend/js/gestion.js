document.addEventListener("DOMContentLoaded", () => {
  const backendGelasURL = CONFIG.BASE_URL + "backend/controladores/gelakController.php";
  const backendEquiposURL = CONFIG.BASE_URL + "backend/controladores/ekipamenduakController.php";
  const backendKokalekuURL = CONFIG.BASE_URL + "backend/controladores/kokalekuaController.php";
  const apiKey = sessionStorage.getItem("apiKey");

  // ===================== LISTAR GELAK =====================
  async function actualizarListaGelas() {
    const container = document.getElementById("gelak-list");
    container.innerHTML = `<div class="data-row text-center py-2">Cargando datos...</div>`;
    try {
      const res = await fetch(backendGelasURL, { headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` } });
      const data = await res.json();
      if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
        container.innerHTML = `<div class="data-row empty text-center">No hay gelak registrados.</div>`;
        return;
      }
      container.innerHTML = "";
      const header = document.createElement("div");
      header.classList.add("data-row", "data-header");
      header.innerHTML = `<span>Nombre</span><span>Taldea</span><span>Acciones</span>`;
      container.appendChild(header);

      data.data.forEach((gela, i) => {
        const row = document.createElement("div");
        row.classList.add("data-row");
        if (i % 2 === 0) row.classList.add("even");
        row.innerHTML = `
          <span>${gela.izena ?? "—"}</span>
          <span>${gela.taldea ?? "—"}</span>
          <span>
            <button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="${gela.id}">✏️</button>
            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${gela.id}">🗑️</button>
          </span>
        `;
        container.appendChild(row);
      });

      document.querySelectorAll(".edit-btn").forEach(btn => btn.addEventListener("click", () => editarGela(btn.dataset.id)));
      document.querySelectorAll(".delete-btn").forEach(btn => btn.addEventListener("click", () => eliminarGela(btn.dataset.id)));
    } catch (err) {
      console.error("Error cargando gelak:", err);
      container.innerHTML = `<div class="data-row text-center text-danger">Error cargando gelak</div>`;
    }
  }
  actualizarListaGelas();

  // ===================== CREAR GELA =====================
  document.getElementById("addGelaForm").addEventListener("submit", async e => {
    e.preventDefault();
    const payload = {
      izena: document.getElementById("gela-nombre").value,
      taldea: document.getElementById("gela-taldea").value || null
    };
    try {
      const res = await fetch(backendGelasURL, {
        method: "POST",
        headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById("addGelaModal")).hide();
        e.target.reset();
        actualizarListaGelas();
        document.getElementById("successMessage").textContent = data.message || "Gela añadida correctamente.";
        new bootstrap.Modal(document.getElementById("successModal")).show();
      } else alert("Error: " + data.message);
    } catch (err) { console.error(err); alert("Error al añadir gela"); }
  });

  // ===================== EDITAR GELA =====================
  async function editarGela(id) {
    try {
      const res = await fetch(`${backendGelasURL}?id=${id}`, { headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` } });
      const data = await res.json();
      if (!data.success || !data.data) return alert("Gela no encontrada");
      const g = data.data;
      document.getElementById("edit-gela-id").value = g.id;
      document.getElementById("edit-gela-nombre").value = g.izena;
      document.getElementById("edit-gela-taldea").value = g.taldea ?? "";
      new bootstrap.Modal(document.getElementById("editGelaModal")).show();
    } catch (err) { console.error(err); alert("Error cargando gela"); }
  }

  document.getElementById("editGelaForm").addEventListener("submit", async e => {
    e.preventDefault();
    const payload = {
      id: parseInt(document.getElementById("edit-gela-id").value),
      izena: document.getElementById("edit-gela-nombre").value,
      taldea: document.getElementById("edit-gela-taldea").value || null
    };
    try {
      const res = await fetch(backendGelasURL, {
        method: "PUT",
        headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById("editGelaModal")).hide();
        actualizarListaGelas();
        document.getElementById("successMessage").textContent = data.message || "Gela actualizada correctamente.";
        new bootstrap.Modal(document.getElementById("successModal")).show();
      } else alert("Error: " + data.message);
    } catch (err) { console.error(err); alert("Error actualizando gela"); }
  });

  // ===================== ELIMINAR GELA =====================
  let gelaAEliminarId = null;
  function eliminarGela(id) {
    gelaAEliminarId = id;
    document.getElementById("confirmDeleteMessage").textContent = "¿Seguro que deseas eliminar esta gela?";
    new bootstrap.Modal(document.getElementById("confirmDeleteModal")).show();
  }
  document.getElementById("confirmDeleteButton").addEventListener("click", async () => {
    if (!gelaAEliminarId) return;
    try {
      const res = await fetch(backendGelasURL, {
        method: "DELETE",
        headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` },
        body: JSON.stringify({ id: parseInt(gelaAEliminarId) })
      });
      const data = await res.json();
      if (data.success) {
        actualizarListaGelas();
        document.getElementById("successMessage").textContent = data.message || "Gela eliminada correctamente.";
        new bootstrap.Modal(document.getElementById("successModal")).show();
      } else alert("Error: " + data.message);
    } catch (err) { console.error(err); alert("Error eliminando gela"); }
    finally {
      gelaAEliminarId = null;
      bootstrap.Modal.getInstance(document.getElementById("confirmDeleteModal")).hide();
    }
  });

  // ===================== BÚSQUEDA DE GELAK =====================
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      const filtro = searchInput.value.trim().toLowerCase();
      const contenedor = document.getElementById("gelak-list");
      if (!contenedor) return;
      const filas = contenedor.querySelectorAll(".data-row:not(.data-header)");
      filas.forEach(fila => {
        fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? "" : "none";
      });
    });
  }

  // ===================== CARGAR GELAK Y EQUIPOS PARA ASIGNACIÓN =====================
  async function cargarSelectsAsignacion() {
    const selectGela = document.getElementById("select-gela");
    const selectEquipo = document.getElementById("select-equipo");
    const stockInfo = document.getElementById("stockInfo");

    // Gelak
    try {
      const resGelas = await fetch(backendGelasURL, { headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` } });
      const dataGelas = await resGelas.json();
      selectGela.innerHTML = '<option value="">Seleccione gela</option>';
      if (dataGelas.success && Array.isArray(dataGelas.data)) {
        dataGelas.data.forEach(g => {
          const opt = document.createElement('option');
          opt.value = g.id;
          opt.textContent = `${g.izena} - ${g.taldea ?? "—"}`;
          selectGela.appendChild(opt);
        });
      }
    } catch (err) { console.error(err); }

    // Equipos
    try {
      const resEquipos = await fetch(backendEquiposURL, { headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` } });
      const dataEquipos = await resEquipos.json();
      selectEquipo.innerHTML = '<option value="">Seleccione equipo</option>';
      if (dataEquipos.success && Array.isArray(dataEquipos.data)) {
        dataEquipos.data.forEach(eq => {
          const opt = document.createElement('option');
          opt.value = eq.id;
          opt.textContent = eq.izena;
          selectEquipo.appendChild(opt);
        });
      }
    } catch (err) { console.error(err); }

    stockInfo.textContent = "Stock disponible: 0";
  }
  document.getElementById("addAsignacionModal").addEventListener("show.bs.modal", cargarSelectsAsignacion);

  // ===================== STOCK EN TIEMPO REAL =====================
  document.getElementById("select-equipo").addEventListener("change", async e => {
    const idEquipo = parseInt(e.target.value);
    const stockInfo = document.getElementById("stockInfo");
    if (!idEquipo) { stockInfo.textContent = "Stock disponible: 0"; return; }
    try {
      const res = await fetch(`${backendKokalekuURL}?idEkipamendu=${idEquipo}`, {
        headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` }
      });
      const data = await res.json();
      stockInfo.textContent = `Stock disponible: ${data.success ? data.data.stock : 0}`;
    } catch (err) { console.error(err); stockInfo.textContent = "Stock disponible: 0"; }
  });

  // ===================== LISTAR ASIGNACIONES =====================
  async function actualizarAsignaciones() {
    const container = document.getElementById("asignaciones-list");
    container.innerHTML = `<div class="data-row text-center py-2">Cargando asignaciones...</div>`;
    try {
      const res = await fetch(backendKokalekuURL, { headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` } });
      const data = await res.json();
      if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
        container.innerHTML = `<div class="data-row empty text-center">No hay asignaciones.</div>`;
        return;
      }

      container.innerHTML = "";
      const header = document.createElement("div");
      header.classList.add("data-row", "data-header");
      header.innerHTML = `<span>Etiketa</span><span>Gela</span><span>Taldea</span><span>Desde</span><span>Hasta</span><span>Acciones</span>`;
      container.appendChild(header);

      data.data.forEach((asignacion, i) => {
        if (asignacion.amaieraData) return; // Solo activas
        const row = document.createElement("div");
        row.classList.add("data-row");
        if (i % 2 === 0) row.classList.add("even");
        row.innerHTML = `
          <span>${asignacion.etiketa ?? "—"}</span>
          <span>${asignacion.gela_izena ?? "—"}</span>
          <span>${asignacion.taldea ?? "—"}</span>
          <span>${asignacion.hasieraData ?? "—"}</span>
          <span>${asignacion.amaieraData ?? "—"}</span>
          <span>
            <button class="btn btn-sm btn-outline-primary me-1 edit-asign-btn" data-id="${asignacion.etiketa}">✏️</button>
            <button class="btn btn-sm btn-outline-warning delete-asign-btn" data-id="${asignacion.etiketa}">🗓️</button>
          </span>
        `;
        container.appendChild(row);
      });

      document.querySelectorAll(".edit-asign-btn").forEach(btn => btn.addEventListener("click", () => editarAsignacion(btn.dataset.id)));
      document.querySelectorAll(".delete-asign-btn").forEach(btn => btn.addEventListener("click", () => finalizarAsignacion(btn.dataset.id)));

    } catch (err) {
      console.error("Error cargando asignaciones:", err);
      container.innerHTML = `<div class="data-row text-center text-danger">Error cargando asignaciones</div>`;
    }
  }

  // ===================== FINALIZAR ASIGNACIÓN =====================
  async function finalizarAsignacion(etiketa) {
    if (!etiketa) return;
    try {
      const res = await fetch(backendKokalekuURL, {
        method: "DELETE",
        headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` },
        body: JSON.stringify({ etiketa }) // backend asigna fecha automáticamente
      });
      const data = await res.json();
      if (data.success) {
        actualizarAsignaciones();
        document.getElementById("successMessage").textContent = data.message || "Asignación finalizada correctamente.";
        new bootstrap.Modal(document.getElementById("successModal")).show();
      } else alert("Error: " + data.message);
    } catch (err) { console.error(err); alert("Error finalizando asignación"); }
  }

  // ===================== HISTORIAL CON SCROLL INFINITO =====================
  const historialModal = document.getElementById("historialModal");
  const historialList = document.getElementById("historialList");
  const loadingHistorial = document.getElementById("loadingHistorial");
  const filterGela = document.getElementById("filterGela");
  const filterStartDate = document.getElementById("filterStartDate");
  const filterEndDate = document.getElementById("filterEndDate");
  const applyFiltersBtn = document.getElementById("applyFilters");

  let offset = 0, limit = 50, loading = false, allLoaded = false;

  async function cargarGelasFiltro() {
    try {
      const res = await fetch(backendGelasURL, { headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` } });
      const data = await res.json();
      if (data.success && Array.isArray(data.data)) {
        filterGela.innerHTML = '<option value="">Todas las gelak</option>';
        data.data.forEach(g => {
          const opt = document.createElement('option');
          opt.value = g.id;
          opt.textContent = `${g.izena} - ${g.taldea ?? "—"}`;
          filterGela.appendChild(opt);
        });
      }
    } catch (err) { console.error(err); }
  }

  async function cargarHistorial(reset = false) {
    if (loading || allLoaded) return;
    loading = true;
    loadingHistorial.style.display = "block";

    if (reset) { offset = 0; historialList.innerHTML = ""; allLoaded = false; }

    const params = new URLSearchParams({ historial: "1", offset, limit });
    if (filterGela.value) params.append("idGela", filterGela.value);
    if (filterStartDate.value) params.append("fechaInicio", filterStartDate.value);
    if (filterEndDate.value) params.append("fechaFin", filterEndDate.value);

    try {
      const res = await fetch(`${backendKokalekuURL}?${params.toString()}`, {
        headers: { "Content-Type": "application/json", "Authorization": `Bearer ${apiKey}` }
      });
      const data = await res.json();
      if (data.success && Array.isArray(data.data)) {
        if (data.data.length < limit) allLoaded = true;
        data.data.forEach(k => {
          const row = document.createElement("div");
          row.classList.add("data-row", "py-1", "border-bottom");
          row.innerHTML = `
            <span><strong>Etiketa:</strong> ${k.etiketa}</span>
            <span><strong>Gela:</strong> ${k.gela_izena} (${k.taldea ?? "—"})</span>
            <span><strong>Equipo:</strong> ${k.ekipamendu} (ID ${k.idEkipamendu})</span>
            <span><strong>Desde:</strong> ${k.hasieraData}</span>
            <span><strong>Hasta:</strong> ${k.amaieraData}</span>
          `;
          historialList.appendChild(row);
        });
        offset += limit;
      } else if (reset) {
        historialList.innerHTML = `<div class="text-center py-2">No hay registros históricos.</div>`;
        allLoaded = true;
      }
    } catch (err) { console.error("Error cargando historial:", err); }
    finally { loading = false; loadingHistorial.style.display = "none"; }
  }

  historialList.addEventListener("scroll", () => {
    if (historialList.scrollTop + historialList.clientHeight >= historialList.scrollHeight - 50) {
      cargarHistorial();
    }
  });

  applyFiltersBtn.addEventListener("click", () => cargarHistorial(true));
  historialModal.addEventListener("show.bs.modal", () => {
    cargarGelasFiltro();
    cargarHistorial(true);
  });

  // ===================== INICIALIZACIÓN =====================
  actualizarAsignaciones();
});
