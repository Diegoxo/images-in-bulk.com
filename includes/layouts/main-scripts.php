<?php
/**
 * Main script injection logic
 */
if (isset($pageScripts)) {
    include 'includes/layouts/scripts/' . $pageScripts . '.php';
}
?>