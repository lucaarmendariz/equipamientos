document.addEventListener("DOMContentLoaded", () => {

    function cargarDatos(url, contenedorId, columnas, placeholder = "No hay datos disponibles") {
        const container = document.getElementById(contenedorId);

        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error("Error en la respuesta del servidor");
                return res.json();
            })
            .then(data => {
                if (data.success && data.data.length > 0) {
                    container.innerHTML = "";

                    // Cabecera
                    const header = document.createElement("div");
                    header.classList.add("data-row", "data-header");
                    header.innerHTML = columnas.map(col => `<span>${col.label}</span>`).join("");
                    container.appendChild(header);

                    // Filas
                    data.data.forEach((item, index) => {
                        const row = document.createElement("div");
                        row.classList.add("data-row");
                        if (index % 2 === 0) row.classList.add("even");

                        row.innerHTML = columnas.map(col => `<span>${item[col.key] ?? "—"}</span>`).join("");
                        container.appendChild(row);
                    });
                } else {
                    container.innerHTML = `<div class="data-row empty">${placeholder}</div>`;
                }
            })
            .catch(err => {
                console.error(`Error al obtener ${contenedorId}:`, err);
                container.innerHTML = `<div class="data-row empty">Error al cargar los datos.</div>`;
            });
    }
    cargarDatos("../backend/equipos.php", "equipamientos-list", [
        { label: "Izena", key: "izena" },
        { label: "Stock", key: "stock" },
        { label: "Marka", key: "marka" },
        { label: "Modelo", key: "modelo" }
    ]);

  // Llenar categorías
  fetch('../backend/kategoriak.php')
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById('categoria');
      data.forEach(c => {
        const option = document.createElement('option');
        option.value = c.id;
        option.textContent = c.izena;
        select.appendChild(option);
      });
    });

  // Llenar gelas
  fetch('../backend/gelak.php')
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById('gela');
      data.forEach(g => {
        const option = document.createElement('option');
        option.value = g.id;
        option.textContent = g.izena;
        select.appendChild(option);
      });
    });

  // Enviar formulario
  document.getElementById('addEquipoForm').addEventListener('submit', e => {
    e.preventDefault();

    const payload = {
      nombre: document.getElementById('nombre').value,
      descripcion: document.getElementById('descripcion').value,
      marca: document.getElementById('marca').value,
      modelo: document.getElementById('modelo').value,
      stock: parseInt(document.getElementById('stock').value),
      idCategoria: parseInt(document.getElementById('categoria').value),
      idGela: parseInt(document.getElementById('gela').value)
    };
    console.log(payload)

    fetch('../backend/sortuEkipamendu.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert('Equipo añadido correctamente!');
          const modalEl = document.getElementById('addEquipoModal');
          const modal = bootstrap.Modal.getInstance(modalEl);
          modal.hide();
          // Opcional: actualizar lista de equipos
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(err => console.error(err));
  });
});
