<?php

// Script to create directory structure
$directories = [
    'app',
    'app/Controllers',
    'app/Models', 
    'app/Views',
    'app/Core',
    'config',
    'public',
    'vendor'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    }
}

echo "Directory structure created successfully!\n";