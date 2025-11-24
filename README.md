# CECOE News Monitoring System (PHP + Tailwind + Chart.js)

PHP version target: **8.2**
Tailwind: CDN (quick prototype)
Charts: Chart.js (CDN)
Password hashing: Bcrypt (cost 12)

## What is included
- Basic project scaffold (MVC-like)
- Authentication (login/logout, sessions)
- Superadmin and user roles
- Monitor ID generator: `CCSPM01` ...
- CRUD endpoints for regions, event_types, sub_event_types, actions, users
- Activity logs with pagination (default 15)
- Dashboard with Chart.js donuts (region & user)
- Dependent dropdown example (event → sub-event)
- SQL schema (`sql/init.sql`) — import manually (you said you'll import)

## How to run (development)
1. Import `sql/init.sql` into your MySQL/MariaDB database.
2. Update `includes/config.php` DB credentials.
3. Serve the `public/` folder via your webserver (Apache/Nginx) or PHP built-in:
   ```bash
   php -S localhost:8000 -t public
   ```
4. Visit `http://localhost:8000/` and login with the seeded superadmin from the SQL dump.

## Notes
- Tailwind is included via CDN for a quick start. For production, compile Tailwind.
- File uploads are disabled per your choice.
- You asked NOT to auto-import SQL; use phpMyAdmin or `mysql` CLI to import `sql/init.sql`.

