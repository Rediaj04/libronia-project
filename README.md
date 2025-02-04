## Fase 6: Panel de Administración

### 1. Diseño de la Interfaz de Administración
- **Vista protegida**: Se debe crear una página de administración accesible solo por usuarios autorizados, utilizando un sistema de autenticación robusto. 
- **Diseño responsivo**: Utilizando Bootstrap, se debe asegurar que la interfaz se adapte correctamente a diferentes tamaños de pantalla, garantizando una experiencia de usuario óptima en dispositivos móviles, tabletas y escritorios.
- **Gestión de datos scrapeados**: Implementar formularios y tablas en la interfaz para mostrar y gestionar la información extraída mediante el proceso de scraping. Estos elementos deben ser intuitivos y fáciles de usar para permitir al administrador añadir, modificar y eliminar datos con eficiencia.

### 2. Implementación de Funciones CRUD
- **Controladores CRUD**: Se deben desarrollar controladores específicos que manejen las operaciones básicas de gestión de datos:
  - **Crear**: Permitir la inserción de nuevos registros en la base de datos.
  - **Leer**: Facilitar la visualización de los datos almacenados, ya sea en formato de tabla o mediante búsquedas y filtros.
  - **Actualizar**: Habilitar la edición de registros existentes, garantizando que se puedan modificar los datos de manera precisa.
  - **Eliminar**: Implementar una funcionalidad para eliminar datos que ya no sean necesarios, con la opción de confirmar antes de realizar la acción.
