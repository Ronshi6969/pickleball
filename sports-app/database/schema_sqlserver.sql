-- =====================================================================
-- Play Together: Sports Hub  |  SQL Server (T-SQL) schema + demo data
-- Run in SQL Server Management Studio (SSMS):
--   File > Open > File... > pick this file > press Execute (F5)
-- It is safe to run again: it drops and recreates all tables.
-- Demo password for ALL demo accounts: password
-- =====================================================================

-- 1) Create the database if it does not exist yet
IF DB_ID(N'sports_hub') IS NULL
    CREATE DATABASE sports_hub;
GO

USE sports_hub;
GO

-- 2) Drop old tables (children first, because of foreign keys)
IF OBJECT_ID(N'dbo.open_play_registrations', N'U') IS NOT NULL DROP TABLE dbo.open_play_registrations;
IF OBJECT_ID(N'dbo.open_plays',              N'U') IS NOT NULL DROP TABLE dbo.open_plays;
IF OBJECT_ID(N'dbo.booking_slots',           N'U') IS NOT NULL DROP TABLE dbo.booking_slots;
IF OBJECT_ID(N'dbo.bookings',                N'U') IS NOT NULL DROP TABLE dbo.bookings;
IF OBJECT_ID(N'dbo.court_blocks',            N'U') IS NOT NULL DROP TABLE dbo.court_blocks;
IF OBJECT_ID(N'dbo.courts',                  N'U') IS NOT NULL DROP TABLE dbo.courts;
IF OBJECT_ID(N'dbo.venues',                  N'U') IS NOT NULL DROP TABLE dbo.venues;
IF OBJECT_ID(N'dbo.user_sports',             N'U') IS NOT NULL DROP TABLE dbo.user_sports;
IF OBJECT_ID(N'dbo.sports',                  N'U') IS NOT NULL DROP TABLE dbo.sports;
IF OBJECT_ID(N'dbo.users',                   N'U') IS NOT NULL DROP TABLE dbo.users;
GO

-- 3) Tables
-- Lookup table: one row per sport (avoids repeating sport names everywhere)
CREATE TABLE dbo.sports (
  id   INT IDENTITY(1,1) NOT NULL,
  name NVARCHAR(40) NOT NULL,
  CONSTRAINT pk_sports PRIMARY KEY (id),
  CONSTRAINT uq_sports_name UNIQUE (name)
);

-- One table for all accounts; "role" drives RBAC (player / manager)
CREATE TABLE dbo.users (
  id            INT IDENTITY(1,1) NOT NULL,
  name          NVARCHAR(100) NOT NULL,
  email         NVARCHAR(150) NOT NULL,
  password_hash NVARCHAR(255) NOT NULL,
  role          VARCHAR(10)   NOT NULL CONSTRAINT df_users_role  DEFAULT 'player',
  avatar        NVARCHAR(255) NULL,
  skill_level   VARCHAR(12)   NOT NULL CONSTRAINT df_users_skill DEFAULT 'beginner',
  created_at    DATETIME2(0)  NOT NULL CONSTRAINT df_users_created DEFAULT SYSDATETIME(),
  CONSTRAINT pk_users PRIMARY KEY (id),
  CONSTRAINT uq_users_email UNIQUE (email),
  CONSTRAINT ck_users_role  CHECK (role IN ('player','manager')),
  CONSTRAINT ck_users_skill CHECK (skill_level IN ('beginner','intermediate','advanced'))
);

-- Many-to-many: a user can prefer many sports, a sport has many fans
CREATE TABLE dbo.user_sports (
  user_id  INT NOT NULL,
  sport_id INT NOT NULL,
  CONSTRAINT pk_user_sports PRIMARY KEY (user_id, sport_id),
  CONSTRAINT fk_us_user  FOREIGN KEY (user_id)  REFERENCES dbo.users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_us_sport FOREIGN KEY (sport_id) REFERENCES dbo.sports(id) ON DELETE CASCADE
);

-- A manager owns exactly one venue (UNIQUE manager_id)
CREATE TABLE dbo.venues (
  id           INT IDENTITY(1,1) NOT NULL,
  manager_id   INT NOT NULL,
  name         NVARCHAR(120) NOT NULL,
  address      NVARCHAR(200) NOT NULL,
  city         NVARCHAR(80)  NOT NULL,
  description  NVARCHAR(MAX) NULL,
  photo        NVARCHAR(255) NULL,
  opening_hour TINYINT NOT NULL CONSTRAINT df_venues_open  DEFAULT 6,    -- 0-23
  closing_hour TINYINT NOT NULL CONSTRAINT df_venues_close DEFAULT 22,   -- 1-24 (exclusive end)
  CONSTRAINT pk_venues PRIMARY KEY (id),
  CONSTRAINT uq_venues_manager UNIQUE (manager_id),
  CONSTRAINT fk_venues_manager FOREIGN KEY (manager_id) REFERENCES dbo.users(id) ON DELETE CASCADE,
  CONSTRAINT ck_venues_hours CHECK (opening_hour BETWEEN 0 AND 23 AND closing_hour BETWEEN 1 AND 24 AND closing_hour > opening_hour)
);
CREATE INDEX idx_venue_city ON dbo.venues (city);

CREATE TABLE dbo.courts (
  id          INT IDENTITY(1,1) NOT NULL,
  venue_id    INT NOT NULL,
  sport_id    INT NOT NULL,
  name        NVARCHAR(80) NOT NULL,
  hourly_rate DECIMAL(10,2) NOT NULL,
  is_active   TINYINT NOT NULL CONSTRAINT df_courts_active DEFAULT 1,
  CONSTRAINT pk_courts PRIMARY KEY (id),
  CONSTRAINT fk_courts_venue FOREIGN KEY (venue_id) REFERENCES dbo.venues(id) ON DELETE CASCADE,
  CONSTRAINT fk_courts_sport FOREIGN KEY (sport_id) REFERENCES dbo.sports(id),
  CONSTRAINT ck_courts_active CHECK (is_active IN (0,1))
);
CREATE INDEX idx_court_name ON dbo.courts (name);

-- Maintenance / holidays: court unavailable from start_hour up to (not incl.) end_hour
CREATE TABLE dbo.court_blocks (
  id         INT IDENTITY(1,1) NOT NULL,
  court_id   INT NOT NULL,
  block_date DATE NOT NULL,
  start_hour TINYINT NOT NULL,
  end_hour   TINYINT NOT NULL,
  reason     NVARCHAR(150) NULL,
  CONSTRAINT pk_court_blocks PRIMARY KEY (id),
  CONSTRAINT fk_blocks_court FOREIGN KEY (court_id) REFERENCES dbo.courts(id) ON DELETE CASCADE,
  CONSTRAINT ck_blocks_hours CHECK (start_hour BETWEEN 0 AND 23 AND end_hour BETWEEN 1 AND 24 AND end_hour > start_hour)
);
CREATE INDEX idx_block_lookup ON dbo.court_blocks (court_id, block_date);

-- Court rental header (one row per reservation)
CREATE TABLE dbo.bookings (
  id           INT IDENTITY(1,1) NOT NULL,
  reference    VARCHAR(20) NOT NULL,
  user_id      INT NOT NULL,
  court_id     INT NOT NULL,
  booking_date DATE NOT NULL,
  start_hour   TINYINT NOT NULL,
  end_hour     TINYINT NOT NULL,
  total_price  DECIMAL(10,2) NOT NULL,
  status       VARCHAR(10) NOT NULL CONSTRAINT df_bookings_status DEFAULT 'confirmed',
  created_at   DATETIME2(0) NOT NULL CONSTRAINT df_bookings_created DEFAULT SYSDATETIME(),
  CONSTRAINT pk_bookings PRIMARY KEY (id),
  CONSTRAINT uq_bookings_reference UNIQUE (reference),
  CONSTRAINT fk_bookings_user  FOREIGN KEY (user_id)  REFERENCES dbo.users(id),
  CONSTRAINT fk_bookings_court FOREIGN KEY (court_id) REFERENCES dbo.courts(id),
  CONSTRAINT ck_bookings_status CHECK (status IN ('confirmed','cancelled'))
);
CREATE INDEX idx_booking_user ON dbo.bookings (user_id, booking_date);

-- One row per booked hour. The UNIQUE constraint is what makes double-booking
-- impossible, even if two people click "Confirm" at the same moment.
-- Rows are deleted when a booking is cancelled, which frees the hour.
CREATE TABLE dbo.booking_slots (
  id         INT IDENTITY(1,1) NOT NULL,
  booking_id INT NOT NULL,
  court_id   INT NOT NULL,
  slot_date  DATE NOT NULL,
  slot_hour  TINYINT NOT NULL,
  CONSTRAINT pk_booking_slots PRIMARY KEY (id),
  CONSTRAINT fk_slots_booking FOREIGN KEY (booking_id) REFERENCES dbo.bookings(id) ON DELETE CASCADE,
  CONSTRAINT fk_slots_court   FOREIGN KEY (court_id)   REFERENCES dbo.courts(id),
  CONSTRAINT uq_court_hour UNIQUE (court_id, slot_date, slot_hour)
);

CREATE TABLE dbo.open_plays (
  id          INT IDENTITY(1,1) NOT NULL,
  venue_id    INT NOT NULL,
  sport_id    INT NOT NULL,
  title       NVARCHAR(120) NOT NULL,
  description NVARCHAR(MAX) NULL,
  play_date   DATE NOT NULL,
  start_time  TIME(0) NOT NULL,
  end_time    TIME(0) NOT NULL,
  skill_level VARCHAR(12) NOT NULL CONSTRAINT df_plays_skill DEFAULT 'all',
  courts_used TINYINT  NOT NULL CONSTRAINT df_plays_courts DEFAULT 1,
  max_slots   SMALLINT NOT NULL,
  price       DECIMAL(10,2) NOT NULL,           -- price per slot
  CONSTRAINT pk_open_plays PRIMARY KEY (id),
  CONSTRAINT fk_plays_venue FOREIGN KEY (venue_id) REFERENCES dbo.venues(id) ON DELETE CASCADE,
  CONSTRAINT fk_plays_sport FOREIGN KEY (sport_id) REFERENCES dbo.sports(id),
  CONSTRAINT ck_plays_skill CHECK (skill_level IN ('all','beginner','intermediate','advanced'))
);
CREATE INDEX idx_play_date ON dbo.open_plays (play_date, start_time);

-- A player joins an open play for 1..n slots. "slots left" is never stored;
-- it is computed: max_slots - SUM(slots of joined rows).
CREATE TABLE dbo.open_play_registrations (
  id           INT IDENTITY(1,1) NOT NULL,
  open_play_id INT NOT NULL,
  user_id      INT NOT NULL,
  slots        TINYINT NOT NULL CONSTRAINT df_reg_slots DEFAULT 1,
  amount       DECIMAL(10,2) NOT NULL,
  status       VARCHAR(10) NOT NULL CONSTRAINT df_reg_status DEFAULT 'joined',
  created_at   DATETIME2(0) NOT NULL CONSTRAINT df_reg_created DEFAULT SYSDATETIME(),
  CONSTRAINT pk_open_play_registrations PRIMARY KEY (id),
  CONSTRAINT fk_reg_play FOREIGN KEY (open_play_id) REFERENCES dbo.open_plays(id) ON DELETE CASCADE,
  CONSTRAINT fk_reg_user FOREIGN KEY (user_id)      REFERENCES dbo.users(id),
  CONSTRAINT uq_play_user UNIQUE (open_play_id, user_id),
  CONSTRAINT ck_reg_status CHECK (status IN ('joined','cancelled'))
);
GO

-- 4) Demo data (this whole block must run as ONE batch because it uses variables)
DECLARE @today DATE = CAST(GETDATE() AS DATE);
-- bcrypt hash of the word "password"
DECLARE @pw NVARCHAR(255) = N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
-- bcrypt hash of "admin12345"
DECLARE @adminPw NVARCHAR(255) = N'$2y$10$wOH2EjtG8evvyeoDacsh4O0g4eb4GH.O9YtvGF5FInj9/hrIbWTpu';

INSERT INTO dbo.sports (name) VALUES (N'Pickleball');

INSERT INTO dbo.users (name, email, password_hash, role, skill_level) VALUES
 (N'John M. Reyes',  N'john@example.com',     @pw,      'player',  'intermediate'),
 (N'Maria Santos',   N'maria@example.com',    @pw,      'player',  'beginner'),
 (N'Paolo Cruz',     N'paolo@example.com',    @pw,      'player',  'advanced'),
 (N'Alab Admin',     N'admin@example.com',    @adminPw, 'manager', 'advanced'),
 (N'Pickle Admin',   N'pickle@example.com',   @pw,      'manager', 'advanced'),
 (N'Playzone Admin', N'playzone@example.com', @pw,      'manager', 'advanced');

INSERT INTO dbo.user_sports (user_id, sport_id) VALUES (1,1),(2,1),(3,1);

-- NCHAR(241) is the letter n with a tilde, so this file stays plain ASCII
INSERT INTO dbo.venues (manager_id, name, address, city, description, opening_hour, closing_hour) VALUES
 (4, N'ALAB Court',      N'Open Canal, Imus',     N'Imus, Cavite', N'Indoor pickleball courts with LED lighting, lockers and a small pro shop.', 6, 22),
 (5, N'Play and Pickle', N'Anabu Road, Imus',     N'Imus, Cavite', N'Friendly neighbourhood pickleball courts. Paddle rentals available.', 6, 21),
 (6, N'Playzone',        N'Aguinaldo Highway, Dasmari' + NCHAR(241) + N'as', N'Dasmari' + NCHAR(241) + N'as, Cavite', N'Covered pickleball club courts with parking and a courtside cafe.', 7, 23);

INSERT INTO dbo.courts (venue_id, sport_id, name, hourly_rate) VALUES
 (1,1,N'ALAB Court 1',350),(1,1,N'ALAB Court 2',350),(1,1,N'ALAB Court 3',350),
 (2,1,N'Pickle Court A',300),(2,1,N'Pickle Court B',300),(2,1,N'Pickle Court C',300),
 (3,1,N'Playzone Pickle 1',320),(3,1,N'Playzone Pickle 2',320),(3,1,N'Playzone Pickle 3',350);

INSERT INTO dbo.open_plays (venue_id, sport_id, title, description, play_date, start_time, end_time, skill_level, courts_used, max_slots, price) VALUES
 (1,1,N'ALAB Early Morning',        N'Rotating pickleball doubles, balls provided.', DATEADD(DAY,1,@today),'07:00','12:00','all',3,24,250),
 (2,1,N'Sunrise Open Play',         N'Easy-going pickleball games for everyone.',   DATEADD(DAY,1,@today),'05:00','09:00','all',3,20,200),
 (3,1,N'Dink and Smash Morning',    N'Mixed-level rotating pickleball games.',      DATEADD(DAY,2,@today),'06:00','10:00','all',3,18,200),
 (1,1,N'Intermediate Night Ladder', N'Competitive pickleball ladder play.',         DATEADD(DAY,3,@today),'19:00','22:00','intermediate',2,12,300),
 (2,1,N'Beginner Dink Social',      N'Welcoming beginner pickleball doubles.',      DATEADD(DAY,4,@today),'17:00','20:00','beginner',1,8,150);

INSERT INTO dbo.open_play_registrations (open_play_id, user_id, slots, amount) VALUES
 (1,1,1,250),(1,2,2,500),(2,1,1,200),(3,3,1,200);

-- One past booking so the "Past activity" tab has a receipt to show
INSERT INTO dbo.bookings (reference, user_id, court_id, booking_date, start_hour, end_hour, total_price, status)
 VALUES ('BK-DEMO0001', 1, 1, DATEADD(DAY,-3,@today), 18, 20, 700, 'confirmed');
INSERT INTO dbo.booking_slots (booking_id, court_id, slot_date, slot_hour) VALUES
 (1, 1, DATEADD(DAY,-3,@today), 18),
 (1, 1, DATEADD(DAY,-3,@today), 19);
GO

-- 5) Quick check: you should see 6 users, 3 venues, 9 courts, 5 open plays
SELECT (SELECT COUNT(*) FROM dbo.users)       AS users,
       (SELECT COUNT(*) FROM dbo.venues)      AS venues,
       (SELECT COUNT(*) FROM dbo.courts)      AS courts,
       (SELECT COUNT(*) FROM dbo.open_plays)  AS open_plays;
GO
