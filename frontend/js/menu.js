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

  const userIcon = document.querySelector(".user-icon");
  const userMenu = userIcon.querySelector(".user-menu");
  const logoutButton = document.getElementById("logout-button");

  // Toggle del menú
  userIcon.addEventListener("click", (e) => {
    e.stopPropagation(); // para que no cierre al hacer click dentro
    userMenu.classList.toggle("active");
  });

  // Cerrar el menú al hacer click fuera
  document.addEventListener("click", () => {
    userMenu.classList.remove("active");
  });

  // Cerrar sesión
  logoutButton.addEventListener("click", (e) => {
    e.preventDefault();
    // Limpiar sesión y redirigir a login
    sessionStorage.clear();
    window.location.href = "../frontend/index.html";
  });

  const name = sessionStorage.getItem("name");
  const lastname = sessionStorage.getItem("lastname");
  const userNameSpan = document.getElementById("user-name");

  if (name && lastname) {
    userNameSpan.textContent = `${name} ${lastname}`;
  } else {
    userNameSpan.textContent = "Invitado";
  }
});
