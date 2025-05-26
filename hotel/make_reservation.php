<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_id = mysqli_real_escape_string($conn, $_POST['room_id']);
    $check_in_date = mysqli_real_escape_string($conn, $_POST['check_in_date']);
    $check_out_date = mysqli_real_escape_string($conn, $_POST['check_out_date']);
    
    // Validate dates
    $check_in = strtotime($check_in_date);
    $check_out = strtotime($check_out_date);
    $today = strtotime('today');
    
    if ($check_in < $today) {
        redirectWith('index.php', 'Check-in date cannot be in the past.', 'danger');
    }
    
    if ($check_out <= $check_in) {
        redirectWith('index.php', 'Check-out date must be after check-in date.', 'danger');
    }
    
    // Check if room is available for the selected dates
    $sql = "SELECT * FROM reservations 
            WHERE room_id = ? 
            AND status != 'cancelled'
            AND (
                (check_in_date <= ? AND check_out_date >= ?) 
                OR (check_in_date <= ? AND check_out_date >= ?) 
                OR (check_in_date >= ? AND check_out_date <= ?)
            )";
            
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sssssss", 
            $room_id, 
            $check_out_date, $check_in_date, 
            $check_in_date, $check_in_date,
            $check_in_date, $check_out_date
        );
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            redirectWith('index.php', 'Room is not available for the selected dates.', 'danger');
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Calculate total price
    $sql = "SELECT price_per_night FROM rooms WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $room = mysqli_fetch_assoc($result);
        
        $nights = ceil(($check_out - $check_in) / (86400)); // 86400 seconds in a day
        $total_price = $room['price_per_night'] * $nights;
        
        mysqli_stmt_close($stmt);
    }
    
    // Create reservation
    $sql = "INSERT INTO reservations (user_id, room_id, check_in_date, check_out_date, total_price, status) 
            VALUES (?, ?, ?, ?, ?, 'confirmed')";
            
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "iissd", 
            $_SESSION['user_id'], 
            $room_id, 
            $check_in_date, 
            $check_out_date, 
            $total_price
        );
        
        if (mysqli_stmt_execute($stmt)) {
            // Update room status
            $update_sql = "UPDATE rooms SET status = 'booked' WHERE id = ?";
            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "i", $room_id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
            
            redirectWith('my_reservations.php', 'Reservation confirmed successfully!', 'success');
        } else {
            redirectWith('index.php', 'Something went wrong. Please try again.', 'danger');
        }
        
        mysqli_stmt_close($stmt);
    }
} else {
    header("Location: index.php");
    exit();
}
?> 