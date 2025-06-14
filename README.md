How to Run the Project with XAMPP

This project can be run in a local XAMPP environment by following the steps below.
The source code is available on GitHub: https://github.com/Hovhannisyan111/hotel
1. Install XAMPP
    Download and install XAMPP on your system.
    After installation, open the XAMPP Control Panel and start the Apache and MySQL modules.
2. Clone the Code from GitHub
    Open your terminal (Command Prompt or PowerShell on Windows, Terminal on Linux/Mac).
    Navigate to the htdocs directory inside your XAMPP installation:
        Example paths:
            C:\xampp\htdocs (Windows)
            /opt/lampp/htdocs (Linux)
    Run the following command to clone the repository:
        git clone https://github.com/Hovhannisyan111/hotel.git

3. Set Up the Database
    Open your browser and go to http://localhost/phpmyadmin.
    Create a new database named hotel.
    Import the SQL file located at hotel_system/database/hotel_db.sql:
        Click on the Import tab.
        Choose the SQL file and import it.
        This will create the necessary tables: users, rooms, and reservations.

4. Configure the Application
    Open the file hotel/includes/config.php in a text editor.
    Ensure the database configuration matches your local setup:

DB_HOST = 'localhost';
DB_USER = 'root';
DB_PASS = '';
DB_NAME = 'hotel';

Save the file.

5. Run the System
    Open your browser and go to: http://localhost/hotel/hotel
    You will see the homepage (index.php), where you can view rooms, register, or log in.
    To access the admin panel, use an admin account (e.g., username: admin, password: admin123).
