# Play Together - Pickleball Club (PHP + SQL Server)

Stack: HTML, CSS, vanilla JavaScript, PHP 8.3+ (`sqlsrv`), Microsoft SQL Server (managed with SSMS). Runs on XAMPP (Windows).

## Project structure (HTML is a separate .html file from PHP)
Same idea as the lab activities: the pages are plain `.html` files, and PHP files only do the work.
```
sports-app/
  *.html, manager/*.html         THE PAGES: pure HTML, no PHP inside. Forms use <form action="actions/xxx.php" method="post">
  assets/css/style.css           styles
  assets/js/common.js            runs on every page: header/footer, login state, CSRF token, flash messages, helpers
  assets/js/pages/*.js           one small script per page: asks api/*.php for data and shows it in the page
  actions/*.php                  FORM HANDLERS (like connect.php in the lab): read $_POST, run SQL, then redirect to a .html page
  api/*.php, api/manager/*.php   DATA FILES: run a SELECT and answer with JSON (no HTML) for the page script
  includes/functions.php         helpers: db_* shortcuts, security (CSRF, login/RBAC), availability logic
  config/config.php              SQL Server connection settings (sqlsrv_connect to SSMS)
  uploads/                       uploaded photos
  setup_check.html + api/setup_check.php   self-test page (delete both when everything works)
```
Flow when a page only shows data (example: Open play):
`openplays.html` loads -> `common.js` + `pages/openplays.js` run -> `fetch('api/openplays.php')` -> PHP runs SQL, answers JSON -> the script builds the cards inside the page.

Flow when a form is submitted (example: Join game):
`<form action="actions/openplay_action.php">` -> PHP checks CSRF + login, runs SQL in a transaction -> `header('Location: openplays.html')` -> the page loads again and shows the message.

## Setup (XAMPP + SQL Server)
1. Install **Microsoft ODBC Driver 18 (or 17) for SQL Server** (x64).
2. Install the PHP extension **sqlsrv**:
   - Check your PHP version/type: open `http://localhost/dashboard/phpinfo.php` (look at "PHP Version", "Architecture x64", "Thread Safety enabled").
   - Download *Microsoft Drivers for PHP for SQL Server* (Windows) from Microsoft / the GitHub releases page of `microsoft/msphpsql`.
   - Copy the DLL that matches your PHP, e.g. `php_sqlsrv_83_ts_x64.dll` into `C:\xampp\php\ext\`.
   - Open `C:\xampp\php\php.ini` and add: `extension=php_sqlsrv_83_ts_x64.dll` (use your own file name).
   - Restart Apache in the XAMPP Control Panel.
3. Place this folder under `C:\xampp\htdocs\pickleball\sports-app` (keep the name or update `BASE_URL` in `config/config.php`). Start **Apache** only (MySQL is not needed).
4. Ensure your tables exist in the `DLSU` database in **SSMS**.
5. Edit `config/config.php`:
   - `$serverName`: the same server name you type in SSMS (for example `LAPTOP-9M40AKII\SQLEXPRESS`).
   - `$connectionOptions`: `Database => "DLSU"`, and keep `UID` / `PWD` empty for **Windows Authentication**.
6. Open `http://localhost/pickleball/sports-app/setup_check.html`. Everything should be green.
7. Open `http://localhost/pickleball/sports-app/index.html`

## Demo accounts (players password: `password` | admin password: `admin12345`)
| Role    | Email                  |
|---------|------------------------|
| Player  | john@example.com       |
| Player  | maria@example.com      |
| Admin   | admin@example.com (ALAB Court, password: `admin12345`) |
| Manager | pickle@example.com (Play and Pickle) |
| Manager | playzone@example.com (Playzone) |

## MySQL -> SQL Server changes (for your defense)
| MySQL                          | SQL Server (T-SQL)                                  |
|--------------------------------|------------------------------------------------------|
| `AUTO_INCREMENT`               | `IDENTITY(1,1)`                                      |
| `ENUM(...)`                    | `VARCHAR` + `CHECK (col IN (...))`                   |
| `INT UNSIGNED`, `TINYINT(1)`   | `INT`, `TINYINT` (+ `CHECK`)                         |
| `TEXT`, `VARCHAR`              | `NVARCHAR(MAX)`, `NVARCHAR(n)` (Unicode)             |
| `LIMIT 3`                      | `SELECT TOP 3 ...`                                   |
| `CURDATE()`, `NOW()`           | today/now passed from PHP (`date()`), `SYSDATETIME()` default |
| `SELECT ... FOR UPDATE`        | `FROM table WITH (UPDLOCK, ROWLOCK)`                 |
| `lastInsertId()`               | `INSERT ... OUTPUT INSERTED.id VALUES (...)`         |
| `DELETE cb FROM ... JOIN ...`  | same syntax works in T-SQL                           |
| inline `INDEX` in CREATE TABLE | separate `CREATE INDEX`                              |
| `SET @pw = ...`                | `DECLARE @pw NVARCHAR(255) = ...`                    |
| `mysqli_connect(...)`          | `sqlsrv_connect($serverName, $connectionOptions)`    |
| duplicate key code `23000`     | also `23000` (SQL Server errors 2627 / 2601)         |

## Requirement checklist
- RBAC (player / manager): `require_login('player'|'manager')` (actions) and `api_require(...)` (api) in `includes/functions.php`
- Profiles (name, avatar, skill level): `profile.html`, `actions/profile_save.php`, table `users`
- Search by location and court name: `venues.html`, `api/venues.php`
- Interactive date picker + available slots: `court.html`, `assets/js/pages/court.js`, `api/availability.php`
- Booking page with confirmation: `actions/book.php`, `booking_confirmation.html`
- Open play list by date/venue, slots left, roster, join/leave 1..n slots: `openplays.html`, `api/openplays.php`, `actions/openplay_action.php`
- Player dashboard (upcoming courts and open plays, past activity, receipts): `dashboard.html`, `receipt.html`
- Manager: open plays CRUD, block hours, court rentals CRUD, bookings: `manager/*.html`, `actions/manager_*.php`, `api/manager/*.php`
- Enhancements: cancel bookings, receipts (printable), venue photo upload, CSRF protection,
  double-booking protection at database level, hide/show courts.

## Troubleshooting
| Message in setup_check.html / browser | Fix |
|---|---|
| `sqlsrv NOT loaded` | `php_sqlsrv` not loaded: wrong DLL name/version in php.ini, or Apache not restarted |
| `Login failed for user ...` | Windows Auth: Apache must run as your Windows user, and your user needs a SQL login |
| `Cannot open database` | Verify the database name in SSMS matches `config/config.php` |
| `network-related or instance-specific error` | Wrong `$serverName`, SQL Server service stopped, or SQL Server Browser stopped (named instances) |
| `certificate verify failed` | keep `"TrustServerCertificate" => true` in `config/config.php` |
| `Data source name not found` | Install the Microsoft ODBC Driver 17/18 for SQL Server |
