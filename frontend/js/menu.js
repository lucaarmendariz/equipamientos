document.addEventListener("DOMContentLoaded", () => {
  // --- MENÚ MÓVIL ---
  const menuIcon = document.querySelector(".menu-icon");
  const nav = document.getElementById("main-nav");

  // Toggle del menú en móvil
  menuIcon.addEventListener("click", () => {
    nav.classList.toggle("active");
  });

  // --- MARCAR LINK ACTIVO ---
  const navLinks = document.querySelectorAll("#main-nav a");
  const currentPage = window.location.pathname.split("/").pop(); // obtener nombre de la página

  navLinks.forEach(link => {
    if (link.getAttribute("href") === currentPage) {
      link.classList.add("active");
    }
  });
});
