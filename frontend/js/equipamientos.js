document.addEventListener("DOMContentLoaded", () => {

  const backendURL = "../backend/equipos.php";

  // ============================================================
  // FUNCIONES AUXILIARES
  // ============================================================

  // Función para cargar categorías en un select
  function cargarCategorias(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;

    fetch('../backend/kategoriak.php')
      .then(res => res.json())
      .then(data => {
        select.innerHTML = '<option value="">Seleccione una categoría</option>';
        data.data.forEach(cat => {
          const option = document.createElement('option');
          option.value = cat.id;
          option.textContent = cat.izena;
          select.appendChild(option);
        });
      })
      .catch(err => console.error('Error cargando categorías:', err));
  }

  // Al cargar la página o modal, cargar categorías
  cargarCategorias('edit-categoria');

  // 🧩 Función para cargar datos en un contenedor
  function cargarDatos(url, contenedorId, columnas, placeholder = "No hay datos disponibles") {
    const container = document.getElementById(contenedorId);

    fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "list" })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success && data.data.length > 0) {
          container.innerHTML = "";

          // Cabecera
          const header = document.createElement("div");
          header.classList.add("data-row", "data-header");
          header.innerHTML = columnas.map(col => `<span>${col.label}</span>`).join("") + "<span>Acciones</span>";
          container.appendChild(header);

          // Filas
          data.data.forEach((item, index) => {
            const row = document.createElement("div");
            row.classList.add("data-row");
            if (index % 2 === 0) row.classList.add("even");

            row.innerHTML = columnas.map(col => `<span>${item[col.key] ?? "—"}</span>`).join("");

            // Botones de acción
            const btns = document.createElement("span");
            btns.innerHTML = `
              <button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="${item.id}">✏️</button>
              <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${item.id}">🗑️</button>
            `;
            row.appendChild(btns);
            container.appendChild(row);
          });

          // Añadir eventos a botones
          document.querySelectorAll(".edit-btn").forEach(btn =>
            btn.addEventListener("click", () => editarEquipo(btn.dataset.id))
          );
          document.querySelectorAll(".delete-btn").forEach(btn =>
            btn.addEventListener("click", () => eliminarEquipo(btn.dataset.id))
          );

        } else {
          container.innerHTML = `<div class="data-row empty">${placeholder}</div>`;
        }
      })
      .catch(err => {
        console.error(`Error al obtener ${contenedorId}:`, err);
        container.innerHTML = `<div class="data-row empty">Error al cargar los datos.</div>`;
      });
  }

  // ============================================================
  // 🔄 CARGAR LISTA DE EQUIPOS
  // ============================================================

  function actualizarListaEquipos() {
    cargarDatos(backendURL, "equipamientos-list", [
      { label: "Izena", key: "izena" },
      { label: "Stock", key: "stock" },
      { label: "Marka", key: "marka" },
      { label: "Modelo", key: "modelo" }
    ]);
  }
  actualizarListaEquipos();

  // ============================================================
  // 📚 CARGAR SELECTS (CATEGORÍAS)
  // ============================================================

  fetch('../backend/kategoriak.php')
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById('categoria');
      data.data.forEach(c => {
        const option = document.createElement('option');
        option.value = c.id;
        option.textContent = c.izena;
        select.appendChild(option);
      });
    });


  // ============================================================
  // ➕ AGREGAR NUEVO EQUIPO
  // ============================================================

  const form = document.getElementById('addEquipoForm');
  form.addEventListener('submit', e => {
    e.preventDefault();

    const payload = {
      action: "insert",
      nombre: document.getElementById('nombre').value,
      deskribapena: document.getElementById('descripcion').value,
      marca: document.getElementById('marca').value,
      modelo: document.getElementById('modelo').value,
      stock: parseInt(document.getElementById('stock').value),
      idKategoria: parseInt(document.getElementById('categoria').value),
    };

    fetch(backendURL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Cerrar modal y limpiar formulario
          bootstrap.Modal.getInstance(document.getElementById('addEquipoModal')).hide();
          form.reset();

          actualizarListaEquipos();

          // Mostrar modal de éxito
          new bootstrap.Modal(document.getElementById('successModal')).show();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(err => console.error(err));
  });

  // ============================================================
  // ✏️ EDITAR EQUIPO
  // ============================================================

  function editarEquipo(id) {
    fetch(backendURL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "getById", id: id }) // enviamos el ID
    })
      .then(res => res.json())
      .then(data => {
        if (!data.success) return alert(data.message || "Equipo no encontrado");

        const equipo = data.data; // ya es un objeto, no un array
        console.log(equipo);

        // Rellenar formulario de edición
        document.getElementById("edit-id").value = equipo.id;
        document.getElementById("edit-nombre").value = equipo.izena;
        document.getElementById("edit-descripcion").value = equipo.deskribapena ?? "";
        document.getElementById("edit-marca").value = equipo.marka ?? "";
        document.getElementById("edit-modelo").value = equipo.modelo ?? "";
        document.getElementById("edit-stock").value = equipo.stock ?? 0;

        // Seleccionar categoría actual en el select
        const selectCategoria = document.getElementById("edit-categoria");
        if (selectCategoria) {
          selectCategoria.value = equipo.idKategoria ?? "";
        }

        // Mostrar modal
        new bootstrap.Modal(document.getElementById("editEquipoModal")).show();
      })
      .catch(err => console.error("Error al cargar el equipo:", err));
  }



  // Guardar cambios de edición
  const editForm = document.getElementById("editEquipoForm");
  if (editForm) {
    editForm.addEventListener("submit", e => {
      e.preventDefault();

      const payload = {
        action: "update",
        id: parseInt(document.getElementById("edit-id").value),
        nombre: document.getElementById("edit-nombre").value,
        deskribapena: document.getElementById("edit-descripcion").value,
        marca: document.getElementById("edit-marca").value,
        modelo: document.getElementById("edit-modelo").value,
        stock: parseInt(document.getElementById("edit-stock").value),
        idKategoria: parseInt(document.getElementById("edit-categoria").value || 0)
      };

      fetch(backendURL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById("editEquipoModal")).hide();
            actualizarListaEquipos();
            alert("Equipo actualizado correctamente");
          } else {
            alert("Error: " + data.message);
          }
        })
        .catch(err => console.error(err));
    });
  }

  // ============================================================
  // 🗑️ ELIMINAR EQUIPO
  // ============================================================

  function eliminarEquipo(id) {
    if (!confirm("¿Seguro que quieres eliminar este equipo?")) return;

    fetch(backendURL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "delete", id: parseInt(id) })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          actualizarListaEquipos();
          alert("Equipo eliminado correctamente");
        } else {
          alert("Error: " + data.message);
        }
      })
      .catch(err => console.error(err));
  }
});
