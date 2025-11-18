const apiKey = sessionStorage.getItem("apiKey");

document.addEventListener("DOMContentLoaded", () => {

  const erabiltzaileURL = CONFIG.BASE_URL + "controladores/erabiltzaileController.php";

  const username = sessionStorage.getItem("username");
  const name = sessionStorage.getItem("name");
  const lastname = sessionStorage.getItem("lastname");
  const role = sessionStorage.getItem("userRole");

  if (!username || !role) {
    alert("Saio baliogabea. Mesedez, hasi saioa berriro.");
    window.location.href = "index.html";
    return;
  }

  // Elementos del formulario
  const inputUsername = document.getElementById("username");
  const inputName = document.getElementById("name");
  const inputLastname = document.getElementById("lastname");
  const inputRole = document.getElementById("role");
  const saveBtn = document.getElementById("save-btn");
  const userNameSpan = document.getElementById("user-name");

  // Mostrar datos en el formulario
  inputUsername.value = username;
  inputName.value = name;
  inputLastname.value = lastname;
  inputRole.value = role.toUpperCase() === "A" ? "Administrador" : "Erabiltzailea";
  if (userNameSpan) userNameSpan.textContent = `${name} ${lastname}`;

  // Función para actualizar el modo (ver o editar)
  function updateMode() {
    const isEditMode = window.location.hash === "#editar";
    inputName.disabled = !isEditMode;
    inputLastname.disabled = !isEditMode;
    saveBtn.style.display = isEditMode ? "inline-block" : "none";
  }

  // Ejecutar al cargar y al cambiar hash
  updateMode();
  window.addEventListener("hashchange", updateMode);

  // Guardar cambios
  const form = document.getElementById("profile-form");
  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const updatedName = inputName.value.trim();
    const updatedLastname = inputLastname.value.trim();

    if (!updatedName || !updatedLastname) {
      alert("Mesedez, bete eremu guztiak.");
      return;
    }

    const headers = {
      "Content-Type": "application/json",
      "Authorization": `Bearer ${apiKey}`,
    };

    fetch(erabiltzaileURL, {
      method: "POST",
      headers,
      body: JSON.stringify({
        action: "PUT",
        username,
        name: updatedName,
        lastname: updatedLastname
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("Profila behar bezala eguneratu da.");

        // Actualizar sessionStorage y el header
        sessionStorage.setItem("name", updatedName);
        sessionStorage.setItem("lastname", updatedLastname);
        if (userNameSpan) userNameSpan.textContent = `${updatedName} ${updatedLastname}`;

        // Volver a modo ver perfil
        window.location.hash = "";
      } else {
        alert("Errorea profil eguneratzean: " + data.message);
      }
    })
    .catch(err => console.error(err));
  });
});
