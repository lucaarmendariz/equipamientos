const kategoriaLink = document.getElementById('kategoria-link');
const ekipamenduakLink = document.getElementById('ekipamenduak-link');
const categoriaContent = document.getElementById('categoria-content');
const ekipamenduakContent = document.getElementById('ekipamenduak-content');
const overlay = document.getElementById('overlay');
const categoriaList = document.getElementById('categoria-list');
const ekipamenduakList = document.getElementById('ekipamenduak-list');

function hideAll() {
  categoriaContent.style.display = 'none';
  ekipamenduakContent.style.display = 'none';
  overlay.style.display = 'none';
}

function loadCategorias() {
  fetch('/api/kategoria')  // Cambia a tu endpoint real
    .then(response => response.json())
    .then(categorias => {
      categoriaList.innerHTML = '';
      categorias.forEach(cat => {
        const li = document.createElement('li');
        li.textContent = cat.name;
        categoriaList.appendChild(li);
      });
    })
    .catch(error => {
      categoriaList.innerHTML = '<li>Error al cargar categorías</li>';
      console.error(error);
    });
}

function loadEkipamenduak() {
  fetch('/api/ekipamenduak') // Cambia a tu endpoint real
    .then(response => response.json())
    .then(ekipamenduak => {
      ekipamenduakList.innerHTML = '';
      ekipamenduak.forEach(equi => {
        const li = document.createElement('li');
        li.textContent = equi.name;
        ekipamenduakList.appendChild(li);
      });
    })
    .catch(error => {
      ekipamenduakList.innerHTML = '<li>Error al cargar ekipamenduak</li>';
      console.error(error);
    });
}

kategoriaLink.addEventListener('click', (e) => {
  e.preventDefault();
  loadCategorias();
  categoriaContent.style.display = 'block';
  ekipamenduakContent.style.display = 'none';
  overlay.style.display = 'block';
});

ekipamenduakLink.addEventListener('click', (e) => {
  e.preventDefault();
  loadEkipamenduak();
  ekipamenduakContent.style.display = 'block';
  categoriaContent.style.display = 'none';
  overlay.style.display = 'block';
});

overlay.addEventListener('click', hideAll);
