<?php
// Script to create necessary directories
$directories = [
    'uploads',
    'uploads/products',
    'api'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    } else {
        echo "Directory already exists: $dir\n";
    }
}

// Create .htaccess for uploads directory
$htaccess_content = "Options -Indexes\n";
file_put_contents('uploads/.htaccess', $htaccess_content);

echo "Setup complete!\n";
echo "Remember to:\n";
echo "1. Import the database schema (database/schema.sql)\n";
echo "2. Update database credentials in config/database.php\n";
echo "3. Set proper file permissions for uploads directory\n";
echo "4. Default admin login: username 'admin', password 'password'\n";
?>