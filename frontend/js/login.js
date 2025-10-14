document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm");
  const errorMessage = document.getElementById("error-message");

  form.addEventListener("submit", async (e) => {
    e.preventDefault(); // evitar recarga del formulario

    const formData = new FormData(form);

    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: formData,
      }).then(response => response.json()).then(data => {
        console.log(data);
        if (data.success) {
          // Redirige según el rol
          window.location.href = data.redirect;
        } else {
          // Mostrar mensaje de error en pantalla
          errorMessage.textContent = data.message;
        }
      })

/*      const data = await response.json();
      console.log(data);
      if (data.success) {
        // Redirige según el rol
        window.location.href = data.redirect;
      } else {
        // Mostrar mensaje de error en pantalla
        errorMessage.textContent = data.message;
      }
   */ } catch (error) {
      console.error("Error:", error);
      errorMessage.textContent = "Error en el servidor. Inténtalo más tarde.";
    }
  });
});
