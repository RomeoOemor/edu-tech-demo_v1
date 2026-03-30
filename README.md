# Edu-Tech Student Portal (PHP + MySQL)

This project is converted to run on shared hosting environments such as InfinityFree.

## Stack

- Frontend: HTML, CSS, JavaScript
- Backend API: PHP (`api/index.php`)
- Database: MySQL

## API Endpoints

- `GET /api/health`
- `POST /api/auth/register`
- `POST /api/auth/login`
- `PUT /api/students/{studentId}`
- `POST /api/sick-leave`
- `GET /api/sick-leave/{studentId}`
- `GET /api/emergency-contacts`
- `POST /api/chat`

## Local Setup (PHP)

1. Create a MySQL database.
2. Run SQL scripts in this order:
	1. `database/schema.sql`
	2. `database/seed.sql` (optional demo data)
3. Update DB credentials in `config/database.php`.
4. Serve with PHP:

```bash
php -S localhost:8000
```

5. Open `http://localhost:8000`.

## InfinityFree Deployment

1. Create your MySQL database in InfinityFree control panel.
2. Import `database/schema.sql` using phpMyAdmin.
3. Edit `config/database.php` with your InfinityFree host, db name, user, and password.
4. Upload all project files to `htdocs`.
5. Ensure `.htaccess` is uploaded so `/api/*` routes map to `api/index.php`.
6. Test:
	- `https://your-domain/api/health`
	- Register and login from `login.php`

## Important Notes

- This repository now targets PHP + MySQL deployment.
- Student passwords are stored securely using PHP `password_hash` / `password_verify`.
