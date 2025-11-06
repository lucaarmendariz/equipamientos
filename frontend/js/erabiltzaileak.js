const erabiltzaileakURL = CONFIG.BASE_URL + "backend/controladores/erabiltzaileController.php";
  const apiKey = sessionStorage.getItem("apiKey");

document.addEventListener("DOMContentLoaded", () => {

  // ============================================================
  // LISTAR USUARIOS
  // ============================================================
  async function cargarErabiltzaileak() {
    const container = document.getElementById("users-list");
    container.innerHTML = `<div class="data-row text-center py-2">Kargatzen erabiltzaileak...</div>`;

    try {
      const res = await fetch(erabiltzaileakURL, {
        headers: {
          "Content-Type": "application/json",
          "Authorization": `Bearer ${apiKey}`
        }
      });
      const data = await res.json();

      if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
        container.innerHTML = `<div class="data-row empty text-center">Ez dago erabiltzailerik.</div>`;
        return;
      }

      container.innerHTML = "";

      // CABECERA
      const header = document.createElement("div");
      header.classList.add("data-row", "data-header");
      header.innerHTML = `
        <span>NAN</span>
        <span>Izena</span>
        <span>Abizena</span>
        <span>Erabiltzailea</span>
        <span>Rola</span>
        <span>Ekintzak</span>
      `;
      container.appendChild(header);

      // FILAS
      data.data.forEach((u, i) => {
        const row = document.createElement("div");
        row.classList.add("data-row");
        if (i % 2 === 0) row.classList.add("even");

        row.innerHTML = `
          <span>${u.nan ?? "—"}</span>
          <span>${u.izena ?? "—"}</span>
          <span>${u.abizena ?? "—"}</span>
          <span>${u.erabiltzailea ?? "—"}</span>
          <span>${u.rola === "A" ? "Admin" : "User"}</span>
          <span>
<button class="btn btn-sm btn-outline-primary me-1 edit-user-btn"
  data-user='${JSON.stringify(u)}'>✏️</button>
            <button class="btn btn-sm btn-outline-danger delete-user-btn" data-nan="${u.nan}">🗑️</button>
          </span>
        `;
        container.appendChild(row);
      });

      // EVENTOS DE BOTONES
      document.querySelectorAll(".edit-user-btn").forEach(btn => {
        btn.addEventListener("click", () => {
          const user = JSON.parse(btn.dataset.user);
          editarErabiltzailea(user);
        });
      });


      document.querySelectorAll(".delete-user-btn").forEach(btn =>
        btn.addEventListener("click", () => eliminarErabiltzailea(btn.dataset.nan))
      );

    } catch (err) {
      console.error("Errorea erabiltzaileak kargatzean:", err);
      container.innerHTML = `<div class="data-row text-center text-danger">Errorea kargatzean.</div>`;
    }
  }

  cargarErabiltzaileak();


  // ============================================================
  // AÑADIR USUARIO
  // ============================================================
  document.getElementById("addUserForm").addEventListener("submit", async e => {
    e.preventDefault();

    const payload = {
      action: "POST",
      nan: document.getElementById("new-nan").value.trim(),
      name: document.getElementById("new-name").value.trim(),
      lastname: document.getElementById("new-lastname").value.trim(),
      username: document.getElementById("new-username").value.trim(),
      password: document.getElementById("new-password").value.trim(),
      role: document.getElementById("new-role").value.trim(),
    };

    if (!payload.nan || !payload.name || !payload.lastname || !payload.username || !payload.password) {
      return alert("Bete beharrezko eremu guztiak.");
    }

    const res = await fetch(erabiltzaileakURL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": `Bearer ${apiKey}`
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById("addUserModal")).hide();
      e.target.reset();
      cargarErabiltzaileak();
      new bootstrap.Modal(document.getElementById("successModal")).show();
    } else {
      alert("Errorea: " + data.message);
    }
  });


  // ============================================================
  // EDITAR USUARIO
  // ============================================================
  function editarErabiltzailea(user) {
    console.log("Editatzen erabiltzailea:", user.nan);

    // Rellenar los campos del formulario con los datos actuales
    document.getElementById("edit-nan").value = user.nan || "";
    document.getElementById("edit-name").value = user.izena || "";
    document.getElementById("edit-lastname").value = user.abizena || "";
    document.getElementById("edit-username").value = user.erabiltzailea || "";
    document.getElementById("edit-role").value = user.rola || "U";

    // Mostrar la modal
    const modal = new bootstrap.Modal(document.getElementById("editUserModal"));
    modal.show();

    // Guardar cambios
    const form = document.getElementById("editUserForm");
    form.onsubmit = e => {
      e.preventDefault();

      const updatedUser = {
        nan: document.getElementById("edit-nan").value,
        name: document.getElementById("edit-name").value,
        lastname: document.getElementById("edit-lastname").value,
        username: document.getElementById("edit-username").value,
        role: document.getElementById("edit-role").value,
      };

      fetch(erabiltzaileakURL, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          "Authorization": `Bearer ${apiKey}`
        },
        body: JSON.stringify(updatedUser),
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            modal.hide();
            cargarErabiltzaileak(); // refrescar la lista
          } else {
            alert(data.message || "Errorea eguneratzean.");
          }
        })
        .catch(err => {
          console.error("Errorea eguneratzean:", err);
          alert("Errorea eguneratzean.");
        });
    };
  }




  document.getElementById("editUserForm")?.addEventListener("submit", async e => {
    e.preventDefault();

    const payload = {
      action: "UPDATE_ADMIN",
      nan: document.getElementById("edit-nan").value,
      name: document.getElementById("edit-name").value,
      lastname: document.getElementById("edit-lastname").value,
      username: document.getElementById("edit-username").value,
      password: document.getElementById("edit-password").value,
      role: document.getElementById("edit-role").value
    };

    const res = await fetch(erabiltzaileakURL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": `Bearer ${apiKey}`
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById("editUserModal")).hide();
      cargarErabiltzaileak();
      new bootstrap.Modal(document.getElementById("editSuccessModal")).show();
    } else {
      alert("Errorea: " + data.message);
    }
  });


  // ============================================================
  // ELIMINAR USUARIO
  // ============================================================
  let nanEzabatzeko = null;

  function eliminarErabiltzailea(nan) {
    nanEzabatzeko = nan;
    new bootstrap.Modal(document.getElementById("confirmDeleteUserModal")).show();
  }

  document.getElementById("confirmDeleteUserButton")?.addEventListener("click", async () => {
    if (!nanEzabatzeko) return;

    const res = await fetch(erabiltzaileakURL, {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
        "Authorization": `Bearer ${apiKey}`
      },
      body: JSON.stringify({ action: "DELETE", nan: nanEzabatzeko })
    });

    const data = await res.json();

    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById("confirmDeleteUserModal")).hide();
      cargarErabiltzaileak();
      new bootstrap.Modal(document.getElementById("editDeleteModal")).show();
    } else {
      alert("Errorea: " + data.message);
    }

    nanEzabatzeko = null;
  });


  // ============================================================
  // BÚSQUEDA GLOBAL DE USUARIOS
  // ============================================================
  const searchUserInput = document.getElementById("searchUserInput");
  if (searchUserInput) {
    searchUserInput.addEventListener("input", () => {
      const filtro = searchUserInput.value.trim().toLowerCase();
      const contenedor = document.getElementById("users-list");
      const filas = contenedor.querySelectorAll(".data-row:not(.data-header)");
      filas.forEach(fila => {
        const texto = fila.innerText.toLowerCase();
        fila.style.display = texto.includes(filtro) ? "" : "none";
      });
    });
  }
});
