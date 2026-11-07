# Study guide: concepts you must be able to explain

## 1. How a request flows (MVC-lite, no framework)
Browser -> a controller PHP file (e.g. `openplays.php`) -> helpers in `includes/functions.php` -> PDO (PDO_SQLSRV) query to SQL Server
-> controller calls `render('openplays', $data)` -> the view in `views/` prints HTML -> browser.
- **Controller** (root and `manager/` files): (1) guard/login check, (2) handle POST, (3) run SELECT queries, (4) call `render()`.
- **View** (`views/`): HTML only. It never queries the database; it only prints what the controller gave it, with `e()` to escape output.
- **Why separate them?** Easier to read and change the design without touching the logic (separation of concerns).

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
- XSS: every printed value goes through `e()` = `htmlspecialchars`.
- CSRF: hidden token in every POST form, checked by `csrf_check()`.
- Session fixation: `session_regenerate_id(true)` after login.
- File uploads: check real MIME type with `finfo`, size limit, random file name, `uploads/.htaccess` blocks PHP execution.
- Authorization: managers can only touch rows that belong to THEIR venue (`WHERE venue_id = ?`).
- `views/`, `includes/`, `config/`, `database/` have an `.htaccess` that blocks direct browser access.

## 5. Transactions and race conditions
- `book.php`: BEGIN -> re-check every hour -> insert booking + slots -> COMMIT. If anything fails -> ROLLBACK.
  If two players click at the same time, the UNIQUE constraint rejects the second (SQLSTATE 23000, error 2627).
- `openplay_action.php`: `WITH (UPDLOCK, ROWLOCK)` locks the open play row, so two players cannot take the last slot together.

## 6. Front-end JavaScript (`assets/js/app.js`)
- Date picker `change` event -> `fetch('api/availability.php?...')` -> JSON -> build slot buttons (DOM).
- Selection rule: always one continuous block of hours; total = hours x rate.
- The server does NOT trust the browser: `book.php` validates everything again.

## 7. Responsive CSS
Mobile-first, CSS variables for the palette, CSS Grid (`repeat(auto-fill, minmax())`) for card layouts,
a menu toggle under 960px, tables scroll horizontally on small screens.

## Likely defense questions
1. Why two tables for bookings? 2. How do you stop double booking? 3. Why compute slots left instead of storing it?
4. What does `UPDLOCK` do? 5. How do you prevent SQL injection / XSS / CSRF? 6. How does RBAC work here?
7. Walk through what happens when I click "Join game". 8. What is a junction table and where did you use one?
9. Why did you split controllers and views? 10. What changed when you moved from MySQL to SQL Server?
