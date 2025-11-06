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

      // Intentar parsear JSON
      const data = await response.json();

      if (data.success) {
        // ✅ Guardar datos del usuario y apiKey en sessionStorage
        sessionStorage.setItem("apiKey", data.apiKey); // 🔑 añadimos la API key
        sessionStorage.setItem("userRole", data.rola);
        sessionStorage.setItem("username", data.erabiltzailea);
        sessionStorage.setItem("name", data.izena);
        sessionStorage.setItem("lastname", data.abizena);

        // Redirigir al dashboard o menú principal
        window.location.href = data.redirect;
      } else {
        // ❌ Mostrar mensaje de error en el label o con alert
        const errorLabel = document.getElementById("error-message");
        if (errorLabel) {
          errorLabel.textContent = data.message || "Errorea saioan sartzean.";
          errorLabel.style.display = "block";
        } else {
          alert(data.message || "Errorea saioan sartzean.");
        }
      }
    } catch (error) {
      console.error("Error al iniciar sesión:", error);
      alert("Errorea zerbitzarian. Saiatu berriro geroago.");
    }
  });
});
