<?php
/**
 * Database Bridge File
 * This file ensures compatibility between db.php and database.php
 * It prevents duplicate class definitions while maintaining backward compatibility
 */

// Check if the Database class is already defined
if (!class_exists('Database')) {
    // If it's not defined, include db.php
    require_once __DIR__ . '/db.php';
} else {
    // Database class already exists, do nothing
    // This prevents duplicate class definition errors
}
