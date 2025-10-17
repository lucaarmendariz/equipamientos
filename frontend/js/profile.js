document.addEventListener("DOMContentLoaded", () => {
  const username = sessionStorage.getItem("username");
  const name = sessionStorage.getItem("name");
  const lastname = sessionStorage.getItem("lastname");
  const role = sessionStorage.getItem("userRole");

  if (!username || !role) {
    alert("Sesión no válida. Por favor, inicia sesión de nuevo.");
    window.location.href = "index.html";
    return;
  }

  // Elementos del formulario
  const inputUsername = document.getElementById("username");
  const inputName = document.getElementById("name");
  const inputLastname = document.getElementById("lastname");
  const inputRole = document.getElementById("role");
  const editBtn = document.getElementById("edit-btn");
  const saveBtn = document.getElementById("save-btn");
  const userNameSpan = document.getElementById("user-name");

  // Mostrar datos en el formulario
  inputUsername.value = username;
  inputName.value = name;
  inputLastname.value = lastname;
  inputRole.value = role.toUpperCase() === "A" ? "Administrador" : "Usuario";

  if (userNameSpan) userNameSpan.textContent = `${name} ${lastname}`;

  // Función para actualizar el modo (ver o editar)
  function updateMode() {
    const isEditMode = window.location.hash === "#editar";

    inputName.disabled = !isEditMode;
    inputLastname.disabled = !isEditMode;

    if (isEditMode) {
      saveBtn.style.display = "inline-block";
      editBtn.style.display = "none";
    } else {
      saveBtn.style.display = "none";
      editBtn.style.display = "none"; // nunca mostrar botón en modo ver perfil
    }
  }

  // Ejecutar al cargar y al cambiar hash
  updateMode();
  window.addEventListener("hashchange", updateMode);

  // Botón de editar (solo cambia hash)
  editBtn.addEventListener("click", () => {
    window.location.hash = "#editar";
  });

  // Guardar cambios
  const form = document.getElementById("profile-form");
  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const updatedName = inputName.value.trim();
    const updatedLastname = inputLastname.value.trim();

    fetch("../backend/update_profile.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username, name: updatedName, lastname: updatedLastname })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("Perfil actualizado correctamente");

        // Actualizar sessionStorage y el header
        sessionStorage.setItem("name", updatedName);
        sessionStorage.setItem("lastname", updatedLastname);
        if (userNameSpan) userNameSpan.textContent = `${updatedName} ${updatedLastname}`;

        // Volver a modo ver perfil
        window.location.hash = "";
      } else {
        alert("Error al actualizar perfil: " + data.message);
      }
    })
    .catch(err => console.error(err));
  });
});
