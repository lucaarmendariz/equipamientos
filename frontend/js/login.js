document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(form);

    try {
      const response = await fetch("../backend/login.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        // Guardar el rol en sessionStorage
        sessionStorage.setItem("userRole", data.role);

        // Redirigir al dashboard
        window.location.href = data.redirect;
      } else {
        alert(data.message);
      }
    } catch (error) {
      console.error("Error al iniciar sesión:", error);
      alert("Error en el servidor. Inténtalo de nuevo más tarde.");
    }
  });
});
