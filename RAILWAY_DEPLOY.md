# Railway Deploy (Trial)

This adds Docker support for Railway without changing how you run the app locally on XAMPP.

## What This Does

- Uses the PHP built-in server inside a Docker container.
- Loads configuration from Railway environment variables (no `.env` is required in the container).
- Keeps your local `.env` and XAMPP setup unchanged.

## Steps (Railway)

1. Push the project to GitHub (make sure `.env` is not pushed — it is already in `.gitignore`).
2. Railway → New Project → Deploy from GitHub Repo.
3. Railway will detect the `Dockerfile` automatically.
4. Add a database on Railway (MySQL or Postgres).
5. Set environment variables in Railway:

### Required

- `APP_ENV=production`
- `APP_DEBUG=false`

### Base URL (Recommended)

- `APP_BASE_URL=https://YOUR-RAILWAY-DOMAIN`

### Database (MySQL example)

- `DB_DRIVER=mysql`
- `DB_HOST=...`
- `DB_PORT=3306`
- `DB_NAME=...`
- `DB_USER=...`
- `DB_PASS=...`

### Database (Postgres example)

- `DB_DRIVER=pgsql`
- `DB_HOST=...`
- `DB_PORT=5432`
- `DB_NAME=...`
- `DB_USER=...`
- `DB_PASS=...`
- `DB_SCHEMA=public`
- `DB_SSLMODE=require`

## Notes

- File uploads in `public/uploads` are not persistent on Railway by default. For a trial, this is fine.
- If you want persistent uploads later, use object storage or a volume.

