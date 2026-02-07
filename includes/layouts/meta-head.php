<?php
/**
 * SEO & Social Media Head Tags
 * Centralized component for meta descriptions, Open Graph, and Structured Data.
 */

// Default values if not set by controller
$metaDescription = $metaDescription ?? "Generate stunning AI images in bulk with DALL-E 3 effortlessly.";
$metaKeywords = $metaKeywords ?? "AI, images, bulk, batch generator, OpenAI";
$canonicalUrl = $canonicalUrl ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$siteName = "Images In Bulks";
$ogImage = SITE_URL . "/assets/img/og-image.webp"; // Default OG image
?>

<meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords); ?>">
<link rel="canonical" href="<?php echo $canonicalUrl; ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo $canonicalUrl; ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($pageTitle . ' | ' . $siteName); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
<meta property="og:image" content="<?php echo $ogImage; ?>">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="<?php echo $canonicalUrl; ?>">
<meta property="twitter:title" content="<?php echo htmlspecialchars($pageTitle . ' | ' . $siteName); ?>">
<meta property="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
<meta property="twitter:image" content="<?php echo $ogImage; ?>">

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="<?php echo $prefix ?? ''; ?>assets/img/favicon.ico">

<!-- Structured Data (JSON-LD) - Rich Snippets -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Images In Bulks",
  "operatingSystem": "Web",
  "applicationCategory": "DesignApplication",
  "offers": {
    "@type": "Offer",
    "price": "21.00",
    "priceCurrency": "USD"
  },
  "description": "<?php echo htmlspecialchars($metaDescription); ?>",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.9",
    "ratingCount": "1250"
  }
}
</script>