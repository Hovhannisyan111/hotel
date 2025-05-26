<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'hotel_db');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS hotel_db";
if (mysqli_query($conn, $sql)) {
    mysqli_select_db($conn, DB_NAME);
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Create tables
$users_table = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'guest') DEFAULT 'guest',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$rooms_table = "CREATE TABLE IF NOT EXISTS rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    description TEXT,
    status ENUM('available', 'booked', 'maintenance') DEFAULT 'available',
    image_url VARCHAR(255)
)";

$reservations_table = "CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    room_id INT,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
)";

mysqli_query($conn, $users_table);
mysqli_query($conn, $rooms_table);
mysqli_query($conn, $reservations_table);

// Insert default admin if not exists
$check_admin = mysqli_query($conn, "SELECT id FROM users WHERE username = 'admin'");
if (mysqli_num_rows($check_admin) == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO users (username, password, email, full_name, role) VALUES 
    ('admin', '$admin_password', 'admin@hotel.com', 'System Admin', 'admin')";
    mysqli_query($conn, $insert_admin);
}

// Insert sample rooms if not exists
$check_rooms = mysqli_query($conn, "SELECT id FROM rooms LIMIT 1");
if (mysqli_num_rows($check_rooms) == 0) {
    $sample_rooms = "INSERT INTO rooms (room_number, room_type, price_per_night, capacity, description, status) VALUES
    ('101', 'Standard', 100.00, 2, 'Comfortable standard room with queen-size bed', 'available'),
    ('102', 'Deluxe', 150.00, 2, 'Luxurious room with king-size bed and city view', 'available'),
    ('201', 'Suite', 250.00, 4, 'Spacious suite with separate living area', 'available'),
    ('202', 'Family', 200.00, 6, 'Perfect for families with multiple beds', 'available')";
    mysqli_query($conn, $sample_rooms);
}
?>
