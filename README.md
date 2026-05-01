# ChargeAlaya - EV Charging Station Management System

ChargeAlaya is a full-stack web application for managing electric vehicle (EV) charging 
stations in Nepal. It connects EV owners with charging station operators through a 
role-based platform that handles station discovery, slot reservations, charging session 
tracking, payments, feedback, and admin oversight - all in one system.

---

## Project Overview

Nepal's EV adoption is growing rapidly, but infrastructure management remains fragmented. 
ChargeAlaya addresses this by providing a centralized platform where:

- EV owners can find stations, book chargers, track sessions, and pay online
- Station owners can manage their infrastructure, set tariffs, handle reservations, and view earnings
- Admins can oversee the entire system including users, stations, sessions, and revenue

---

## Tech Stack

- Backend:     PHP 8.2 (procedural + OOP with mysqli)
- Frontend:    HTML5, CSS3, Bootstrap 5.3, JavaScript, Font Awesome 6.4
- Database:    MySQL / MariaDB 10.4
- Server:      Apache (XAMPP / WAMP)
- PDF:         Custom PHP receipt rendering
- Payments:    eSewa, Khalti, Card, Cash (simulated)

---

## Features by Role

### Public (No Login Required)
- View all charging stations with location, status, and charger details
- View individual station detail pages with ratings and available chargers
- Register as EV Owner or Station Owner

### EV Owner
- Dashboard with session stats, spending summary, and recent activity
- Add and manage vehicles (brand, model, battery capacity, connector type, licence plate)
- Browse and filter stations with real-time charger availability
- Make reservations by selecting station, charger, vehicle, date, and time slot
- View, cancel, or track reservation status
- Charging history with energy used, cost, and session duration
- Make payments via eSewa, Khalti, card, or cash
- Download and view PDF receipts for completed sessions
- Leave star ratings and written feedback for stations
- Report issues with chargers directly from the platform
- View personal spending and usage reports

### Station Owner
- Dashboard with station count, charger count, total sessions, and total revenue
- Add new charging stations with address, city, province, coordinates, and operating hours
- Edit station details and toggle status (online / offline / under maintenance)
- Manage chargers per station (type: fast/normal, power kW, connector type, status)
- Set and update tariffs (price per kWh, service fee, peak hours)
- View and manage incoming reservations (confirm, complete, cancel)
- View session history for all stations
- Maintenance log - track reported issues and resolution status
- Revenue and session reports

### Admin
- Full system dashboard: total users, station owners, stations, chargers, sessions, revenue
- User management - view all users, block or unblock accounts
- Station management - view and oversee all stations across the platform
- Session management - monitor all charging sessions system-wide
- Revenue reports across all stations and payment methods

---

## Database Schema

The database is named `25123857` and contains 10 tables:

### users
Stores all registered users across all roles.
Fields: user_id, first_name, last_name, email, password_hash, phone,
        role (admin | station_owner | ev_owner), status (active | blocked), created_at

### charging_stations
Each station belongs to one station owner (via user_id foreign key).
Fields: station_id, station_name, user_id, address, city, province,
        latitude, longitude, operating_hours,
        status (online | offline | under_maintenance)

### chargers
Each charger belongs to one station.
Fields: charger_id, station_id, charger_type (fast | normal),
        max_power_kw, connector_type (CCS2 / CHAdeMO / Type2 etc.),
        status (available | in_use | maintenance)

### reservations
Tracks slot bookings by EV owners.
Fields: reservation_id, user_id, charger_id, vehicle_id,
        start_time, end_time, status (confirmed | cancelled | completed)

### charging_sessions
Records actual charging activity.
Fields: session_id, user_id, vehicle_id, charger_id,
        start_time, end_time, energy_used_kwh, cost, session_status

### payments
One payment record per charging session.
Fields: payment_id, session_id, user_id, amount,
        payment_method (esewa | khalti | card | cash | pending),
        payment_status (pending | paid), transaction_time

### tariffs
One tariff per station, set by the station owner.
Fields: tariff_id, station_id, price_per_kwh, service_fee,
        peak_start_time, peak_end_time

### vehicles
EV owner's registered vehicles.
Fields: vehicle_id, user_id, brand, model, battery_capacity_kwh,
        connector_type, license_plate, manufacturing_year

### feedback
Station reviews submitted by EV owners.
Fields: feedback_id, user_id, station_id, rating (1-5), comment, created_at

### maintenance
Charger issue reports with resolution tracking.
Fields: maintenance_id, charger_id, reported_by (user_id),
        issue_description, reported_date, fixed_date,
        status (open | in_progress | resolved)

---

## Foreign Key Relationships

- chargers.station_id             -> charging_stations.station_id  (CASCADE DELETE)
- charging_sessions.user_id       -> users.user_id                 (CASCADE DELETE)
- charging_sessions.vehicle_id    -> vehicles.vehicle_id           (CASCADE DELETE)
- charging_sessions.charger_id    -> chargers.charger_id           (CASCADE DELETE)
- charging_stations.user_id       -> users.user_id                 (CASCADE DELETE)
- feedback.user_id                -> users.user_id                 (CASCADE DELETE)
- feedback.station_id             -> charging_stations.station_id  (CASCADE DELETE)
- maintenance.charger_id          -> chargers.charger_id           (CASCADE DELETE)
- maintenance.reported_by         -> users.user_id                 (CASCADE DELETE)
- payments.session_id             -> charging_sessions.session_id  (CASCADE DELETE)
- payments.user_id                -> users.user_id                 (CASCADE DELETE)
- reservations.user_id            -> users.user_id                 (CASCADE DELETE)
- reservations.charger_id         -> chargers.charger_id           (CASCADE DELETE)
- reservations.vehicle_id         -> vehicles.vehicle_id           (SET NULL)
- tariffs.station_id              -> charging_stations.station_id  (CASCADE DELETE)
- vehicles.user_id                -> users.user_id                 (CASCADE DELETE)

---

## Project Structure

    chargealaya/
    ├── index.php                            # Homepage with live stats and features
    ├── config/
    │   └── database.php                     # Database connection class (mysqli OOP)
    ├── auth/
    │   ├── login.php                        # Login with role-based redirect
    │   ├── register.php                     # Registration (ev_owner or station_owner)
    │   └── logout.php                       # Session destroy and redirect
    ├── partials/
    │   ├── header.php                       # Global navbar, flash messages, CSS imports
    │   ├── footer.php                       # Global footer
    │   └── functions.php                    # Auth helpers, sanitize, format functions
    ├── public/
    │   ├── stations.php                     # Public station listing with filters
    │   └── station_detail.php               # Station page with chargers and feedback
    ├── user/
    │   ├── dashboard.php                    # EV owner dashboard with stats
    │   ├── profile.php                      # View and edit user profile
    │   ├── vehicles.php                     # My vehicles list
    │   ├── add_vehicle.php                  # Register a new vehicle
    │   ├── edit_vehicle.php                 # Edit vehicle details
    │   ├── make_reservation.php             # Reservation booking form
    │   ├── process_reservation.php          # Reservation POST handler
    │   ├── reservations.php                 # My reservations list
    │   ├── reservation_card.php             # Reservation card component
    │   ├── charging_history.php             # Past sessions with cost and energy
    │   ├── make_payment.php                 # Payment method selection and processing
    │   ├── view_receipt.php                 # Receipt viewer page
    │   ├── receipt_pdf.php                  # PDF receipt generator
    │   ├── download_receipt.php             # Receipt download handler
    │   ├── leave_feedback.php               # Station review and star rating form
    │   ├── report_issue.php                 # Report a charger issue
    │   └── my_reports.php                   # Personal usage and spending analytics
    ├── station_owner/
    │   ├── dashboard.php                    # Owner dashboard with revenue stats
    │   ├── add_station.php                  # Add a new charging station
    │   ├── edit_station.php                 # Edit station info and status
    │   ├── manage_stations.php              # All stations list with actions
    │   ├── manage_chargers.php              # Chargers per station
    │   ├── set_tariff.php                   # Set price per kWh and service fee
    │   ├── manage_reservations.php          # Incoming reservations management
    │   ├── complete_reservation.php         # Mark reservation complete
    │   ├── cancel_reservation.php           # Cancel a reservation
    │   ├── session_history.php              # Session log across all stations
    │   ├── maintenance.php                  # Maintenance issue tracker
    │   └── reports.php                      # Revenue and session reports
    ├── admin/
    │   ├── dashboard.php                    # System-wide stats overview
    │   ├── users.php                        # User management (block/unblock)
    │   ├── stations.php                     # All stations overview
    │   ├── sessions.php                     # All sessions oversight
    │   └── reports.php                      # Platform-wide revenue reports
    ├── assets/
    │   ├── css/
    │   │   ├── main.css                     # Global styles, animations, components
    │   │   ├── receipt.css                  # Print and PDF receipt styling
    │   │   ├── view_receipt.css             # Receipt viewer page styles
    │   │   ├── review_star.css              # Star rating input styles
    │   │   └── payment_option.css           # Payment method card styles
    │   ├── js/scripts/
    │   │   ├── main.js                      # Global JS utilities
    │   │   ├── make_reservation.js          # Reservation form dynamic logic
    │   │   ├── manage_reservations.js       # Reservation management interactions
    │   │   ├── set_tariff.js                # Tariff form logic
    │   │   ├── stations.js                  # Station filter and search
    │   │   ├── station_detail.js            # Station detail page interactions
    │   │   └── view_receipt.js              # Receipt page logic
    │   └── img/
    │       ├── chargealaya_logo.png         # App logo (favicon + navbar)
    │       ├── esewa_logo.png               # eSewa payment gateway logo
    │       └── khalti_logo.webp             # Khalti payment gateway logo
    └── database/
        └── 25123857.sql                     # Full database dump with seed data

---

## Setup Instructions

### Prerequisites
- XAMPP or WAMP (PHP 8.x + Apache + MySQL)
- Web browser

### Steps

1. Extract the project folder into your web server root:

       Windows:  C:/xampp/htdocs/chargealaya
       Linux:    /var/www/html/chargealaya

2. Start Apache and MySQL from the XAMPP or WAMP control panel.

3. Import the database:
   - Open phpMyAdmin at http://localhost/phpmyadmin
   - Create a new database named exactly: 25123857
   - Select the database, click Import
   - Choose the file: database/25123857.sql
   - Click Go

4. Verify the database config in config/database.php:

       define('DB_HOST', 'localhost');
       define('DB_USER', 'root');
       define('DB_PASS', '');
       define('DB_NAME', '25123857');

   Update DB_USER and DB_PASS if your MySQL credentials differ.

5. Open your browser and go to:

       http://localhost/chargealaya/

---

## Test Credentials

| Role          | Full Name     | Email                    | Password   |
|---------------|---------------|--------------------------|------------|
| Admin         | Admin User    | admin@chargealaya.com    | admin123   |
| Station Owner | Harry Walker  | harrywalker@gmail.com    | Harry@123  |
| EV Owner      | John Cena     | johncena@gmail.com       | John@123   |
| EV Owner      | Roman Reigns  | romanreigns@gmail.com    | Roman@123  |

---

## Sample Seed Data

The database comes pre-loaded with realistic test data:

- 4 users (1 admin, 1 station owner, 2 EV owners)
- 3 charging stations:
    - TU Campus Charging Hub, Kirtipur, Kathmandu (24/7)
    - Thamel EV Station, Kathmandu (06:00-22:00)
    - Pokhara Lakeside Charging, Pokhara (24/7)
- 7 chargers (mix of fast DC and normal AC, CCS2 / CHAdeMO / Type2)
- 6 charging sessions with real duration, energy, and cost values
- 6 payments (eSewa, Khalti, card, cash - mix of paid and pending)
- 6 reservations (confirmed, linked to real chargers and vehicles)
- 4 feedback entries with star ratings and comments
- 3 maintenance reports (1 open, 1 in progress, 1 resolved)
- 4 registered vehicles across both EV owner accounts

---

## Security Implementation

- Passwords hashed using password_hash() with PASSWORD_DEFAULT (bcrypt)
- All user inputs sanitized via htmlspecialchars() and strip_tags()
- Prepared statements with bind_param() used throughout to prevent SQL injection
- Role-based access control enforced on every protected page:
    - requireLogin()        - redirects unauthenticated users to login
    - requireAdmin()        - restricts access to admin role only
    - requireStationOwner() - restricts access to station_owner role only
- Session-based authentication with role-aware redirect on login and logout
- Blocked users are denied login with a descriptive error message

---

## Key Design Decisions

- Currency: All amounts are in NPR (Nepalese Rupees)
- Payment methods: eSewa and Khalti are Nepal's two most-used digital wallets,
  included alongside card and cash (no live API keys required for testing)
- Connector types: CCS2, CHAdeMO, Type2 - the three standard EV connector 
  formats in use across Nepal and internationally
- Charger types: Fast (DC, 50-150kW) and Normal (AC, 7.4-22kW)
- Default tariff fallback: NPR 15.00/kWh and NPR 50.00 service fee applied 
  automatically if a station owner has not yet configured a custom tariff
- Station coordinates: Real GPS coordinates used for seeded stations in 
  Kathmandu and Pokhara, ready for map integration
- Database name: Uses student ID (25123857) as the database name per 
  university submission convention

---

## Author

Utsav Rai
School of Architecture, Built Environment, Computing and Engineering
Birmingham City University, Birmingham, United Kingdom
GitHub: https://github.com/Utu8848

---

## License

This project is licensed under the MIT License. See the LICENSE file for details.
