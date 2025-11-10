// URLs del backend
const urlKokapena = "../backend/controladores/gestioaController.php";
const urlEquipos = "../backend/controladores/ekipamenduakController.php";
const urlGelas = "../backend/controladores/gelakController.php";

const listKokapena = document.getElementById("kokapena-list");
const selectEquipo = document.getElementById("select-equipo");
const selectGela = document.getElementById("select-gela");

// Sección desplegable para IDs en el modal
let detailsIds = document.createElement("details");
let summary = document.createElement("summary");
summary.textContent = "Ver IDs seleccionados";
let pIds = document.createElement("p");
pIds.textContent = "Aún no hay selección.";
detailsIds.appendChild(summary);
detailsIds.appendChild(pIds);
document.querySelector("#addKokapenaModal .modal-body").appendChild(detailsIds);

// ------------------ FUNCIONES ------------------

// Listar kokapena
async function listarKokapena() {
  listKokapena.innerHTML = '<li class="data-row text-center py-2">Kargatzen...</li>';
  try {
    const res = await fetch(urlKokapena + "?action=GET");
    const data = await res.json();
    listKokapena.innerHTML = '';

    if (data.success && data.data.length > 0) {
      const fragment = document.createDocumentFragment();
      data.data.forEach((k, idx) => {
        const li = document.createElement("li");
        li.className = `data-row ${idx % 2 === 0 ? 'even' : ''}`;
        li.innerHTML = `
          <span>${k.ekipamendu_izena}</span>
          <span>${k.gela_izena}</span>
          <span>${k.taldea}</span>
        `;
        fragment.appendChild(li);
      });
      listKokapena.appendChild(fragment);
    } else {
      listKokapena.innerHTML = '<li class="data-row empty">Ez dago daturik.</li>';
    }
  } catch (err) {
    console.error(err);
    listKokapena.innerHTML = '<li class="data-row empty">Errorea konektatzean.</li>';
  }
}

// Cargar select genérico
async function cargarSelect(url, select, placeholder) {
  select.innerHTML = `<option value="">${placeholder}</option>`;
  try {
    let res, data;
    if (url.includes("gelasController")) {
      // gelasController espera 'action=GET' en JSON body
      res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "GET" })
      });
      data = await res.json();
    } else {
      // equiposController usa GET
      res = await fetch(url + "?action=GET");
      data = await res.json();
    }

    if (!data.success) throw new Error(data.message || "Error al cargar datos");

    data.data.forEach(item => {
      const option = document.createElement("option");
      option.value = item.id;
      option.textContent = item.izena;
      select.appendChild(option);
    });
  } catch (err) {
    console.error(err);
    select.innerHTML += `<option value="">Errorea kargatzean</option>`;
  }
}

// Mostrar IDs seleccionados en el modal
function mostrarIdsSeleccionados() {
  const idEquipo = selectEquipo.value || "(no seleccionado)";
  const idGela = selectGela.value || "(no seleccionado)";
  pIds.textContent = `ID Ekipamendu: ${idEquipo}, ID Gela: ${idGela}`;
}

// ------------------ EVENTOS ------------------

// Abrir modal: cargar selects
const addBtn = document.querySelector("[data-bs-target='#addKokapenaModal']");
addBtn.addEventListener("click", () => {
  cargarSelect(urlEquipos, selectEquipo, "Aukeratu ekipoa");
  cargarSelect(urlGelas, selectGela, "Aukeratu gela");
  mostrarIdsSeleccionados();
});

// Actualizar IDs al cambiar selección
selectEquipo.addEventListener("change", mostrarIdsSeleccionados);
selectGela.addEventListener("change", mostrarIdsSeleccionados);

// Inicializar lista al cargar la página
document.addEventListener("DOMContentLoaded", listarKokapena);