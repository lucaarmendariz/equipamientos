window.onload = function() {
  // ===== ELEMENTOS =====
  const inventoryList = document.getElementById('inventory-list');
  const equipoSelect = document.getElementById('equipo-select');
  
  const nuevaCategoriaBtn = document.getElementById('nueva-categoria-btn');
  const nuevaCategoriaModal = new bootstrap.Modal(document.getElementById('nuevaCategoriaModal'));
  const nuevaCategoriaInput = document.getElementById('nueva-categoria-input');
  const guardarCategoriaBtn = document.getElementById('guardar-categoria');

  // ================== INVENTARIO ==================
  function cargarInventario() {
    fetch('../backend/controladores/inbentarioController.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) {
          console.error('Errorea datuak lortzean:', res.message);
          return;
        }

        inventoryList.innerHTML = '';
        res.data.forEach(item => {
          const div = document.createElement('div');
          div.className = 'data-row';
          div.innerHTML = `
            <span>${item.izena}</span>
            <span>${item.etiketa}</span>
            <span>${item.kokaleku || ''}</span>
          `;
          inventoryList.appendChild(div);
        });
      })
      .catch(err => {
        console.error('Errorea inventarioa kargatzean:', err);
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
          option.value = equipo.id;
          option.textContent = equipo.izena;
          equipoSelect.appendChild(option);
        });
      })
      .catch(err => console.error('Error fetching equipamientos:', err));
  }

  // ================== NUEVA CATEGORÍA ==================
  nuevaCategoriaBtn.onclick = () => {
    nuevaCategoriaInput.value = '';
    guardarCategoriaBtn.textContent = 'Sortu';
    nuevaCategoriaModal.show();
  };

  guardarCategoriaBtn.onclick = () => {
    const nombre = nuevaCategoriaInput.value.trim();
    if (!nombre) {
      Swal.fire({ icon: 'info', title: 'Info', text: 'Sartu izen bat' });
      return;
    }

    fetch('../backend/controladores/kategoriaController.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ izena: nombre })
    })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          Swal.fire({ icon: 'success', title: 'Kategoria sortu da', text: 'Kategoria berria ondo sortu da.' });
          nuevaCategoriaModal.hide();
          // cargarCategorias(); // activar si tienes función para recargar categorías
        } else {
          Swal.fire({ icon: 'error', title: 'Errorea', text: res.message });
        }
      });
  };

  // ===== INICIAL =====
  cargarInventario();
  cargarEquipamientos();
};
