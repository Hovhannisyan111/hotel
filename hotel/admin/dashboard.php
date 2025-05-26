<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get all rooms
$rooms_sql = "SELECT * FROM rooms ORDER BY room_number";
$rooms_result = mysqli_query($conn, $rooms_sql);
$rooms = mysqli_fetch_all($rooms_result, MYSQLI_ASSOC);

// Sample room image URLs
$room_images = [
    'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
    'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
    'https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
    'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'
];

// Get all reservations with user and room details
$reservations_sql = "SELECT r.*, u.username, u.email, rm.room_number, rm.room_type 
                    FROM reservations r 
                    JOIN users u ON r.user_id = u.id 
                    JOIN rooms rm ON r.room_id = rm.id 
                    ORDER BY r.created_at DESC";
$reservations_result = mysqli_query($conn, $reservations_sql);
$reservations = mysqli_fetch_all($reservations_result, MYSQLI_ASSOC);

// Get all users except admin
$users_sql = "SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_sql);
$users = mysqli_fetch_all($users_result, MYSQLI_ASSOC);

// Handle room deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_room'])) {
    $room_id = mysqli_real_escape_string($conn, $_POST['room_id']);
    
    // Check if room has any reservations
    $check_sql = "SELECT id FROM reservations WHERE room_id = ? AND status IN ('confirmed', 'pending')";
    if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($check_stmt, "i", $room_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            redirectWith('dashboard.php', 'Cannot delete room: It has active reservations.', 'danger');
            exit();
        }
        mysqli_stmt_close($check_stmt);
    }
    
    // Delete room if no active reservations
    $sql = "DELETE FROM rooms WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        if (mysqli_stmt_execute($stmt)) {
            redirectWith('dashboard.php', 'Room deleted successfully!', 'success');
        } else {
            redirectWith('dashboard.php', 'Error deleting room.', 'danger');
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle room status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_room'])) {
    $room_id = mysqli_real_escape_string($conn, $_POST['room_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $sql = "UPDATE rooms SET status = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $status, $room_id);
        if (mysqli_stmt_execute($stmt)) {
            redirectWith('dashboard.php', 'Room status updated successfully!', 'success');
        } else {
            redirectWith('dashboard.php', 'Error updating room status.', 'danger');
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle adding new room
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_room'])) {
    $room_number = mysqli_real_escape_string($conn, $_POST['room_number']);
    $room_type = mysqli_real_escape_string($conn, $_POST['room_type']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $capacity = mysqli_real_escape_string($conn, $_POST['capacity']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    
    $sql = "INSERT INTO rooms (room_number, room_type, price_per_night, capacity, description, image_url) 
            VALUES (?, ?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssdiss", $room_number, $room_type, $price, $capacity, $description, $image_url);
        if (mysqli_stmt_execute($stmt)) {
            redirectWith('dashboard.php', 'New room added successfully!', 'success');
        } else {
            redirectWith('dashboard.php', 'Error adding new room.', 'danger');
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hotel Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Hotel Reservation</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Admin Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Dashboard Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Rooms</h5>
                        <p class="card-text display-4"><?php echo count($rooms); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active Reservations</h5>
                        <p class="card-text display-4">
                            <?php 
                            echo count(array_filter($reservations, function($r) {
                                return $r['status'] == 'confirmed';
                            }));
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Registered Users</h5>
                        <p class="card-text display-4"><?php echo count($users); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for different sections -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="rooms-tab" data-bs-toggle="tab" href="#rooms">Rooms</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="reservations-tab" data-bs-toggle="tab" href="#reservations">Reservations</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="users-tab" data-bs-toggle="tab" href="#users">Users</a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Rooms Tab -->
            <div class="tab-pane fade show active" id="rooms">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Manage Rooms</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                        Add New Room
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Type</th>
                                <th>Price/Night</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                    <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                    <td>$<?php echo number_format($room['price_per_night'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($room['capacity']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $room['status'] == 'available' ? 'success' : 
                                                ($room['status'] == 'booked' ? 'danger' : 'warning'); 
                                            ?>">
                                            <?php echo ucfirst($room['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="d-inline">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                            <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                                                <option value="available" <?php echo $room['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                                <option value="maintenance" <?php echo $room['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            </select>
                                            <button type="submit" name="update_room" class="btn btn-primary btn-sm">Update</button>
                                        </form>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this room? This action cannot be undone.');">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                            <button type="submit" name="delete_room" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reservations Tab -->
            <div class="tab-pane fade" id="reservations">
                <h3 class="mb-3">All Reservations</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Total Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($reservation['username']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($reservation['email']); ?></small>
                                    </td>
                                    <td>
                                        Room <?php echo htmlspecialchars($reservation['room_number']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($reservation['room_type']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($reservation['check_in_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($reservation['check_out_date'])); ?></td>
                                    <td>$<?php echo number_format($reservation['total_price'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $reservation['status'] == 'confirmed' ? 'success' : 
                                                ($reservation['status'] == 'pending' ? 'warning' : 
                                                ($reservation['status'] == 'cancelled' ? 'danger' : 'secondary')); 
                                            ?>">
                                            <?php echo ucfirst($reservation['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Users Tab -->
            <div class="tab-pane fade" id="users">
                <h3 class="mb-3">Registered Users</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Joined Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="room_number" class="form-label">Room Number</label>
                            <input type="text" class="form-control" id="room_number" name="room_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="room_type" class="form-label">Room Type</label>
                            <select class="form-select" id="room_type" name="room_type" required>
                                <option value="Standard">Standard</option>
                                <option value="Deluxe">Deluxe</option>
                                <option value="Suite">Suite</option>
                                <option value="Family">Family</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price per Night</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="image_url" class="form-label">Room Image</label>
                            <select class="form-select" id="image_url" name="image_url" required>
                                <?php foreach ($room_images as $url): ?>
                                    <option value="<?php echo htmlspecialchars($url); ?>">
                                        Room Image <?php echo htmlspecialchars(array_search($url, $room_images) + 1); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-2">
                                <img id="preview_image" src="<?php echo $room_images[0]; ?>" alt="Room Preview" style="max-width: 200px; height: auto;">
                            </div>
                        </div>
                        <button type="submit" name="add_room" class="btn btn-primary">Add Room</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add this to your existing JavaScript
        document.getElementById('image_url').addEventListener('change', function() {
            document.getElementById('preview_image').src = this.value;
        });
    </script>
</body>
</html> 
