document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm");
  const errorMessage = document.getElementById("error-message");

  form.addEventListener("submit", async (e) => {
    e.preventDefault(); // evitar recarga del formulario

    const username = form.username.value.trim();
    const password = form.password.value.trim();

    // Validación de campos vacíos
    if (username === "" || password === "") {
      errorMessage.textContent = "Por favor, rellena todos los campos.";
      return; // no continúa con la petición
    }

    const formData = new FormData(form);

    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          console.log(data);
          if (data.success) {
            // ✅ Redirige según el rol (decide el JS)
            window.location.href = data.redirect;
          } else {
            // Mostrar mensaje de error en pantalla
            errorMessage.textContent = data.message;
            console.log(errorMessage);
          }
        });
    } catch (error) {
      console.error("Error:", error);
      errorMessage.textContent = "Error en el servidor. Inténtalo más tarde.";
    }
  });
});
