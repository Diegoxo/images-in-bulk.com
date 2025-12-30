# Estructura de Páginas del Proyecto

| Página | Nombre de Archivo | Propósito Principal | Elementos Clave |
|-------|------------------|---------------------|------------------|
| Home / Landing Page | index.php | Vender el servicio y atraer usuarios. | Hero con demo, sección de características, tabla de precios, testimonios y llamada a la acción (CTA). |
| App / Dashboard | app.php | El generador que ya construimos. | Formulario de prompts, configuración de IA, grilla de resultados procesados en tiempo real e historial local. |
| Login / Sign Up | login.php | Autenticación de usuarios. | Botones de "Sign in with Google" y "Hotmail" (Hybridauth), términos y condiciones. |
| Pricing | pricing.php | Mostrar el plan de $5 USD. | Detalle de lo que incluye el plan, comparativa (Free vs Pro) y botón de "Subscribe now" hacia Stripe. |
| Account / Billing | account.php | Gestión del usuario y suscripción. | Estado del plan, historial de facturación (vía Stripe Portal), botón para cancelar y log de uso (cuántas imágenes ha generado). |
| Privacy Policy | privacy.php | Requisito legal para Stripe/Google. | Texto legal sobre el manejo de datos de usuario. |
| Terms of Service | terms.php | Requisito legal y reglas de uso. | Normas sobre el contenido generado y políticas de reembolso. |
| 404 | error.php | Manejo de errores de ruta. | Mensaje amigable y botón de retorno. |
| Contact / Soporte | reports.php | Soporte y reportes. | Formulario de contacto / reporte de errores. |
| Admin Dashboard | admin/dashboard/administracion/index.php | Administración interna. | Control de usuarios, pagos y estadísticas globales. |
