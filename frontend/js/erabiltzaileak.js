// ===============================
// CONFIGURACIÓN GLOBAL
// ===============================
const usuariosURL = CONFIG.BASE_URL + "backend/controladores/erabiltzaileController.php";

// ===============================
// CARGAR USUARIOS AL INICIAR
// ===============================
document.addEventListener("DOMContentLoaded", () => {
  cargarUsuarios();
});

// ===============================
// FUNCIONES PRINCIPALES
// ===============================

// Obtener lista de usuarios y mostrar en tabla
async function cargarUsuarios() {
  const tbody = document.getElementById("usuariosTableBody");
  tbody.innerHTML = `<tr><td colspan="6" class="text-center">Cargando usuarios...</td></tr>`;

  try {
    const res = await fetch(usuariosURL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "GET" })
    });

    const data = await res.json();
    if (!data.success) throw new Error(data.message);

    tbody.innerHTML = "";

    data.data.forEach(user => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${user.nan}</td>
        <td>${user.izena}</td>
        <td>${user.abizena}</td>
        <td>${user.erabiltzailea}</td>
        <td>${user.rola}</td>
        <td class="text-center">
          <button class="btn btn-sm btn-warning edit-user" data-username="${user.erabiltzailea}"><i class="fa fa-pen"></i></button>
          <button class="btn btn-sm btn-danger delete-user" data-username="${user.erabiltzailea}"><i class="fa fa-trash"></i></button>
        </td>
      `;
      tbody.appendChild(tr);
    });

    activarBotonesUsuarios();
  } catch (err) {
    console.error("Error cargando usuarios:", err);
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error al cargar usuarios.</td></tr>`;
  }
}

// ===============================
// AÑADIR NUEVO USUARIO
// ===============================
document.getElementById("addUserForm")?.addEventListener("submit", async e => {
  e.preventDefault();

  const nan = document.getElementById("new-nan").value.trim();
  const name = document.getElementById("new-name").value.trim();
  const lastname = document.getElementById("new-lastname").value.trim();
  const username = document.getElementById("new-username").value.trim();
  const password = document.getElementById("new-password").value.trim();
  const role = document.getElementById("new-role").value.trim();

  if (!nan || !name || !lastname || !username || !password) {
    alert("Por favor, completa todos los campos obligatorios.");
    return;
  }

  try {
    const res = await fetch(usuariosURL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "POST",
        nan,
        name,
        lastname,
        username,
        password,
        role
      })
    });

    const data = await res.json();
    if (data.success) {
      const modal = bootstrap.Modal.getInstance(document.getElementById("addUserModal"));
      modal.hide();
      mostrarModalExito("Usuario añadido correctamente.");
      cargarUsuarios();
      e.target.reset();
    } else {
      alert(data.message);
    }
  } catch (err) {
    console.error("Error al crear usuario:", err);
    alert("Error al crear usuario.");
  }
});

// ===============================
// EDITAR USUARIO
// ===============================
function activarBotonesUsuarios() {
  // Editar
  document.querySelectorAll(".edit-user").forEach(btn => {
    btn.addEventListener("click", e => {
      const row = e.target.closest("tr");
      const username = btn.dataset.username;
      document.getElementById("edit-username").value = username;
      document.getElementById("edit-name").value = row.children[1].textContent;
      document.getElementById("edit-lastname").value = row.children[2].textContent;
      document.getElementById("edit-role").value = row.children[4].textContent;

      const modal = new bootstrap.Modal(document.getElementById("editUserModal"));
      modal.show();
    });
  });

  // Eliminar
  document.querySelectorAll(".delete-user").forEach(btn => {
    btn.addEventListener("click", () => {
      const username = btn.dataset.username;
      document.getElementById("confirmDeleteMessage").textContent =
        `¿Seguro que deseas eliminar al usuario "${username}"?`;

      document.getElementById("confirmDeleteButton").dataset.username = username;
      const modal = new bootstrap.Modal(document.getElementById("confirmDeleteModal"));
      modal.show();
    });
  });
}

// ===============================
// GUARDAR CAMBIOS EN USUARIO EDITADO
// ===============================
document.getElementById("editUserForm")?.addEventListener("submit", async e => {
  e.preventDefault();

  const username = document.getElementById("edit-username").value.trim();
  const name = document.getElementById("edit-name").value.trim();
  const lastname = document.getElementById("edit-lastname").value.trim();

  if (!username || !name || !lastname) return alert("Todos los campos son obligatorios.");

  try {
    const res = await fetch(usuariosURL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "PUT",
        username,
        name,
        lastname
      })
    });

    const data = await res.json();
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById("editUserModal")).hide();
      mostrarModalExito("Usuario actualizado correctamente.");
      cargarUsuarios();
    } else {
      alert(data.message);
    }
  } catch (err) {
    console.error("Error al editar usuario:", err);
    alert("Error al editar usuario.");
  }
});

// ===============================
// CONFIRMAR ELIMINACIÓN DE USUARIO
// ===============================
document.getElementById("confirmDeleteButton")?.addEventListener("click", async e => {
  const username = e.target.dataset.username;
  if (!username) return;

  try {
    const res = await fetch(usuariosURL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "DELETE", username })
    });

    const data = await res.json();
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById("confirmDeleteModal")).hide();
      mostrarModalExito("Usuario eliminado correctamente.");
      cargarUsuarios();
    } else {
      alert(data.message);
    }
  } catch (err) {
    console.error("Error al eliminar usuario:", err);
    alert("Error al eliminar usuario.");
  }
});

// ===============================
// MODAL DE ÉXITO
// ===============================
function mostrarModalExito(mensaje) {
  const msg = document.getElementById("successMessage");
  msg.textContent = mensaje;
  const modal = new bootstrap.Modal(document.getElementById("successModal"));
  modal.show();
}
