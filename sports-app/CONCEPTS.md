# Study guide: concepts you must be able to explain

## 1. How a request flows (static HTML + PHP handlers + JSON)
- **Pages** (`*.html`, `manager/*.html`): pure HTML. No PHP inside, so they are separate from the logic. A browser cannot run PHP inside .html,
  so a page gets its data with JavaScript `fetch()` from a PHP file that answers JSON.
- **Show data**: `page.html` -> `assets/js/pages/page.js` -> `fetch('api/xxx.php')` -> PHP does `SELECT` -> JSON -> the script fills the page.
- **Save data**: `<form action="actions/xxx.php" method="post">` -> PHP (1) checks login/role + CSRF, (2) validates, (3) runs INSERT/UPDATE in PDO,
  (4) `header('Location: page.html')`. Errors and success messages are kept in the session (flash) and shown by the page after the redirect.
- **Why separate them?** The design (HTML/CSS) can be changed without touching the logic, and the PHP has no HTML (separation of concerns).
  This is the same pattern as the lab: a plain form page + a `connect.php` that inserts and redirects.

## 2. Database design (normalization)
- `users` (role column) -> one login table for both personas. RBAC = checking `role` on every protected page.
- `sports` is a lookup table; `user_sports` is a many-to-many junction table (preferred sports).
- `venues` 1-to-1 with a manager (UNIQUE `manager_id`); `courts` many-to-1 with venue.
- `bookings` (header) + `booking_slots` (one row per hour). Why? The UNIQUE constraint
  `(court_id, slot_date, slot_hour)` makes double booking impossible at the database level.
  Cancelling deletes the slot rows, which frees the hours.
- `open_plays` + `open_play_registrations`. Slots left is NOT stored (would go out of sync). It is computed:
  `max_slots - SUM(slots)` of rows with status `joined` (subquery in `fetch_open_plays()`).
- `court_blocks` stores maintenance/holiday hours. `court_slots()` combines booked + blocked + past.
- Foreign keys + `ON DELETE CASCADE` keep data consistent. Indexes are on columns used in WHERE/JOIN.

## 3. SQL Server specifics (expect questions on these)
- `IDENTITY(1,1)` auto-numbers the primary key. The new id is read back with `OUTPUT INSERTED.id` (safer than `@@IDENTITY`).
- No ENUM in SQL Server, so a `CHECK` constraint limits the allowed values (`role IN ('player','manager')`).
- `TOP n` replaces `LIMIT n`.
- `WITH (UPDLOCK, ROWLOCK)` is the row lock used where MySQL used `FOR UPDATE`.
- `NVARCHAR` stores Unicode text (names with n-tilde, etc.). `DATETIME2` / `DATE` / `TIME` are language independent for `YYYY-MM-DD`.
- SQL Server allows only ONE cascade path between two tables; the foreign keys are designed so none conflict.
- `T-SQL` batches are separated by `GO` in SSMS (GO is an SSMS command, not SQL).
- Connection: `PDO('sqlsrv:Server=...;Database=...')`. Windows Authentication = no user/password in the connection.

## 4. Security
- Passwords: `password_hash()` / `password_verify()` (bcrypt). Never store plain text.
- SQL injection: PDO prepared statements with `?` placeholders. User input never goes inside the SQL string.
  (Even the `TOP n` value is cast to `(int)` before it is written into the SQL.)
- XSS: every value the JavaScript puts into the page goes through `esc()` (escapes & < > " '). PHP never prints HTML.
- CSRF: hidden token in every POST form (filled in by `common.js` from `api/session.php`), checked by `csrf_check()`. An empty token never matches.
- Session fixation: `session_regenerate_id(true)` after login.
- File uploads: check real MIME type with `finfo`, size limit, random file name, `uploads/.htaccess` blocks PHP execution.
- Authorization: managers can only touch rows that belong to THEIR venue (`WHERE venue_id = ?`).
- `includes/`, `config/`, `database/` have an `.htaccess` that blocks direct browser access.

## 5. Transactions and race conditions
- `actions/book.php`: BEGIN -> re-check every hour -> insert booking + slots -> COMMIT. If anything fails -> ROLLBACK.
  If two players click at the same time, the UNIQUE constraint rejects the second (SQLSTATE 23000, error 2627).
- `actions/openplay_action.php`: `WITH (UPDLOCK, ROWLOCK)` locks the open play row, so two players cannot take the last slot together.

## 6. Front-end JavaScript (`assets/js/`)
- `common.js` (every page): calls `api/session.php` -> who is logged in + CSRF token + flash messages; builds the header/footer; puts the token in every form.
- `pages/*.js`: one per page. Example `court.js`: date picker `change` event -> `fetch('api/availability.php?...')` -> JSON -> slot buttons (DOM).
- Selection rule: always one continuous block of hours; total = hours x rate.
- The server does NOT trust the browser: `actions/book.php` validates everything again. If the API says 401 (not logged in) the page goes to `login.html`.

## 7. Responsive CSS
Mobile-first, CSS variables for the palette, CSS Grid (`repeat(auto-fill, minmax())`) for card layouts,
a menu toggle under 960px, tables scroll horizontally on small screens.

## Likely defense questions
1. Why two tables for bookings? 2. How do you stop double booking? 3. Why compute slots left instead of storing it?
4. What does `UPDLOCK` do? 5. How do you prevent SQL injection / XSS / CSRF? 6. How does RBAC work here?
7. Walk through what happens when I click "Join game". 8. What is a junction table and where did you use one?
9. Why are the .html pages separate from the .php files? 10. What changed when you moved from MySQL to SQL Server?
