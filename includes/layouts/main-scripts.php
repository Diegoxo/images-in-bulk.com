<?php
/**
 * Main script injection logic
 */
if (isset($pageScripts)) {
    include __DIR__ . '/scripts/' . $pageScripts . '.php';
}
?>