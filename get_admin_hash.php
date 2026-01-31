<?php
/**
 * Generate password hash and output complete SQL reset script
 * Visit this file in your browser to get the SQL with correct password hash
 */

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

header('Content-Type: text/plain');

echo "-- Database Reset Script (Generated)\n";
echo "-- Admin Username: admin\n";
echo "-- Admin Password: admin123\n\n";

echo "-- Disable foreign key checks\n";
echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";

echo "-- Truncate all tables\n";
echo "TRUNCATE TABLE `screenshots`;\n";
echo "TRUNCATE TABLE `attendance`;\n";
echo "TRUNCATE TABLE `notifications`;\n";
echo "TRUNCATE TABLE `tasks`;\n";
echo "TRUNCATE TABLE `users`;\n\n";

echo "-- Re-enable foreign key checks\n";
echo "SET FOREIGN_KEY_CHECKS = 1;\n\n";

echo "-- Insert admin user\n";
echo "INSERT INTO `users` (`full_name`, `username`, `password`, `role`, `created_at`) VALUES\n";
echo "('Administrator', 'admin', '$hash', 'admin', NOW());\n";
