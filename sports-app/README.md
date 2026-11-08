# Play Together - Sports Hub (PHP + SQL Server)

Stack: HTML, CSS, vanilla JavaScript, PHP 8.3+ (PDO_SQLSRV), Microsoft SQL Server (managed with SSMS). Runs on XAMPP (Windows).

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
  config/config.php              SQL Server connection settings
  database/schema_sqlserver.sql  T-SQL: tables, constraints, demo data (run in SSMS)
  database/optional_sql_login.sql  optional SQL login (only if not using Windows Authentication)
  uploads/                       uploaded photos
  setup_check.html + api/setup_check.php   self-test page (delete both when everything works)
```
Flow when a page only shows data (example: Open play):
`openplays.html` loads -> `common.js` + `pages/openplays.js` run -> `fetch('api/openplays.php')` -> PHP runs SQL, answers JSON -> the script builds the cards inside the page.

Flow when a form is submitted (example: Join game):
`<form action="actions/openplay_action.php">` -> PHP checks CSRF + login, runs SQL in a transaction -> `header('Location: openplays.html')` -> the page loads again and shows the message.

## Setup (XAMPP + SQL Server)
1. Install **Microsoft ODBC Driver 18 (or 17) for SQL Server** (x64).
2. Install the PHP extension **pdo_sqlsrv**:
   - Check your PHP version/type: open `http://localhost/dashboard/phpinfo.php` (look at "PHP Version", "Architecture x64", "Thread Safety enabled").
   - Download *Microsoft Drivers for PHP for SQL Server* (Windows) from Microsoft / the GitHub releases page of `microsoft/msphpsql`.
     Latest (5.13.x) supports PHP 8.3, 8.4, 8.5. For PHP 8.1/8.2 use the 5.12 release.
   - Copy the DLL that matches your PHP, e.g. `php_pdo_sqlsrv_83_ts_x64.dll` (83 = PHP 8.3, ts = Thread Safe, x64) into `C:\xampp\php\ext\`.
   - Open `C:\xampp\php\php.ini` and add: `extension=php_pdo_sqlsrv_83_ts_x64.dll` (use your own file name).
   - Restart Apache in the XAMPP Control Panel.
3. Copy this folder to `C:\xampp\htdocs\sports-app` (keep the name or change `BASE_URL` in `config/config.php`). Start **Apache** only (MySQL is not needed).
4. Open **SSMS**, connect to your server, then `File > Open > File...` -> `database/schema_sqlserver.sql` -> **Execute (F5)**. It creates the `sports_hub` database, tables and demo data.
5. Edit `config/config.php`:
   - `DB_SERVER`: the same server name you type in SSMS (for example `localhost\SQLEXPRESS`).
   - **A. Windows Authentication (default):** keep `DB_USER` empty.
   - **B. SQL login:** enable Mixed Mode (SSMS: right-click server > Properties > Security > "SQL Server and Windows Authentication mode", then restart the SQL Server service), run `database/optional_sql_login.sql`, and set `DB_USER` / `DB_PASS`.
6. Open `http://localhost/sports-app/setup_check.html`. Everything should be green. Then **delete `setup_check.html` and `api/setup_check.php`**.
7. Open `http://localhost/sports-app/index.html`

## Demo accounts (password for all: `password`)
| Role    | Email                  |
|---------|------------------------|
| Player  | john@example.com       |
| Player  | maria@example.com      |
| Manager | alab@example.com (ALAB Court) |
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
| `PDO('mysql:...')`             | `PDO('sqlsrv:Server=...;Database=...')`              |
| duplicate key code `23000`     | also `23000` (SQL Server errors 2627 / 2601)         |

## Requirement checklist
- RBAC (player / manager): `require_login('player'|'manager')` (actions) and `api_require(...)` (api) in `includes/functions.php`
- Profiles (name, avatar, sports, skill level): `profile.html`, `actions/profile_save.php`, tables `users`, `user_sports`
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
| `could not find driver` | pdo_sqlsrv not loaded: wrong DLL name/version in php.ini, or Apache not restarted |
| `Login failed for user ...` | Windows Auth: Apache must run as your Windows user, and your user needs a SQL login. Or use a SQL login (step 5B) |
| `Cannot open database "sports_hub"` | Run `schema_sqlserver.sql` in SSMS |
| `network-related or instance-specific error` | Wrong `DB_SERVER`, SQL Server service stopped, or SQL Server Browser stopped (named instances) |
| `certificate verify failed` | keep `TrustServerCertificate=1` in `config/config.php` |
| `Data source name not found` | Install the Microsoft ODBC Driver 17/18 for SQL Server |
