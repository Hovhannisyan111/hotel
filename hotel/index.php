<?php
require_once 'includes/config.php';

// Get all available rooms
$sql = "SELECT * FROM rooms WHERE status = 'available'";
$result = mysqli_query($conn, $sql);
$rooms = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .room-card {
            transition: transform 0.3s;
        }
        .room-card:hover {
            transform: translateY(-5px);
        }
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Hotel Reservation</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/dashboard.php">Admin Dashboard</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my_reservations.php">My Reservations</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4">Welcome to Our Hotel</h1>
            <p class="lead">Experience luxury and comfort at its finest</p>
        </div>
    </div>

    <!-- Room Listings -->
    <div class="container mb-5">
        <h2 class="text-center mb-4">Available Rooms</h2>
        
        <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info text-center">
                Please <a href="login.php">login</a> or <a href="register.php">register</a> to make a reservation.
            </div>
        <?php endif; ?>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($rooms as $room): ?>
                <div class="col">
                    <div class="card h-100 room-card">
                        <img src="<?php echo !empty($room['image_url']) ? $room['image_url'] : 'https://via.placeholder.com/300x200.png?text=Room+' . $room['room_number']; ?>" 
                             class="card-img-top" alt="Room <?php echo $room['room_number']; ?>">
                        <div class="card-body">
                            <h5 class="card-title">Room <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?></h5>
                            <p class="card-text"><?php echo $room['description']; ?></p>
                            <ul class="list-unstyled">
                                <li><strong>Price:</strong> $<?php echo number_format($room['price_per_night'], 2); ?> per night</li>
                                <li><strong>Capacity:</strong> <?php echo $room['capacity']; ?> persons</li>
                            </ul>
                            <?php if (isLoggedIn()): ?>
                                <button type="button" class="btn btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#reservationModal" 
                                        data-room-id="<?php echo $room['id']; ?>"
                                        data-room-number="<?php echo $room['room_number']; ?>"
                                        data-room-price="<?php echo $room['price_per_night']; ?>">
                                    Book Now
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-secondary">Login to Book</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Reservation Modal -->
    <?php if (isLoggedIn()): ?>
    <div class="modal fade" id="reservationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Make a Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reservationForm" action="make_reservation.php" method="post">
                        <input type="hidden" name="room_id" id="room_id">
                        <div class="mb-3">
                            <label for="check_in_date" class="form-label">Check-in Date</label>
                            <input type="text" class="form-control" id="check_in_date" name="check_in_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="check_out_date" class="form-label">Check-out Date</label>
                            <input type="text" class="form-control" id="check_out_date" name="check_out_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Price</label>
                            <div id="total_price" class="form-control-plaintext">$0.00</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Confirm Reservation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date pickers
            const checkInPicker = flatpickr("#check_in_date", {
                minDate: "today",
                onChange: calculateTotal
            });

            const checkOutPicker = flatpickr("#check_out_date", {
                minDate: "today",
                onChange: calculateTotal
            });

            // Handle reservation modal
            const reservationModal = document.getElementById('reservationModal');
            if (reservationModal) {
                reservationModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const roomId = button.getAttribute('data-room-id');
                    const roomPrice = button.getAttribute('data-room-price');
                    
                    document.getElementById('room_id').value = roomId;
                    window.roomPrice = parseFloat(roomPrice);
                    
                    // Reset dates
                    checkInPicker.clear();
                    checkOutPicker.clear();
                    document.getElementById('total_price').textContent = '$0.00';
                });
            }

            // Calculate total price
            function calculateTotal() {
                const checkIn = checkInPicker.selectedDates[0];
                const checkOut = checkOutPicker.selectedDates[0];
                
                if (checkIn && checkOut) {
                    const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                    const total = nights * window.roomPrice;
                    document.getElementById('total_price').textContent = '$' + total.toFixed(2);
                }
            }
        });
    </script>
</body>
</html> 