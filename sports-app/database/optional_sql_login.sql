-- OPTIONAL: only needed if you do NOT want Windows Authentication.
-- Run this in SSMS AFTER schema_sqlserver.sql, then put the same name/password
-- in config/config.php (DB_USER / DB_PASS).
-- Requires "SQL Server and Windows Authentication mode" (Mixed Mode) to be enabled
-- on the server (see README, step 5B).
USE master;
GO
IF NOT EXISTS (SELECT 1 FROM sys.sql_logins WHERE name = N'sports_app')
    CREATE LOGIN sports_app WITH PASSWORD = N'ChangeMe_12345!', CHECK_POLICY = OFF;
GO
USE sports_hub;
GO
IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name = N'sports_app')
    CREATE USER sports_app FOR LOGIN sports_app;
ALTER ROLE db_datareader ADD MEMBER sports_app;   -- SELECT
ALTER ROLE db_datawriter ADD MEMBER sports_app;   -- INSERT / UPDATE / DELETE
GO
