document.addEventListener("DOMContentLoaded", () => {
  // ===== ELEMENTOS =====
  const inventoryList = document.getElementById('inventory-list');
  const equipoSelect = document.getElementById('equipo-select');
  const nuevaCategoriaBtn = document.getElementById('nueva-categoria-btn');
  const nuevaCategoriaModal = new bootstrap.Modal(document.getElementById('nuevaCategoriaModal'));
  const nuevaCategoriaInput = document.getElementById('nueva-categoria-input');
  const guardarCategoriaBtn = document.getElementById('guardar-categoria');
  const searchInput = document.getElementById("searchInput");

  const carritoBtn = document.getElementById('carrito-btn');
  const cestaModalEl = document.getElementById('cestaModal');
  const cestaModal = new bootstrap.Modal(cestaModalEl);
  const cestaList = document.getElementById('cesta-list');
  const vaciarCestaBtn = document.getElementById('vaciar-cesta');
  const finalizarCompraBtn = document.getElementById('finalizar-compra');

  // ===== CESTA =====
  let cesta = [];

  function actualizarCesta() {
    cestaList.innerHTML = '';
    if (cesta.length === 0) {
      cestaList.innerHTML = '<li class="list-group-item">La cesta está vacía</li>';
      return;
    }
    cesta.forEach((item, index) => {
      const li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center';
      li.innerHTML = `
        ${item.nombre} - Cantidad: ${item.cantidad}
        <button class="btn btn-sm btn-outline-danger" onclick="eliminarItem(${index})">&times;</button>
      `;
      cestaList.appendChild(li);
    });
  }

  window.eliminarItem = function (index) {
    cesta.splice(index, 1);
    actualizarCesta();
  };

  vaciarCestaBtn.addEventListener('click', () => {
    cesta = [];
    actualizarCesta();
  });

  carritoBtn.addEventListener('click', () => {
    actualizarCesta();
    cestaModal.show();
  });

  // ================== INVENTARIO ==================
  function cargarInventario() {
    fetch('../backend/controladores/inbentarioController.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) {
          console.error('Error al cargar inventario:', res.message);
          return;
        }
        console.log('Inventario cargado:', res.data);
        inventoryList.innerHTML = '';
        res.data.forEach(item => {
          const div = document.createElement('div');
          div.className = 'data-row';
          div.dataset.etiketa = item.etiketa;
          div.innerHTML = `
            <span>${item.ekipamendua}</span>
            <span>${item.etiketa}</span>
            <span>${item.gela ?? 'Kokaleku ezezaguna'}</span>
            <span class="col-ekintzak">
              <button class="btn btn-sm btn-primary eliminar-btn">
                <i class="bi bi-trash"></i>
              </button>
            </span>
          `;
          inventoryList.appendChild(div);
        });
      })
      .catch(err => console.error('Error al cargar inventario:', err));
  }

  // ============================================================
  // BÚSQUEDA GLOBAL DE EQUIPOS (IGNORA MAYÚSCULAS Y LA CABECERA)
  // ============================================================
  if (searchInput && inventoryList) {
    searchInput.addEventListener("input", () => {
      const filtro = searchInput.value.trim().toLowerCase();
      const filas = inventoryList.querySelectorAll(".data-row");

      filas.forEach(fila => {
        const textoFila = fila.innerText.toLowerCase();
        fila.style.display = textoFila.includes(filtro) ? "" : "none";
      });
    });
  }

  // ================== EQUIPAMIENTOS ==================
  function cargarEquipamientos() {
    fetch('../backend/controladores/ekipamenduakController.php')
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          console.error('Error al cargar equipamientos:', data.message);
          return;
        }
        equipoSelect.innerHTML = '<option value="">-- Aukeratu ekipamendua --</option>';
        data.data.forEach(equipo => {
          const option = document.createElement('option');
          option.value = equipo.id; // Enviamos ID al backend
          option.textContent = equipo.izena;
          equipoSelect.appendChild(option);
        });
      })
      .catch(err => console.error('Error fetching equipamientos:', err));
  }

  // ================== MODAL DE COMPRA ==================
  nuevaCategoriaBtn.onclick = () => {
    nuevaCategoriaInput.value = '';
    guardarCategoriaBtn.textContent = 'Gehitu saskira';
    nuevaCategoriaModal.show();
  };

  guardarCategoriaBtn.onclick = () => {
    const idEquipo = parseInt(equipoSelect.value);
    const cantidad = parseInt(nuevaCategoriaInput.value.trim());

    if (!idEquipo) {
      Swal.fire({ icon: 'info', title: 'Info', text: 'Ekipamendu bat aukeratu' });
      return;
    }
    if (!cantidad || cantidad <= 0) {
      Swal.fire({ icon: 'info', title: 'Info', text: 'artu balio zuzen bat' });
      return;
    }

    const nombreEquipo = equipoSelect.options[equipoSelect.selectedIndex].text;

    // Añadir a la cesta
    cesta.push({ id: idEquipo, nombre: nombreEquipo, cantidad });
    Swal.fire({ icon: 'success', title: 'Añadido', text: `${nombreEquipo} Saskira gehituta` });
    nuevaCategoriaModal.hide();
    nuevaCategoriaInput.value = '';
    equipoSelect.value = '';
    actualizarCesta();
  };

  // ================== FINALIZAR COMPRA ==================
  finalizarCompraBtn.addEventListener('click', () => {
    if (cesta.length === 0) {
      Swal.fire({ icon: 'error', title: 'Errorea', text: 'Saskia hutsik dago!' });
      return;
    }

    cesta.forEach(item => {
      console.log("Enviando compra:", item);

      fetch('../backend/controladores/inbentarioController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'STOCK',
          idEkipamendu: item.id,
          cantidad: item.cantidad
        })
      })
        .then(res => res.json())
        .then(data => {
          console.log("Respuesta JSON:", data);

          if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Errorea', text: data.message });
            return;
          }

          // Mostrar etiquetas recién creadas en inventario
          data.nuevas_etiquetas.forEach(etk => {
            const etiquetaDiv = document.createElement('div');
            etiquetaDiv.classList.add('data-row');
            etiquetaDiv.innerHTML = `
            <span>${item.nombre}</span>
            <span>${etk}</span>
            <span>Kokaleku ezezaguna</span>
          `;
            document.getElementById('inventory-list').appendChild(etiquetaDiv);
          });

          // ==== ACTUALIZAR STOCK VISUAL EN INVENTARIO ====
          const stockSpan = document.querySelector(`#stock-${item.id}`);
          if (stockSpan) {
            stockSpan.textContent = data.nuevo_stock;
          }

          Swal.fire({
            icon: 'success',
            title: 'Erosketa osatuta!',
            text: 'Stock eguneratu da eta etiketak sortu dira.'
          });

          // Limpiar cesta
          cesta = [];
          actualizarCesta();
        })
        .catch(err => {
          console.error("Error catch JS:", err);
          Swal.fire({ icon: 'error', title: 'Errorea', text: 'Ez da erosketa prozesatu.' });
        });
    });
  });
// ================== FUNCIONALIDAD ELIMINAR ETIQUETA ==================
async function eliminarEtiqueta(row, etiketa) {
  if (!confirm(`¿Deseas eliminar la etiqueta ${etiketa}?`)) return;

  try {
    const response = await fetch('../backend/controladores/inbentarioController.php', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'DELETE', etiketa })
    });

    const data = await response.json();

    if (data.success) {
      // Elimina la fila de la interfaz
      row.remove();

      // Actualiza el stock en la tabla de equipamientos si existe
      const stockCell = document.querySelector(`#stock-${data.idEkipamendu}`);
      if (stockCell) stockCell.textContent = data.nuevo_stock;

      console.log(data.message);
    } else {
      alert(data.message || 'Error al eliminar la etiqueta');
    }
  } catch (err) {
    console.error(err);
    alert('Error en la petición al servidor');
  }
}


// Delegación de eventos para eliminar etiquetas (funciona con filas dinámicas)
inventoryList.addEventListener('click', (e) => {
  const button = e.target.closest('.eliminar-btn');
  if (!button) return;
  const row = button.closest('.data-row');
  const etiketa = row.dataset.etiketa;
  eliminarEtiqueta(row, etiketa);
});

  // ===== INICIAL =====
  cargarInventario();
  cargarEquipamientos();
  actualizarCesta();

});
