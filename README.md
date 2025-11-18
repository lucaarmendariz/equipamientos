# Equipamientos

Este proyecto es una aplicación web que gestiona información sobre equipamientos, utilizando PHP en el backend y JavaScript en el frontend para interactuar con la base de datos.

## Estructura del Proyecto

El proyecto está dividido en dos partes principales:

1. **Backend**: Implementado en PHP, donde se encuentran las clases y controladores que interactúan con la base de datos.
2. **Frontend**: Implementado con HTML, CSS y JavaScript, donde se gestiona el diseño y las interacciones con el usuario. Los archivos JavaScript realizan peticiones `fetch` a los controladores del backend.

---

## Backend

El backend está compuesto por clases PHP que se encargan de la lógica de negocio y los controladores que gestionan las peticiones del frontend y las operaciones con la base de datos.

- **Controladores**: Se encuentran en el directorio `backend/`. Los controladores reciben las solicitudes HTTP desde el frontend y las procesan, utilizando las clases correspondientes para interactuar con la base de datos.
  
- **Base de Datos**: El backend está diseñado para interactuar con una base de datos MySQL o similar. Los controladores ejecutan consultas SQL para obtener o modificar los datos.

### Funcionalidades del Backend:

- Gestión de equipamientos: Los controladores permiten realizar operaciones CRUD (Crear, Leer, Actualizar, Eliminar) sobre los equipamientos.
- Validación de datos: Asegura que los datos enviados por el frontend sean correctos antes de procesarlos.

---

## Frontend

El frontend está compuesto por dos carpetas principales, una para el diseño visual y otra para los archivos de JavaScript que se encargan de interactuar con el backend.

### Estructura del Frontend:

- **Diseño**: Los archivos HTML y CSS están organizados en la carpeta `frontend/`. El diseño incluye las vistas para mostrar la información de los equipamientos al usuario.
  
- **JavaScript**: Los archivos `JS` en la carpeta `frontend/js/` contienen la lógica para interactuar con los controladores del backend. Utilizan la función `fetch()` para enviar solicitudes HTTP a los controladores del backend y actualizar la interfaz de usuario dinámicamente.

### Funcionalidades del Frontend:

- Mostrar una lista de equipamientos en una vista tabular.
- Formularios para crear, editar y eliminar equipamientos.
- Interacción dinámica con el backend usando `fetch()` para actualizar la interfaz sin necesidad de recargar la página.

---

## Requisitos

Para poder ejecutar este proyecto, necesitas tener instalados los siguientes programas:

- **PHP 7.x o superior**
- **Servidor web (Apache o similar)**
- **Base de datos MySQL**
- **Navegador web moderno (para el frontend)**

---

## Instalación

### Backend

1. Clona el repositorio en tu servidor local o de desarrollo.
   ```bash
   git clone https://github.com/lucaarmendariz/equipamientos.git
