const apiKey = sessionStorage.getItem("apiKey");

document.addEventListener("DOMContentLoaded", () => {
  const inbentarioURL = CONFIG.BASE_URL + "controladores/inbentarioController.php";
  const ekipamenduURL = CONFIG.BASE_URL + "controladores/ekipamenduakController.php";

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

  const nuevaCategoriaModal = nuevaCategoriaModalEl ? new bootstrap.Modal(nuevaCategoriaModalEl) : null;
  const cestaModal = cestaModalEl ? new bootstrap.Modal(cestaModalEl) : null;

  let cesta = [];

  function actualizarCesta() {
    if (!cestaList) return;
    cestaList.innerHTML = '';
    if (cesta.length === 0) {
      cestaList.innerHTML = '<li class="list-group-item">Zerrenda hutsik dago</li>';
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

  const headers = {
    "Content-Type": "application/json",
    "Authorization": `Bearer ${apiKey}`,
  };

  function cargarInventario() {
    fetch(inbentarioURL, { headers })
      .then(r => r.json())
      .then(res => {
        if (!res.success) return;
        inventoryList.innerHTML = '';
        if (res.data.length === 0) {
          inventoryList.innerHTML = `<div class="data-row text-center">Ez dago aktibo dagoen stockik.</div>`;
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
      .catch(err => console.error('Errorea stocka kargatzean:', err));
  }

  searchInput?.addEventListener("input", () => {
    const filtro = searchInput.value.trim().toLowerCase();
    const filas = inventoryList.querySelectorAll(".data-row");
    filas.forEach(fila => {
      const textoFila = fila.innerText.toLowerCase();
      fila.style.display = textoFila.includes(filtro) ? "" : "none";
    });
  });

  function cargarEquipamientos() {
    fetch(ekipamenduURL, { headers })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          console.error('Errorea ekipamenduak kargatzean:', data.message);
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
      .catch(err => console.error('Errorea ekipamenduak lortzean:', err));
  }

  nuevaCategoriaBtn?.addEventListener('click', () => {
    nuevaCategoriaInput.value = '';
    guardarCategoriaBtn.textContent = 'Zerrendara gehitu';
    nuevaCategoriaModal?.show();
  });

  guardarCategoriaBtn?.addEventListener('click', () => {
    const idEquipo = parseInt(equipoSelect.value);
    const cantidad = parseInt(nuevaCategoriaInput.value.trim());

    if (!idEquipo) {
      Swal.fire({ icon: 'info', title: 'Informazioa', text: 'Aukeratu ekipamendu bat' });
      return;
    }
    if (!cantidad || cantidad <= 0) {
      Swal.fire({ icon: 'info', title: 'Informazioa', text: 'Sartu kantitate bat' });
      return;
    }

    const nombreEquipo = equipoSelect.options[equipoSelect.selectedIndex].text;
    cesta.push({ id: idEquipo, nombre: nombreEquipo, cantidad });
    Swal.fire({ icon: 'success', title: 'Gehituta', text: `${nombreEquipo} zerrendara gehituta` });
    nuevaCategoriaModal?.hide();
    nuevaCategoriaInput.value = '';
    equipoSelect.value = '';
    actualizarCesta();
  });

  finalizarCompraBtn.addEventListener('click', () => {
    if (cesta.length === 0) {
      mostrarInfo('Zerrenda hutsik dago!');
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

          const stockSpan = document.querySelector(`#stock-${item.id}`);
          if (stockSpan) stockSpan.textContent = data.nuevo_stock;

          cestaModal.hide();
          cesta = [];
          actualizarCesta();
        })
        .catch(err => {
          console.error(err);
          mostrarInfo('Erosketa ezin izan da prozesatu.');
        });
    });
  });

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
        resolve(false);
        yesBtn.removeEventListener('click', onYes);
      }, { once: true });

      modal.show();
    });
  }

  async function eliminarEtiqueta(row, etiketa) {
    const confirmado = await mostrarConfirmacion(`Ziur zaude ${etiketa} etiketa ezabatu nahi duzula?`);
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
        mostrarInfo(data.message || 'Etiqueta ondo ezabatuta', 'success');
      } else {
        mostrarInfo("Ezinezkoa etiketa ezabatzea, kokaleku bat du.");
      }

    } catch (err) {
      console.error(err);
      mostrarInfo('Errorea zerbitzariarekin konektatzean', 'error');
    }
  }

  function mostrarInfo(message, tipo = 'info') {
    const modalEl = document.getElementById('infoModal');
    const modalTitle = document.getElementById('infoModalTitle');
    const modalBody = document.getElementById('infoModalBody');
    const modalHeader = document.getElementById('infoModalHeader');

    modalBody.textContent = message;

    modalHeader.className = 'modal-header';
    if(tipo === 'success') modalHeader.classList.add('bg-success', 'text-white');
    else if(tipo === 'error') modalHeader.classList.add('bg-danger', 'text-white');
    else modalHeader.classList.add('bg-primary', 'text-white');

    modalTitle.textContent = tipo === 'success' ? 'Arrakasta' : tipo === 'error' ? 'Errorea' : 'Informazioa';

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  }

  async function eliminarSeleccionadas() {
    const selectedCheckboxes = document.querySelectorAll('.select-etiqueta:checked');
    if(selectedCheckboxes.length === 0) {
      mostrarInfo('Ez da etiketa aukeraturik');
      return;
    }

    const confirmado = await mostrarConfirmacion(`Ziur zaude ${selectedCheckboxes.length} etiketa ezabatu nahi dituzula?`);
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
        mostrarInfo(data.message || 'Etiquetas ondo ezabatuta', 'success');
      } else {
        mostrarInfo("Ezinezkoa etiketa guztiak ezabatzea, kokaleku bat dute.");
      }

    } catch (err) {
      console.error(err);
      mostrarInfo('Errorea zerbitzariarekin konektatzean', 'error');
    }
  }

  inventoryList.addEventListener('click', (e) => {
    const button = e.target.closest('.eliminar-btn');
    if(!button) return;
    const row = button.closest('.data-row');
    const etiketa = row.dataset.etiketa;
    eliminarEtiqueta(row, etiketa);
  });

  eliminarSeleccionadasBtn?.addEventListener('click', eliminarSeleccionadas);

  cargarInventario();
  cargarEquipamientos();
  actualizarCesta();
});
