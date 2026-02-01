# 🗺️ imagesinbulks.com Sitemap

Este documento detalla todas las rutas y páginas disponibles en la aplicación, organizadas por el tipo de acceso.

## 🔓 Páginas Públicas
Accesibles para cualquier visitante sin necesidad de iniciar sesión.

| Ruta | Archivo | Descripción |
| :--- | :--- | :--- |
| `/` | `index.php` | Landing page principal con propuesta de valor. |
| `/pricing` | `pricing.php` | Planes de suscripción y compra de créditos. |
| `/login` | `login.php` | Acceso al sistema (Manual y Social). |
| `/forgot-password` | `forgot-password.php` | Solicitud de recuperación de contraseña. |
| `/reset-password` | `reset-password.php` | Formulario para establecer nueva contraseña (vía token). |
| `/privacy` | `privacy.php` | Políticas de privacidad. |
| `/terms` | `terms.php` | Términos y condiciones de uso. |

## 🔐 Área de Usuario (Requiere Login)
Páginas protegidas que requieren una sesión activa.

| Ruta | Archivo | Descripción |
| :--- | :--- | :--- |
| `/generator` | `generator.php` | **Herramienta principal**: Generación de imágenes por lote. |
| `/dashboard` | `dashboard/index.php` | Perfil de usuario, gestión de cuenta y galería de imágenes. |
| `/dashboard/billing` | `dashboard/billing.php` | Historial de pagos y gestión de métodos de pago. |
| `/logout` | `logout.php` | Cierre de sesión seguro. |

## ⚙️ Rutas de Sistema y Verificación
Procesos automáticos o confirmaciones vía email.

| Archivo | Función |
| :--- | :--- |
| `verify-email.php` | Procesa el token de bienvenida para nuevos usuarios. |
| `verify-email-change.php` | Procesa el cambio de correo electrónico solicitado. |
| `auth/callback.php` | Maneja la respuesta de Google/Social Login (Hybridauth). |
| `auth/redirect.php` | Redireccionamiento inteligente para login o re-autenticación. |

## 🛠️ API Endpoints (Internos)
Rutas usadas por el Frontend para operaciones asíncronas.

| Endpoint | Acción |
| :--- | :--- |
| `api/generate-images.php` | Conexión con OpenAI para crear imágenes. |
| `api/update-profile.php` | Cambiar nombre o avatar. |
| `api/update-password.php` | Cambiar contraseña del usuario local. |
| `api/delete-account.php` | Eliminación definitiva de la cuenta. |
| `api/request-email-change.php` | Iniciar proceso de cambio de email. |
| `api/billing-actions.php` | Manejo de suscripciones y pagos. |

---
*Nota: El acceso a las rutas amigables (sin `.php`) depende de la configuración del archivo `.htaccess` del servidor.*
