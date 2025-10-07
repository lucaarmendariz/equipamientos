document.getElementById('loginForm').addEventListener('submit', function(e) {
  const username = this.username.value.trim();
  const password = this.password.value.trim();

  if (username.length === 0 || password.length === 0) {
    e.preventDefault();
    alert('Por favor, rellena todos los campos.');
  }
});
