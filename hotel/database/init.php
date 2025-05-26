<?php
require_once '../includes/config.php';

// Create users table
$users_table = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'guest') DEFAULT 'guest',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Create rooms table
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

// Create reservations table
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

// Create tables
if (mysqli_query($conn, $users_table)) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . mysqli_error($conn) . "<br>";
}

if (mysqli_query($conn, $rooms_table)) {
    echo "Rooms table created successfully<br>";
} else {
    echo "Error creating rooms table: " . mysqli_error($conn) . "<br>";
}

if (mysqli_query($conn, $reservations_table)) {
    echo "Reservations table created successfully<br>";
} else {
    echo "Error creating reservations table: " . mysqli_error($conn) . "<br>";
}

// Insert default admin if not exists
$check_admin = mysqli_query($conn, "SELECT id FROM users WHERE username = 'admin'");
if (mysqli_num_rows($check_admin) == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO users (username, password, email, full_name, role) VALUES 
    ('admin', '$admin_password', 'admin@hotel.com', 'System Admin', 'admin')";
    if (mysqli_query($conn, $insert_admin)) {
        echo "Default admin user created successfully<br>";
    }
}

// Insert sample rooms if not exists
$check_rooms = mysqli_query($conn, "SELECT id FROM rooms LIMIT 1");
if (mysqli_num_rows($check_rooms) == 0) {
    $sample_rooms = "INSERT INTO rooms (room_number, room_type, price_per_night, capacity, description, status) VALUES
    ('101', 'Standard', 100.00, 2, 'Comfortable standard room with queen-size bed', 'available'),
    ('102', 'Deluxe', 150.00, 2, 'Luxurious room with king-size bed and city view', 'available'),
    ('201', 'Suite', 250.00, 4, 'Spacious suite with separate living area', 'available'),
    ('202', 'Family', 200.00, 6, 'Perfect for families with multiple beds', 'available')";
    
    if (mysqli_query($conn, $sample_rooms)) {
        echo "Sample rooms created successfully<br>";
    }
}

echo "Database initialization completed!";
?> 