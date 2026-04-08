<?php

/**
 * ============================================
 * HyperGear Tech - Database Configuration
 * ============================================
 * Updated for HyperGearTech project structure
 */

// Database credentials for XAMPP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hypergeartech_db');

// Base URL - updated for HyperGearTech folder
define('BASE_URL', '/HyperGearTech/');

// Site name
define('SITE_NAME', 'HyperGear Tech');

/**
 * Create database connection
 */
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("<div style='text-align:center;padding:50px;font-family:Arial;'>
        <h2>Database Connection Error</h2>
        <p>Could not connect to MySQL. Please ensure:</p>
        <ul style='display:inline-block;text-align:left;'>
            <li>XAMPP Apache and MySQL are running</li>
            <li>Database <strong>hypergeartech_db</strong> has been imported</li>
        </ul>
        <p><small>Error: " . $conn->connect_error . "</small></p>
    </div>");
}

$conn->set_charset("utf8mb4");
