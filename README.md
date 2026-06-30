# Survey App — Local Setup

## 1. Create the environment file

```bash
cp .env.example .env
```

The defaults are already configured to talk to the bundled PostgreSQL container — no edits are required to run locally.

---

## 2. Build and start the containers

```bash
docker compose up -d --build
```

## 3. Create the admin account

```bash
docker compose exec app php artisan db:seed --force
```

This creates the admin login:

- **Email:** `admin@example.com`
- **Password:** `password`

---

## 4. Open the app

| URL | Page |
|-----|------|
| http://localhost:8000 | Application |
| http://localhost:8000/login | Admin login |

Log in at `/login` with the credentials above to reach the admin panel.

---

## Everyday commands

```bash
docker compose up -d        # start
docker compose stop                      # stop (keeps data)
docker compose down                      # stop & remove containers (keeps data)
docker compose down -v                   # stop & wipe the database

# Logs
docker compose logs -f app               # application logs
docker compose logs -f queue             # background worker logs

# Run artisan / composer inside the container
docker compose exec app php artisan <command>
docker compose exec app composer <command>

# Reset the database from scratch (and recreate the admin user)
docker compose exec app php artisan migrate:fresh --seed
```
