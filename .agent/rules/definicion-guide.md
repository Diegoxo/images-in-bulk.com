---
trigger: always_on
---

* Objetivo
Creación de un sitio web, donde el usuario pueda pagar una suscripción, para poder acceder al servicio.

* Servicio
Generador de imágenes por lote con IA.

El usuario podrá meter 
- Lista de Prompts (Obligatorio), Cada prompt representará una imagen distinta que será generada mediante la API de OpenAI.
- Lista de Nombres de Imagen (Un nombre por línea), con estos nombres se renombraran las imagenes que fueron creadas.
- Estilo Personalizado / Modificadores, que deben tener las imagenes que se crean con los promts.

El usuario también podrá definir:
- Modelo de creación de imágenes
- Formato de salida PNG o JPG
- Formato de resolución (1.1), (9:16), (16:9)

* Funcionamiento de la implementación
El flujo del sistema será el siguiente:
- Se envía un prompt a la API de OpenAI.
- La API genera la imagen correspondiente y la devuelve como respuesta.
- El sistema recibe la imagen y la procesa (por ejemplo, la muestra o la almacena).
- El proceso se repite automáticamente con el siguiente prompt de la lista.
- El ciclo continúa hasta que todos los prompts hayan sido procesados y todas las imágenes hayan sido generadas.

* Requerimientos del sitio.
- Nombre del sitio: images-in-bulk
- Frontend: HTML, CSS y usar la menor cantidad de JS
- No combinar HTML con CSS en el frontend, todos los estilos deben estar en un archivo aparte de CSS
- Backend PHP
- Base de datos en produccion MariaDB

* Requerimientos adicionales
- Pasarela de pagos: Stripe
- Planes: Plan de USD 5, por definir las caracteristicas.
- Autenticacion: Google, Hotmail, usar hybridauth/hybridauth.
- Manejo de la API Key: Única API Key maestra
- Almacenamiento de Imágenes: Se guardaran en el navegador del usuario con IndexedDB o lo que consideres mejor.
- Estructura de Base de Datos: A tu criterio, crea el script .sql que la genera y lo ejecutas para generarla.
- Archivo ZIP para el lote: Se generara en el frontend, con JS, con la libreria que consideres.
- Servidor de produccion: LiteSpeed.
- Entorno de desarrollo xampp
- Libreria: No usar librerias para conectar con la API, PHP puro.
- 