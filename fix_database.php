<?php
require_once 'includes/config.php';

try {
    $db = getDB();

    // 1. Crear tabla USERS si no existe (la base)
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        full_name VARCHAR(255),
        auth_provider VARCHAR(50) NOT NULL,
        provider_id VARCHAR(255) NOT NULL,
        avatar_url TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Crear tabla SUBSCRIPTIONS con TODAS las columnas necesarias
    $db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        plan_type VARCHAR(20) DEFAULT 'free', 
        status VARCHAR(20) DEFAULT 'inactive',
        current_period_start DATETIME,
        current_period_end DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 3. Verificación extra: Asegurar que la columna plan_type exista (por si la tabla ya existía pero vieja)
    try {
        $db->query("SELECT plan_type FROM subscriptions LIMIT 1");
    } catch (PDOException $e) {
        // Si falla, es que no existe la columna, así que la agregamos
        $db->exec("ALTER TABLE subscriptions ADD COLUMN plan_type VARCHAR(20) DEFAULT 'free' AFTER user_id");
        echo "Columna plan_type agregada. ";
    }

    echo "¡Base de datos reparada y lista para la acción!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
unlink(__FILE__); // Se autodestruye al terminar
