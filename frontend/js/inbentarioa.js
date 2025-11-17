const apiKey = sessionStorage.getItem("apiKey");

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

  // ==================
  // CESTA DE LA COMPRA
  // ==================
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

      // Checkbox para decidir si se crean etiquetas automáticamente
      li.innerHTML = `
      <div>
        <strong>${item.nombre}</strong> - Cantidad: ${item.cantidad}
      </div>
      <div class="form-check form-check-inline">
      </div>
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

  const headers = {
    "Content-Type": "application/json",
    "Authorization": `Bearer ${apiKey}`,
  };
  function cargarInventario() {
    fetch('../backend/controladores/inbentarioController.php', { headers })
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
              <input type="checkbox" class="select-etiqueta"/>
              <button class="btn btn-sm btn-outline-danger eliminar-btn">🗑️</button>
              
            </span>
          `;
          inventoryList.appendChild(div);
        });
      })
      .catch(err => console.error('Error al cargar inventario:', err));
  }

  // ============================
  // BÚSQUEDA GLOBAL DE EQUIPOS
  // ============================
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

  // ================
  //  EQUIPAMIENTOS
  // ================
  function cargarEquipamientos() {
    fetch('../backend/controladores/ekipamenduakController.php', { headers })
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

  //==================
  // MODAL DE COMPRA 
  //==================
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

    cesta.push({ id: idEquipo, nombre: nombreEquipo, cantidad });
    Swal.fire({ icon: 'success', title: 'Añadido', text: `${nombreEquipo} Saskira gehituta` });
    nuevaCategoriaModal.hide();
    nuevaCategoriaInput.value = '';
    equipoSelect.value = '';
    actualizarCesta();
  };

  // ==================
  // FINALIZAR COMPRA
  // ==================



  finalizarCompraBtn.addEventListener('click', () => {
    if (cesta.length === 0) {
      mostrarInfo('Saskia hutsik dago!');
      return;
    }

    cesta.forEach((item, index) => {
      fetch('../backend/controladores/inbentarioController.php', {
        method: 'POST',
        headers,
        body: JSON.stringify({
          action: 'STOCK',
          idEkipamendu: item.id,
          cantidad: item.cantidad,
        })

      })
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            mostrarInfo(data.message);
            return;
          }

          if (crearEtiquetas) {
            // Mostrar etiquetas recién creadas en inventario con botón eliminar
            data.nuevas_etiquetas.forEach(etk => {
              const etiquetaDiv = document.createElement('div');
              etiquetaDiv.classList.add('data-row');
              etiquetaDiv.dataset.etiketa = etk;
              etiquetaDiv.innerHTML = `
              <span>${item.nombre}</span>
              <span>${etk}</span>
              <span>Kokaleku ezezaguna</span>
              <span class="col-ekintzak">
                <input type="checkbox" class="select-etiqueta"/>
                <button class="btn btn-sm btn-primary eliminar-btn">
                  <i class="bi bi-trash"></i>
                </button>
              </span>
            `;
              inventoryList.appendChild(etiquetaDiv);
            });
          }

          // Actualizar stock visual si existe
          const stockSpan = document.querySelector(`#stock-${item.id}`);
          if (stockSpan) stockSpan.textContent = data.nuevo_stock;

          cestaModal.hide();
          cesta = [];
          actualizarCesta();
        })
        .catch(err => {
          console.error(err);
          mostrarInfo('Ez da erosketa prozesatu.');
        });
    });
  });


  // ==================
  // ELIMINAR ETIQUETA
  // ==================
  function mostrarConfirmacionEtiketa(message) {
    return new Promise((resolve) => {
      const modalEl = document.getElementById('confirmModal');
      const modalBody = document.getElementById('confirmModalBody');
      const yesBtn = document.getElementById('confirmModalYesBtn');

      const modal = new bootstrap.Modal(modalEl);
      modalBody.textContent = message;

      function onYes() {
        resolve(true);
        yesBtn.removeEventListener('click', onYes);
        modal.hide();
      }

      yesBtn.addEventListener('click', onYes);

      modalEl.addEventListener('hidden.bs.modal', () => {
        resolve(false); // Si se cierra el modal sin pulsar "Sí"
        yesBtn.removeEventListener('click', onYes);
      }, { once: true });

      modal.show();
    });
  }


  async function eliminarEtiqueta(row, etiketa) {
    const confirmado = await mostrarConfirmacionEtiketa(`¿Deseas eliminar la etiqueta ${etiketa}?`);
    if (!confirmado) return;

    try {
      const response = await fetch('../backend/controladores/inbentarioController.php', {
        method: 'DELETE',
        headers,
        body: JSON.stringify({ action: 'DELETE', etiketa })
      });

      const data = await response.json();

      if (data.success) {
        row.remove();
        const stockCell = document.querySelector(`#stock-${data.idEkipamendu}`);
        if (stockCell) stockCell.textContent = data.nuevo_stock;
        console.log(data.message);
      } else {
        alert(data.message || 'Error al eliminar la etiqueta'); // Puedes reemplazar esto también por otro modal
      }
    } catch (err) {
      console.error(err);
      alert('Error en la petición al servidor'); // También se puede reemplazar por modal
    }
  }


  // Delegación de eventos para eliminar etiquetas

  function mostrarInfo(message) {
    const modalEl = document.getElementById('infoModal');
    const modalBody = document.getElementById('infoModalBody');
    modalBody.textContent = message;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  }


  inventoryList.addEventListener('click', (e) => {
    const button = e.target.closest('.eliminar-btn');
    if (!button) return;
    const row = button.closest('.data-row');
    const etiketa = row.dataset.etiketa;
    eliminarEtiqueta(row, etiketa);
  });

  document.getElementById('eliminar-seleccionadas').addEventListener('click', async () => {
    const selectedCheckboxes = document.querySelectorAll('.select-etiqueta:checked');
    if (selectedCheckboxes.length === 0) {
      mostrarInfo('No hay etiquetas seleccionadas');
      return;
    }

    const confirmado = await mostrarConfirmacionEtiketa(`¿Deseas eliminar ${selectedCheckboxes.length} etiquetas?`);
    if (!confirmado) return;

    const etiquetas = Array.from(selectedCheckboxes).map(cb => cb.closest('.data-row').dataset.etiketa);

    try {
      const response = await fetch('../backend/controladores/inbentarioController.php', {
        method: 'POST',
        headers,
        body: JSON.stringify({ action: 'DELETE_MULTIPLE', etiquetas })
      });

      const data = await response.json();

      if (data.success) {
        selectedCheckboxes.forEach(cb => cb.closest('.data-row').remove());
        mostrarInfo(data.message);
      } else {
        mostrarInfo(data.message || 'Error al eliminar etiquetas');
      }
    } catch (err) {
      console.error(err);
      mostrarInfo('Error al contactar con el servidor');
    }
  });


  // ===== INICIAL =====
  cargarInventario();
  cargarEquipamientos();
  actualizarCesta();
});
