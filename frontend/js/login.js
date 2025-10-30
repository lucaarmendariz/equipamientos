document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(form);

    try {
      const response = await fetch(CONFIG.BASE_URL + "backend/controladores/loginController.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        // Guardar datos del usuario en sessionStorage
        sessionStorage.setItem("userRole", data.rola);
        sessionStorage.setItem("username", data.erabiltzailea);
        sessionStorage.setItem("name", data.izena);
        sessionStorage.setItem("lastname", data.abizena);

        // Redirigir al dashboard o menú principal
        window.location.href = data.redirect;
      } else {
        // Mostrar mensaje de error
        const errorLabel = document.getElementById("error-message");
        if (errorLabel) {
          errorLabel.textContent = data.message;
        } else {
          alert(data.message);
        }
      }
    } catch (error) {
      console.error("Error al iniciar sesión:", error);
      alert("Error en el servidor. Inténtalo de nuevo más tarde.");
    }
  });
});
