# Play Together - Sports Hub (PHP + SQL Server)

Stack: HTML, CSS, vanilla JavaScript, PHP 8.3+ (PDO_SQLSRV), Microsoft SQL Server (managed with SSMS). Runs on XAMPP (Windows).

## Project structure (HTML is separated from PHP logic)
```
sports-app/
  index.php, login.php, ...      CONTROLLERS: PHP only (checks login, handles forms, runs SQL, then calls render())
  manager/*.php                  controllers for the venue-manager pages
  views/*.php, views/manager/    VIEWS: the HTML pages (only echo / if / foreach inside)
  views/layout/                  header, footer, manager sub-menu (shared HTML)
  includes/functions.php         helpers: db_* shortcuts, render(), security, availability logic
  config/config.php              SQL Server connection settings
  api/availability.php           JSON endpoint used by the date picker
  database/schema_sqlserver.sql  T-SQL: tables, constraints, demo data (run in SSMS)
  database/optional_sql_login.sql  optional SQL login (only if not using Windows Authentication)
  assets/ (css, js)  uploads/    static files and uploaded photos
  setup_check.php                self-test page (delete it when everything works)
```
Flow of one request: browser -> controller (e.g. `openplays.php`) -> SQL via `db_all()/db_one()/...` -> `render('openplays', [...data])` -> `views/layout/header.php` + `views/openplays.php` + `views/layout/footer.php`.

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
6. Open `http://localhost/sports-app/setup_check.php`. Everything should be green. Then **delete `setup_check.php`**.
7. Open `http://localhost/sports-app/`

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
- RBAC (player / manager): `require_login('player'|'manager')` in `includes/functions.php`
- Profiles (name, avatar, sports, skill level): `profile.php`, tables `users`, `user_sports`
- Search by location and court name: `venues.php`
- Interactive date picker + available slots: `court.php`, `assets/js/app.js`, `api/availability.php`
- Booking page with confirmation: `book.php`, `booking_confirmation.php`
- Open play list by date/venue, slots left, roster, join/leave 1..n slots: `openplays.php`, `openplay_action.php`
- Player dashboard (upcoming courts and open plays, past activity, receipts): `dashboard.php`, `receipt.php`
- Manager: open plays CRUD, block hours, court rentals CRUD, bookings: `manager/*.php`
- Enhancements: cancel bookings, receipts (printable), venue photo upload, CSRF protection,
  double-booking protection at database level, hide/show courts.

## Troubleshooting
| Message in setup_check.php / browser | Fix |
|---|---|
| `could not find driver` | pdo_sqlsrv not loaded: wrong DLL name/version in php.ini, or Apache not restarted |
| `Login failed for user ...` | Windows Auth: Apache must run as your Windows user, and your user needs a SQL login. Or use a SQL login (step 5B) |
| `Cannot open database "sports_hub"` | Run `schema_sqlserver.sql` in SSMS |
| `network-related or instance-specific error` | Wrong `DB_SERVER`, SQL Server service stopped, or SQL Server Browser stopped (named instances) |
| `certificate verify failed` | keep `TrustServerCertificate=1` in `config/config.php` |
| `Data source name not found` | Install the Microsoft ODBC Driver 17/18 for SQL Server |
