const apiKey = sessionStorage.getItem("apiKey");

document.addEventListener("DOMContentLoaded", () => {
  const inbentarioURL = CONFIG.BASE_URL + "backend/controladores/inbentarioController.php";
  const ekipamenduURL = CONFIG.BASE_URL + "backend/controladores/ekipamenduakController.php";

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

  // ==================
  // CESTA DE LA COMPRA
  // ==================
  let cesta = [];

  function actualizarCesta() {
    if (!cestaList) return;
    cestaList.innerHTML = '';
    if (cesta.length === 0) {
      cestaList.innerHTML = '<li class="list-group-item">Lista hutsik dago</li>';
      return;
    }

    cesta.forEach((item, index) => {
      const li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center';

      // Checkbox para decidir si se crean etiquetas automáticamente
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

  // ================== INVENTARIO ==================

  const headers = {
    "Content-Type": "application/json",
    "Authorization": `Bearer ${apiKey}`,
  };
  
  function cargarInventario() {
  fetch(inbentarioURL, { headers })
  .then(r => r.json())
  .then(res => {
      if (!res.success) return;
    console.log(res);
      inventoryList.innerHTML = '';
      if (res.data.length === 0) {
          inventoryList.innerHTML = `<div class="data-row text-center">No hay asignaciones activas.</div>`;
          return;
      }

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
    fetch(ekipamenduURL, { headers })
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
    guardarCategoriaBtn.textContent = 'Gehitu listara';
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
      Swal.fire({ icon: 'info', title: 'Info', text: 'Sartu kantitate bat' });
      return;
    }

    const nombreEquipo = equipoSelect.options[equipoSelect.selectedIndex].text;
    cesta.push({ id: idEquipo, nombre: nombreEquipo, cantidad });
    Swal.fire({ icon: 'success', title: 'Gehituta', text: `${nombreEquipo} Listara gehituta` });
    nuevaCategoriaModal?.hide();
    nuevaCategoriaInput.value = '';
    equipoSelect.value = '';
    actualizarCesta();
  });

  // ==================
  // FINALIZAR COMPRA
  // ==================



  finalizarCompraBtn.addEventListener('click', () => {
    if (cesta.length === 0) {
      mostrarInfo('Lista hutsik dago!');
      return;
    }

    cesta.forEach((item, index) => {
      fetch(inbentarioURL, {
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
                <button class="btn btn-sm btn-outline-danger eliminar-btn">🗑️</button>
              </span>
            `;
              inventoryList.appendChild(etiquetaDiv);
            });

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


 // Confirmación
function mostrarConfirmacion(message) {
  return new Promise(resolve => {
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
      resolve(false); // si cierra sin pulsar "Sí"
      yesBtn.removeEventListener('click', onYes);
    }, { once: true });

    modal.show();
  });
}

// Eliminar una etiqueta individual
async function eliminarEtiqueta(row, etiketa) {
  const confirmado = await mostrarConfirmacion(`¿Deseas eliminar la etiqueta ${etiketa}?`);
  if (!confirmado) return;

  try {
    const response = await fetch(inbentarioURL, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${apiKey}` },
      body: JSON.stringify({ action: 'DELETE', etiketa })
    });
    const data = await response.json();

    if (data.success) {
      row.remove();
      const stockCell = document.querySelector(`#stock-${data.idEkipamendu}`);
      if(stockCell) stockCell.textContent = data.nuevo_stock;
      mostrarInfo(data.message || 'Etiqueta eliminada correctamente', 'success');
    } else {
      mostrarInfo("No se ha podido eliminar la etiqueta porque tiene un kokaleku asignado");
    }

  } catch (err) {
    console.error(err);
    mostrarInfo('Error al conectar con el servidor', 'error');
  }
}





// Mostrar información (éxito o error)
function mostrarInfo(message, tipo = 'info') {
  const modalEl = document.getElementById('infoModal');
  const modalTitle = document.getElementById('infoModalTitle');
  const modalBody = document.getElementById('infoModalBody');
  const modalHeader = document.getElementById('infoModalHeader');

  modalBody.textContent = message;

  // Cambiar color del header según tipo
  modalHeader.className = 'modal-header';
  if(tipo === 'success') modalHeader.classList.add('bg-success', 'text-white');
  else if(tipo === 'error') modalHeader.classList.add('bg-danger', 'text-white');
  else modalHeader.classList.add('bg-primary', 'text-white');

  modalTitle.textContent = tipo === 'success' ? 'Éxito' : tipo === 'error' ? 'Error' : 'Info';

  const modal = new bootstrap.Modal(modalEl);
  modal.show();
}


  // Eliminar etiquetas seleccionadas
async function eliminarSeleccionadas() {
  const selectedCheckboxes = document.querySelectorAll('.select-etiqueta:checked');
  if(selectedCheckboxes.length === 0) {
    mostrarInfo('No hay etiquetas seleccionadas');
    return;
  }

  const confirmado = await mostrarConfirmacion(`¿Deseas eliminar ${selectedCheckboxes.length} etiquetas?`);
  if (!confirmado) return;

  const etiquetas = Array.from(selectedCheckboxes).map(cb => cb.closest('.data-row').dataset.etiketa);

  try {
    const response = await fetch(inbentarioURL, {
      method: 'POST',
      headers,
      body: JSON.stringify({ action: 'DELETE_MULTIPLE', etiquetas })
    });
    const data = await response.json();

    if (data.success) {
      selectedCheckboxes.forEach(cb => cb.closest('.data-row').remove());
      mostrarInfo(data.message || 'Etiquetas eliminadas correctamente', 'success');
    } else {
      mostrarInfo("No se han podido eliminar las etiquetas porque tienen un kokaleku asignado");
    }

  } catch (err) {
    console.error(err);
    mostrarInfo('Error al contactar con el servidor', 'error');
  }
}
// Individual
inventoryList.addEventListener('click', (e) => {
  const button = e.target.closest('.eliminar-btn');
  if(!button) return;
  const row = button.closest('.data-row');
  const etiketa = row.dataset.etiketa;
  eliminarEtiqueta(row, etiketa);
});

// Masiva
eliminarSeleccionadasBtn?.addEventListener('click', eliminarSeleccionadas);

  // ===== INICIAL =====
  cargarInventario();
  cargarEquipamientos();
  actualizarCesta();
});
