document.addEventListener("DOMContentLoaded", () => {
  // --- CONTROL DE SESIÓN Y ROLES ---
  const role = sessionStorage.getItem("userRole");

  if (!role) {
    alert("Sesión no válida. Por favor, inicia sesión de nuevo.");
    window.location.href = "../frontend/index.html"; // redirigir al login
    return;
  }

  // --- CONTROL DE ACCESO POR PÁGINA ---
  const currentPage = window.location.pathname.split("/").pop(); // obtener el nombre de la página

  // Páginas de administración de usuarios
  const protectedPages = ["usuarios.html", "usuarios-detalle.html", "usuarios-editar.html"]; 

  if (protectedPages.includes(currentPage) && role.toLowerCase() !== "a") {
    alert("No tienes permisos para acceder a esta página.");
    window.location.href = "../frontend/menu.html"; // redirigir a una página segura
    return;
  }

  // --- OCULTAR ELEMENTOS DEL MENÚ PARA NO ADMIN ---
  if (role.toLowerCase() !== "a") {
    const usuariosContainer = document.getElementById("usuarios-container");
    const usuariosLink = Array.from(document.querySelectorAll("nav a"))
      .find(link => link.textContent.includes("Erabiltzaileak"));

    if (usuariosContainer) usuariosContainer.style.display = "none";
    if (usuariosLink) usuariosLink.style.display = "none";
  }
});
