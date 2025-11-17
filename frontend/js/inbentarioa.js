document.addEventListener("DOMContentLoaded", () => {
  // ===== ELEMENTOS DEL DOM =====
  const inventoryList = document.getElementById('inventory-list');
  const equipoSelect = document.getElementById('equipo-select');
  const nuevaCategoriaBtn = document.getElementById('nueva-categoria-btn');
  const nuevaCategoriaModalEl = document.getElementById('nuevaCategoriaModal');
  const nuevaCategoriaInput = document.getElementById('nueva-categoria-input');
  const guardarCategoriaBtn = document.getElementById('guardar-categoria');
  const searchInput = document.getElementById('searchInput');

  const carritoBtn = document.getElementById('carrito-btn');
  const cestaModalEl = document.getElementById('cestaModal');
  const cestaList = document.getElementById('cesta-list');
  const vaciarCestaBtn = document.getElementById('vaciar-cesta');
  const finalizarCompraBtn = document.getElementById('finalizar-compra');
  const eliminarSeleccionadasBtn = document.getElementById('eliminar-seleccionadas');

  // ===== Inicializamos modales de Bootstrap solo si existen =====
  const nuevaCategoriaModal = nuevaCategoriaModalEl ? new bootstrap.Modal(nuevaCategoriaModalEl) : null;
  const cestaModal = cestaModalEl ? new bootstrap.Modal(cestaModalEl) : null;

  const eliminarEtiquetaModalEl = document.getElementById('eliminarEtiquetaModal');
  const eliminarEtiquetaModal = eliminarEtiquetaModalEl ? new bootstrap.Modal(eliminarEtiquetaModalEl) : null;

  const eliminarEtiquetasMasivasModalEl = document.getElementById('eliminarEtiquetasMasivasModal');
  const eliminarEtiquetasMasivasModal = eliminarEtiquetasMasivasModalEl ? new bootstrap.Modal(eliminarEtiquetasMasivasModalEl) : null;

  const etiquetaAEliminarSpan = document.getElementById('etiqueta-a-eliminar');
  const confirmarEliminarEtiquetaBtn = document.getElementById('confirmarEliminarEtiqueta');
  const cantidadEtiquetasSpan = document.getElementById('cantidad-etiquetas');
  const confirmarEliminarEtiquetasMasivasBtn = document.getElementById('confirmarEliminarEtiquetasMasivas');

  let etiquetaSeleccionada = null;
  let filaSeleccionada = null;
  let etiquetasSeleccionadas = [];

  // ==================
  // CESTA DE LA COMPRA
  // ==================
  let cesta = [];

  function actualizarCesta() {
    if (!cestaList) return;
    cestaList.innerHTML = '';
    if (cesta.length === 0) {
      cestaList.innerHTML = '<li class="list-group-item">Saskia hutsik dago</li>';
      return;
    }
    cesta.forEach((item, index) => {
      const li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center';
      li.innerHTML = `
        ${item.nombre} - Kantitatea: ${item.cantidad}
        <button class="btn btn-sm btn-outline-danger" onclick="eliminarItem(${index})">&times;</button>
      `;
      cestaList.appendChild(li);
    });
  }

  window.eliminarItem = function (index) {
    cesta.splice(index, 1);
    actualizarCesta();
  };

  vaciarCestaBtn?.addEventListener('click', () => {
    cesta = [];
    actualizarCesta();
  });

  carritoBtn?.addEventListener('click', () => {
    actualizarCesta();
    cestaModal?.show();
  });

  // =========== INVENTARIO ===========
  function cargarInventario() {
    fetch('../backend/controladores/inbentarioController.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) {
          console.error('Error al cargar inventario:', res.message);
          return;
        }
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
              <input type="checkbox" class="select-etiqueta"/>
              <button class="btn btn-sm btn-outline-danger eliminar-btn">🗑️</button>
            </span>
          `;
          inventoryList.appendChild(div);
        });
      })
      .catch(err => console.error('Error al cargar inventario:', err));
  }

  // ======= BÚSQUEDA GLOBAL =======
  searchInput?.addEventListener("input", () => {
    const filtro = searchInput.value.trim().toLowerCase();
    const filas = inventoryList.querySelectorAll(".data-row");
    filas.forEach(fila => {
      const textoFila = fila.innerText.toLowerCase();
      fila.style.display = textoFila.includes(filtro) ? "" : "none";
    });
  });

  // ======= CARGAR EQUIPOS =======
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
          option.value = equipo.id;
          option.textContent = equipo.izena;
          equipoSelect.appendChild(option);
        });
      })
      .catch(err => console.error('Error fetching equipamientos:', err));
  }

  // ===== MODAL DE COMPRA =====
  nuevaCategoriaBtn?.addEventListener('click', () => {
    nuevaCategoriaInput.value = '';
    guardarCategoriaBtn.textContent = 'Gehitu saskira';
    nuevaCategoriaModal?.show();
  });

  guardarCategoriaBtn?.addEventListener('click', () => {
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
    cesta.push({ id: idEquipo, nombre: nombreEquipo, cantidad });
    Swal.fire({ icon: 'success', title: 'Gehituta', text: `${nombreEquipo} Saskira gehituta` });
    nuevaCategoriaModal?.hide();
    nuevaCategoriaInput.value = '';
    equipoSelect.value = '';
    actualizarCesta();
  });

  // ===== FINALIZAR COMPRA =====
  finalizarCompraBtn?.addEventListener('click', () => {
    if (cesta.length === 0) {
      Swal.fire({ icon: 'error', title: 'Errorea', text: 'Saskia hutsik dago!' });
      return;
    }

    cesta.forEach(item => {
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
          if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Errorea', text: data.message });
            return;
          }

          data.nuevas_etiquetas.forEach(etk => {
            const etiquetaDiv = document.createElement('div');
            etiquetaDiv.classList.add('data-row');
            etiquetaDiv.dataset.etiketa = etk;
            etiquetaDiv.innerHTML = `
              <span>${item.nombre}</span>
              <span>${etk}</span>
              <span>Kokaleku ezezaguna</span>
              <span class="col-ekintzak d-inline-flex align-items-center">
                <input type="checkbox" class=" form-check-input select-etiqueta me-5"/>
                <button class="btn btn-sm btn-outline-danger eliminar-btn">🗑️</button>
              </span>
            `;
            inventoryList.appendChild(etiquetaDiv);
          });

          Swal.fire({
            icon: 'success',
            title: 'Erosketa osatuta!',
            text: 'Stock eguneratu da eta etiketak sortu dira.'
          });

          cesta = [];
          actualizarCesta();
        })
        .catch(err => {
          console.error("Error catch JS:", err);
          Swal.fire({ icon: 'error', title: 'Errorea', text: 'Ez da erosketa prozesatu.' });
        });
    });
  });

  // ==================
  // ELIMINAR ETIQUETA INDIVIDUAL
  // ==================
  inventoryList?.addEventListener('click', (e) => {
    const button = e.target.closest('.eliminar-btn');
    if (!button) return;

    const row = button.closest('.data-row');
    const etiketa = row.dataset.etiketa;

    etiquetaSeleccionada = etiketa;
    filaSeleccionada = row;
    if (etiquetaAEliminarSpan) etiquetaAEliminarSpan.textContent = etiketa;

    eliminarEtiquetaModal?.show();
  });

  confirmarEliminarEtiquetaBtn?.addEventListener('click', async () => {
    if (!etiquetaSeleccionada || !filaSeleccionada) return;

    try {
      const response = await fetch(`../backend/controladores/inbentarioController.php`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'DELETE', etiketa: etiquetaSeleccionada })
      });
      const data = await response.json();

      if (data.success) {
        filaSeleccionada.remove();
        Swal.fire({ icon: 'success', title: 'Ezabatu', text: data.message });
      } else {
        Swal.fire({ icon: 'error', title: 'Errorea', text: data.message || 'Ezin izan da ezabatu' });
      }
    } catch (err) {
      console.error(err);
      Swal.fire({ icon: 'error', title: 'Errorea', text: 'Errorea zerbitzarira konektatzean' });
    } finally {
      eliminarEtiquetaModal?.hide();
      etiquetaSeleccionada = null;
      filaSeleccionada = null;
    }
  });

  // ==================
  // ELIMINAR ETIQUETAS MASIVAS
  // ==================
  eliminarSeleccionadasBtn?.addEventListener('click', () => {
    const selected = document.querySelectorAll('.select-etiqueta:checked');
    if (selected.length === 0) {
      Swal.fire({ icon: 'info', title: 'Hautaturik gabe', text: 'Ez daude etiketa hautatuta' });
      return;
    }

    etiquetasSeleccionadas = Array.from(selected).map(cb => cb.closest('.data-row').dataset.etiketa);
    if (cantidadEtiquetasSpan) cantidadEtiquetasSpan.textContent = etiquetasSeleccionadas.length;
    eliminarEtiquetasMasivasModal?.show();
  });

  confirmarEliminarEtiquetasMasivasBtn?.addEventListener('click', async () => {
    if (etiquetasSeleccionadas.length === 0) return;

    try {
      const response = await fetch(`../backend/controladores/inbentarioController.php`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'DELETE_MULTIPLE', etiquetas: etiquetasSeleccionadas })
      });
      const data = await response.json();

      if (data.success) {
        etiquetasSeleccionadas.forEach(et => {
          const fila = inventoryList.querySelector(`.data-row[data-etiketa="${et}"]`);
          if (fila) fila.remove();
        });
        Swal.fire({ icon: 'success', title: 'Ezabatuak', text: data.message });
      } else {
        Swal.fire({ icon: 'error', title: 'Errorea', text: data.message || 'Errorea ezabatzean' });
      }
    } catch (err) {
      console.error(err);
      Swal.fire({ icon: 'error', title: 'Errorea', text: 'Errorea zerbitzarira konektatzean' });
    } finally {
      eliminarEtiquetasMasivasModal?.hide();
      etiquetasSeleccionadas = [];
    }
  });

  // ===== INICIAL =====
  cargarInventario();
  cargarEquipamientos();
  actualizarCesta();
});
