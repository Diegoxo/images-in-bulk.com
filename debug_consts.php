<?php
require_once 'includes/config.php';
$consts = get_defined_constants(true);
if (isset($consts['user'])) {
    foreach ($consts['user'] as $name => $value) {
        if (strpos($name, 'WOMPI') !== false) {
            echo "$name: " . (strpos($name, 'SECRET') !== false || strpos($name, 'PRIVATE') !== false ? '********' : $value) . "\n";
        }
    }
}
