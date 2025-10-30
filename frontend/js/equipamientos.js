document.addEventListener("DOMContentLoaded", () => {
  const backendURL = CONFIG.BASE_URL + "backend/controladores/ekipamenduakController.php";
  const categoriasURL = CONFIG.BASE_URL + "backend/controladores/kategoriaController.php";

  // Cargar categorías en select
  function cargarCategorias(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;
    fetch(categoriasURL)
      .then(res => res.json())
      .then(data => {
        select.innerHTML = '<option value="">Seleccione una categoría</option>';
        if (data.success && Array.isArray(data.data)) {
          data.data.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.izena;
            select.appendChild(option);
          });
        }
      })
      .catch(err => console.error("Error cargando categorías:", err));
  }
  cargarCategorias("categoria");
  cargarCategorias("edit-categoria");

  // Listar equipos
  function actualizarListaEquipos() {
    const container = document.getElementById("equipamientos-list");
    container.innerHTML = `<div class="data-row text-center py-2">Cargando datos...</div>`;

    fetch(backendURL)
      .then(res => res.json())
      .then(data => {
        if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
          container.innerHTML = `<div class="data-row empty text-center">No hay equipos registrados.</div>`;
          return;
        }

        console.log(data.data)

        container.innerHTML = "";
        const header = document.createElement("div");
        header.classList.add("data-row", "data-header");
        header.innerHTML = `<span>Nombre</span><span>Stock</span><span>Marca</span><span>Modelo</span><span>Acciones</span>`;
        container.appendChild(header);

        data.data.forEach((item, i) => {
          const row = document.createElement("div");
          row.classList.add("data-row");
          if (i % 2 === 0) row.classList.add("even");
          row.innerHTML = `
            <span>${item.izena ?? "—"}</span>
            <span>${item.stock ?? 0}</span>
            <span>${item.marka ?? "—"}</span>
            <span>${item.modelo ?? "—"}</span>
            <span>
              <button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="${item.id}">✏️</button>
              <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${item.id}">🗑️</button>
            </span>
          `;
          container.appendChild(row);
        });

        document.querySelectorAll(".edit-btn").forEach(btn =>
          btn.addEventListener("click", () => editarEquipo(btn.dataset.id))
        );
        document.querySelectorAll(".delete-btn").forEach(btn =>
          btn.addEventListener("click", () => eliminarEquipo(btn.dataset.id))
        );
      })
      .catch(err => console.error("Error cargando equipos:", err));
  }
  actualizarListaEquipos();

  // Crear equipo
  document.getElementById("addEquipoForm").addEventListener("submit", e => {
    e.preventDefault();
    const payload = {
      izena: document.getElementById("nombre").value,
      deskribapena: document.getElementById("descripcion").value,
      marca: document.getElementById("marca").value || null,
      modelo: document.getElementById("modelo").value || null,
      stock: parseInt(document.getElementById("stock").value) || 0,
      idKategoria: parseInt(document.getElementById("categoria").value) || 0
    };

    console.log(payload)
    fetch(backendURL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          bootstrap.Modal.getInstance(document.getElementById("addEquipoModal")).hide();
          e.target.reset();
          actualizarListaEquipos();
          new bootstrap.Modal(document.getElementById("successModal")).show();
        } else alert("Error: " + data.message);
      });
  });

  // Editar equipo
  function editarEquipo(id) {

    fetch(`${backendURL}?id=${id}`)
      .then(res => res.json())
      .then(data => {
        if (!data.success || !data.data) return alert("Equipo no encontrado");
        const e = data.data;
        document.getElementById("edit-id").value = e.id;
        document.getElementById("edit-nombre").value = e.izena;
        document.getElementById("edit-descripcion").value = e.deskribapena ?? "";
        document.getElementById("edit-marca").value = e.marka ?? "";
        document.getElementById("edit-modelo").value = e.modelo ?? "";
        document.getElementById("edit-stock").value = e.stock ?? 0;
        document.getElementById("edit-categoria").value = e.idKategoria ?? "";
        new bootstrap.Modal(document.getElementById("editEquipoModal")).show();
      });
  }

  document.getElementById("editEquipoForm").addEventListener("submit", e => {
    e.preventDefault();
    const payload = {
      id: parseInt(document.getElementById("edit-id").value),
      izena: document.getElementById("edit-nombre").value,
      deskribapena: document.getElementById("edit-descripcion").value,
      marca: document.getElementById("edit-marca").value || null,
      modelo: document.getElementById("edit-modelo").value || null,
      stock: parseInt(document.getElementById("edit-stock").value) || 0,
      idKategoria: parseInt(document.getElementById("edit-categoria").value) || 0
    };

    console.log(JSON.stringify(payload) + 'a enviar')

    fetch(backendURL, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          bootstrap.Modal.getInstance(document.getElementById("editEquipoModal")).hide();
          actualizarListaEquipos();
          new bootstrap.Modal(document.getElementById("editSuccessModal")).show();
        } else alert("Error: " + data.message);
      });
  });

  let equipoAEliminarId = null;

  function eliminarEquipo(id) {
    equipoAEliminarId = id;
    const modal = new bootstrap.Modal(document.getElementById("confirmDeleteModal"));
    modal.show();
  }

  // Listener del botón "Eliminar" en el modal
  document.getElementById("confirmDeleteButton").addEventListener("click", () => {
    if (!equipoAEliminarId) return;

    fetch(backendURL, {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: parseInt(equipoAEliminarId) })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          actualizarListaEquipos();
          new bootstrap.Modal(document.getElementById("editDeleteModal")).show();
        } else {
          alert("Error: " + data.message);
        }
      })
      .finally(() => {
        // Ocultar modal de confirmación y resetear variable
        equipoAEliminarId = null;
        const modalEl = document.getElementById("confirmDeleteModal");
        bootstrap.Modal.getInstance(modalEl).hide();
      });
  });
  

  // ============================================================
  // BÚSQUEDA GLOBAL DE EQUIPOS (IGNORA MAYÚSCULAS Y LA CABECERA)
  // ============================================================
  const searchInput = document.getElementById("searchInput");

  if (searchInput) {
    searchInput.addEventListener("input", () => {
      const filtro = searchInput.value.trim().toLowerCase();
      const contenedor = document.getElementById("equipamientos-list");

      if (!contenedor) return;

      // Seleccionamos todas las filas excepto la cabecera
      const filas = contenedor.querySelectorAll(".data-row:not(.data-header)");

      filas.forEach(fila => {
        const texto = fila.innerText.toLowerCase();
        fila.style.display = texto.includes(filtro) ? "" : "none";
      });
    });
  }
});
