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

  // ============================================================
  // MODAL: GESTIÓN DE CATEGORÍAS
  // ============================================================
  const manageCategoriasModal = new bootstrap.Modal(document.getElementById("manageCategoriasModal"));
  const categoriasTableBody = document.getElementById("categoriasTableBody");
  const addCategoriaModalBtn = document.getElementById("addCategoriaModalBtn");
  let categoriaAEliminarId = null;

  // Abrir modal de gestión y cargar categorías
  document.getElementById("openCategoriasModalBtn")?.addEventListener("click", () => {
    cargarCategoriasTabla();
    manageCategoriasModal.show();
  });

  // Abrir modal de añadir categoría desde gestión
  addCategoriaModalBtn.addEventListener("click", () => {
    manageCategoriasModal.hide();
    new bootstrap.Modal(document.getElementById("addCategoriaModal")).show();
  });

  // ============================================================
  // CARGAR TABLA DE CATEGORÍAS
  // ============================================================
  async function cargarCategoriasTabla() {
    categoriasTableBody.innerHTML = `<tr><td colspan="4" class="text-center">Cargando categorías...</td></tr>`;

    try {
      const res = await fetch(categoriasURL);
      const result = await res.json();

      if (!result.success || !Array.isArray(result.data) || result.data.length === 0) {
        categoriasTableBody.innerHTML = `<tr><td colspan="4" class="text-center">No hay categorías.</td></tr>`;
        return;
      }

      categoriasTableBody.innerHTML = "";

      result.data.forEach(cat => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
        <td>${cat.id}</td>
        <td>
          <input type="text" class="form-control form-control-sm categoria-nombre" value="${cat.izena}" data-id="${cat.id}" disabled>
        </td>
        <td class="text-center">
          <button class="btn btn-warning btn-sm me-1 edit-categoria" data-id="${cat.id}"><i class="fa fa-edit"></i> Editar</button>
          <button class="btn btn-secondary btn-sm cancel-categoria" data-id="${cat.id}" style="display:none;"><i class="fa fa-times"></i></button>
          <button class="btn btn-success btn-sm me-1 save-categoria" data-id="${cat.id}" style="display:none;"><i class="fa fa-check"></i> Guardar</button>
          <button class="btn btn-danger btn-sm delete-categoria" data-id="${cat.id}"><i class="fa fa-trash"></i></button>
        </td>
      `;
        categoriasTableBody.appendChild(tr);
      });

      configurarBotonesCategorias();

    } catch (error) {
      console.error("Error cargando categorías:", error);
      categoriasTableBody.innerHTML = `<tr><td colspan="4" class="text-center">Error cargando categorías</td></tr>`;
    }
  }

  // ============================================================
  // CONFIGURAR BOTONES DE LA TABLA
  // ============================================================
  function configurarBotonesCategorias() {
  // EDITAR
  document.querySelectorAll(".edit-categoria").forEach(btn => {
    btn.addEventListener("click", () => {
      const row = btn.closest("tr");
      const input = row.querySelector(".categoria-nombre");
      input.disabled = false;
      input.focus();

      row.querySelector(".save-categoria").style.display = "inline-block";
      row.querySelector(".cancel-categoria").style.display = "inline-block";
      btn.style.display = "none";
      row.querySelector(".delete-categoria").style.display = "none";

      row.dataset.original = input.value;

      document.querySelectorAll("#categoriasTableBody tr").forEach(r => {
        if (r !== row) {
          r.querySelector(".edit-categoria").disabled = true;
          r.querySelector(".delete-categoria").disabled = true;
        }
      });
    });
  });

  // CANCELAR
  document.querySelectorAll(".cancel-categoria").forEach(btn => {
    btn.addEventListener("click", () => {
      const row = btn.closest("tr");
      const input = row.querySelector(".categoria-nombre");
      input.value = row.dataset.original;
      input.disabled = true;

      row.querySelector(".save-categoria").style.display = "none";
      row.querySelector(".cancel-categoria").style.display = "none";
      row.querySelector(".edit-categoria").style.display = "inline-block";
      row.querySelector(".delete-categoria").style.display = "inline-block";

      document.querySelectorAll("#categoriasTableBody tr").forEach(r => {
        r.querySelector(".edit-categoria").disabled = false;
        r.querySelector(".delete-categoria").disabled = false;
      });
    });
  });

  // GUARDAR
  document.querySelectorAll(".save-categoria").forEach(btn => {
    btn.addEventListener("click", async () => {
      const row = btn.closest("tr");
      const id = parseInt(btn.dataset.id);
      const nombre = row.querySelector(".categoria-nombre").value.trim();
      if (!nombre) return alert("El nombre no puede estar vacío");

      try {
        const res = await fetch(categoriasURL, {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id, izena: nombre })
        });
        const data = await res.json();
        if (!data.success) return alert(data.message);

        const successModal = new bootstrap.Modal(document.getElementById("categoriaSuccessModal"));
        document.getElementById("categoriaSuccessMessage").textContent = data.message || "La categoría ha sido modificada correctamente.";
        successModal.show();

        row.querySelector(".categoria-nombre").disabled = true;
        row.querySelector(".save-categoria").style.display = "none";
        row.querySelector(".cancel-categoria").style.display = "none";
        row.querySelector(".edit-categoria").style.display = "inline-block";
        row.querySelector(".delete-categoria").style.display = "inline-block";

        document.querySelectorAll("#categoriasTableBody tr").forEach(r => {
          if (r !== row) {
            r.querySelector(".edit-categoria").disabled = false;
            r.querySelector(".delete-categoria").disabled = false;
          }
        });

        await cargarCategorias(); // actualizar selects
      } catch (error) {
        console.error("Error al actualizar categoría:", error);
        alert("Error al actualizar categoría");
      }
    });
  });

  // ELIMINAR (solo abre modal, no hace DELETE directamente)
  document.querySelectorAll(".delete-categoria").forEach(btn => {
    btn.addEventListener("click", () => {
      categoriaAEliminarId = parseInt(btn.dataset.id);
      const nombre = btn.closest("tr").querySelector(".categoria-nombre").value;

      document.getElementById("confirmCategoriaDeleteMessage").textContent =
        `¿Seguro que deseas eliminar la categoría "${nombre}"?`;

      new bootstrap.Modal(document.getElementById("confirmCategoriaDeleteModal")).show();
    });
  });
}

document.getElementById("confirmCategoriaDeleteButton").addEventListener("click", async () => {
  if (!categoriaAEliminarId) return;

  try {
    const res = await fetch(categoriasURL, {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: categoriaAEliminarId })
    });
    const data = await res.json();
    if (!data.success) return alert(data.message);

    const successModal = new bootstrap.Modal(document.getElementById("categoriaSuccessModal"));
    document.getElementById("categoriaSuccessMessage").textContent = data.message || "Categoría eliminada correctamente.";
    successModal.show();

    cargarCategoriasTabla();
    cargarCategorias();
  } catch (error) {
    console.error("Error al eliminar categoría:", error);
  } finally {
    categoriaAEliminarId = null;
    bootstrap.Modal.getInstance(document.getElementById("confirmCategoriaDeleteModal")).hide();
  }
});



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

        container.innerHTML = "";

        // CABECERA
        const header = document.createElement("div");
        header.classList.add("data-row", "data-header");
        header.innerHTML = `
        <span>Nombre</span>
        <span>Stock</span>
        <span>Marca</span>
        <span>Modelo</span>
        <span>Categoría</span>
        <span>Acciones</span>
      `;
        container.appendChild(header);

        // FILAS
        data.data.forEach((item, i) => {
          const row = document.createElement("div");
          row.classList.add("data-row");
          if (i % 2 === 0) row.classList.add("even");

          row.innerHTML = `
          <span>${item.izena ?? "—"}</span>
          <span>${item.stock ?? 0}</span>
          <span>${item.marka ?? "—"}</span>
          <span>${item.modelo ?? "—"}</span>
          <span>${item.kategoria ?? "—"}</span>
          <span>
            <button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="${item.id}">✏️</button>
            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${item.id}">🗑️</button>
          </span>
        `;
          container.appendChild(row);
        });

        // EVENTOS DE BOTONES
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
      marka: document.getElementById("edit-marca").value || null,
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

  // ============================================================
  //  AÑADIR NUEVA CATEGORÍA 
  // ============================================================
  document.getElementById('addCategoriaForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const izena = document.getElementById('categoria-nombre').value.trim();
    if (!izena) return alert('Sartu kategoria izena.');

    try {
      const response = await fetch(categoriasURL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ izena })
      });

      const result = await response.json();

      if (result.success) {
        // Cierra el modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('addCategoriaModal'));
        modal.hide();

        // Limpia el formulario
        document.getElementById('categoria-nombre').value = '';

        // Recarga las categorías
        if (typeof cargarCategorias === 'function') {
          await cargarCategorias();
        }

        // Muestra el mensaje de éxito
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        document.getElementById('successMessage').textContent = result.message || 'Kategoria ondo gehitu da.';
        successModal.show();
      } else {
        alert(result.message || 'Errorea kategoria gehitzean.');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Zerbitzariarekin konexio errorea.');
    }
  });
  // ============================================================
  //  CARGAR TODAS LAS CATEGORÍAS DESDE EL BACKEND
  // ============================================================
  async function cargarCategorias() {
    try {
      const response = await fetch(categoriasURL, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
      });

      const result = await response.json();

      if (result.success && Array.isArray(result.data)) {
        const categorias = result.data;

        const selectAdd = document.getElementById('categoria');
        const selectEdit = document.getElementById('edit-categoria');

        // Limpiamos ambos select antes de rellenar
        selectAdd.innerHTML = '<option value="">Aukeratu kategoria...</option>';
        selectEdit.innerHTML = '<option value="">Aukeratu kategoria...</option>';

        categorias.forEach(cat => {
          const option1 = document.createElement('option');
          option1.value = cat.id;
          option1.textContent = cat.izena;
          selectAdd.appendChild(option1);

          const option2 = document.createElement('option');
          option2.value = cat.id;
          option2.textContent = cat.izena;
          selectEdit.appendChild(option2);
        });
      } else {
        console.warn('No se pudieron cargar las categorías:', result.message);
      }
    } catch (error) {
      console.error('Error al cargar categorías:', error);
    }
  }

  // Llamamos a la función al cargar la página
  document.addEventListener('DOMContentLoaded', cargarCategorias);



});
