document.addEventListener('DOMContentLoaded', () => {

  // ================= ELEMENTOS =================
  const inventarioModal = document.getElementById('inventario-modal');
  const equipoModal = document.getElementById('equipo-modal');
  const overlay = document.getElementById('overlay');

  const closeInventario = document.getElementById('close-inventario-modal');
  const closeEquipo = document.getElementById('close-equipo-modal');

  const inventoryList = document.getElementById('inventory-list');
  const listaEquipos = document.getElementById('lista-equipos');

  const etiketaInput = document.getElementById('etiketa-input');
  const equipoSelect = document.getElementById('equipo-select');
  const fechaInput = document.getElementById('fecha-input');
  const guardarInventarioBtn = document.getElementById('guardar-inventario');

  const nombreInput = document.getElementById('nombre-input');
  const marcaInput = document.getElementById('marca-input');
  const modeloInput = document.getElementById('modelo-input');
  const stockInput = document.getElementById('stock-input');
  const categoriaSelect = document.getElementById('categoria-select');
  const descripcionInput = document.getElementById('descripcion-input');
  const guardarEquipoBtn = document.getElementById('guardar-equipo');

  // ================= FUNCIONES MODAL =================
  function abrirModal(modal) {
    modal.style.display = 'block';
    overlay.style.display = 'block';
  }

  function cerrarModal(modal) {
    modal.style.display = 'none';
    overlay.style.display = 'none';
  }

  closeInventario.addEventListener('click', () => cerrarModal(inventarioModal));
  closeEquipo.addEventListener('click', () => cerrarModal(equipoModal));
  overlay.addEventListener('click', () => {
    cerrarModal(inventarioModal);
    cerrarModal(equipoModal);
  });

  // ================= INVENTARIO =================
  function cargarInventario() {
    fetch('../backend/inventario.php')
      .then(res => res.json())
      .then(data => {
        if (data.success) mostrarInventario(data.data);
      });
  }

  function mostrarInventario(data) {
    inventoryList.innerHTML = '';
    data.forEach(item => {
      const div = document.createElement('div');
      div.classList.add('data-row');
      div.innerHTML = `
        <span>${item.etiketa}</span>
        <span>${item.erosketaData}</span>
        <span>
          <button class="editar-inventario">Editar</button>
          <button class="eliminar-inventario">Eliminar</button>
        </span>
      `;
      inventoryList.appendChild(div);
    });
  }

  inventoryList.addEventListener('click', e => {
    const fila = e.target.closest('.data-row');
    if (!fila) return;
    const etiketa = fila.querySelector('span').textContent;

    if (e.target.classList.contains('editar-inventario')) {
      abrirModal(inventarioModal);
      etiketaInput.value = etiketa;

      // Cargar datos del inventario
      fetch(`../backend/inventario.php?etiketa=${encodeURIComponent(etiketa)}`)
        .then(res => res.json())
        .then(data => {
          if (data.success && data.data.length > 0) {
            const item = data.data[0];
            fechaInput.value = item.erosketaData;
            equipoSelect.innerHTML = '';
            fetch('../backend/ekipamendua.php')
              .then(r => r.json())
              .then(equipos => {
                if (equipos.success) {
                  equipos.data.forEach(eq => {
                    const opt = document.createElement('option');
                    opt.value = eq.id;
                    opt.textContent = eq.izena;
                    if (eq.id == item.idEkipamendu) opt.selected = true;
                    equipoSelect.appendChild(opt);
                  });
                }
              });
          }
        });
    }

    if (e.target.classList.contains('eliminar-inventario')) {
      if (!confirm(`¿Seguro que quieres eliminar "${etiketa}"?`)) return;
      const body = new URLSearchParams();
      body.append('action', 'delete');
      body.append('etiketa', etiketa);
      fetch('../backend/inventario.php', { method: 'POST', body })
        .then(res => res.json())
        .then(data => {
          if (data.success) cargarInventario();
          else alert('Error: ' + data.message);
        });
    }
  });

  if (guardarInventarioBtn) {
    guardarInventarioBtn.addEventListener('click', () => {
      if (!equipoSelect.value || !fechaInput.value) {
        alert('Completa todos los campos antes de guardar.');
        return;
      }
      const body = new URLSearchParams();
      body.append('action', 'update');
      body.append('etiketa', etiketaInput.value);
      body.append('idEkipamendu', equipoSelect.value);
      body.append('erosketaData', fechaInput.value);

      fetch('../backend/inventario.php', { method: 'POST', body })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            cerrarModal(inventarioModal);
            cargarInventario();
          } else alert('Error al actualizar: ' + data.message);
        });
    });
  }

  // ================= EQUIPOS =================
  function cargarEquipos() {
    fetch('../backend/ekipamendua.php')
      .then(res => res.json())
      .then(data => {
        if (data.success) mostrarEquipos(data.data);
      });
  }

  function mostrarEquipos(data) {
    listaEquipos.innerHTML = '';
    data.forEach(item => {
      const div = document.createElement('div');
      div.classList.add('data-row');
      div.innerHTML = `
        <span>${item.izena}</span>
        <span>${item.marka}</span>
        <span>${item.modelo}</span>
        <span>${item.stock}</span>
        <span>${item.kategoria}</span>
        <span>
          <button class="editar-equipo">Editar</button>
          <button class="eliminar-equipo">Eliminar</button>
        </span>
      `;
      listaEquipos.appendChild(div);
    });
  }

  listaEquipos.addEventListener('click', e => {
    const fila = e.target.closest('.data-row');
    if (!fila) return;
    const nombre = fila.querySelector('span').textContent;

    if (e.target.classList.contains('editar-equipo')) {
      abrirModal(equipoModal);
      fetch(`../backend/ekipamendua.php?nombre=${encodeURIComponent(nombre)}`)
        .then(res => res.json())
        .then(data => {
          if (data.success && data.data.length > 0) {
            const eq = data.data[0];
            nombreInput.value = eq.izena;
            marcaInput.value = eq.marka;
            modeloInput.value = eq.modelo;
            stockInput.value = eq.stock;
            descripcionInput.value = eq.deskribapena;
            categoriaSelect.innerHTML = '';
            fetch('../backend/kategoria.php')
              .then(r => r.json())
              .then(catData => {
                if (catData.success) {
                  catData.data.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.id;
                    opt.textContent = cat.izena;
                    if (cat.id == eq.idKategoria) opt.selected = true;
                    categoriaSelect.appendChild(opt);
                  });
                }
              });
          }
        });
    }

    if (e.target.classList.contains('eliminar-equipo')) {
      if (!confirm(`¿Seguro que quieres eliminar "${nombre}"?`)) return;
      const body = new URLSearchParams();
      body.append('action', 'delete');
      body.append('nombre', nombre);
      fetch('../backend/ekipamendua.php', { method: 'POST', body })
        .then(res => res.json())
        .then(data => {
          if (data.success) cargarEquipos();
          else alert('Error: ' + data.message);
        });
    }
  });

  if (guardarEquipoBtn) {
    guardarEquipoBtn.addEventListener('click', () => {
      if (!nombreInput.value || !marcaInput.value || !modeloInput.value || !stockInput.value || !categoriaSelect.value) {
        alert('Completa todos los campos antes de guardar.');
        return;
      }
      const body = new URLSearchParams();
      body.append('action', 'update');
      body.append('nombre', nombreInput.value);
      body.append('marca', marcaInput.value);
      body.append('modelo', modeloInput.value);
      body.append('stock', stockInput.value);
      body.append('idKategoria', categoriaSelect.value);
      body.append('deskribapena', descripcionInput.value);

      fetch('../backend/ekipamendua.php', { method: 'POST', body })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            cerrarModal(equipoModal);
            cargarEquipos();
          } else alert('Error al actualizar: ' + data.message);
        });
    });
  }

  // ================= INICIALIZACIÓN =================
  cargarInventario();
  cargarEquipos();
});
