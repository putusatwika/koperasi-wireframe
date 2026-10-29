# Koperasi

Laravel application with a Filament administration panel, role-based access control, and modular cooperative-management features.

## Requirements

- PHP 8.4 or newer
- Composer
- Node.js and npm
- Docker Desktop with Docker Compose

## Installation

### MySQL with Docker (recommended)

1. Start MySQL and Adminer:

   ```bash
   docker compose up -d mysql adminer
   ```

2. Install the PHP dependencies and create the environment file:

   ```bash
   composer install
   cp .env.example .env
   ```

   On Windows PowerShell:

   ```powershell
   composer install
   Copy-Item .env.example .env
   ```

3. Update the database section in `.env`:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=33060
   DB_DATABASE=koperasi
   DB_USERNAME=koperasi_user
   DB_PASSWORD=password
   ```

   `DB_HOST=127.0.0.1` is required because Laravel is running on the host computer. If Laravel is later moved into a Docker container, change it to `DB_HOST=mysql`.

4. Generate the application key and run the database setup:

   ```bash
   php artisan key:generate
   php artisan migrate
   php artisan db:seed
   ```

5. Install and build the frontend:

   ```bash
   npm install
   npm run build
   ```

6. Start the application:

   ```bash
   composer run dev
   ```

Check the container status with:

```bash
docker compose ps
```

### Automatic setup after `.env` configuration

After configuring `.env` for MySQL, this command installs dependencies, generates the application key, runs migrations, and builds frontend assets:

```bash
composer setup
```

After setup, seed the default roles, permissions, module statuses, and super administrator:

```bash
php artisan db:seed
```

### Manual setup without Docker

If MySQL is already installed on the host:

1. Install the PHP dependencies:

   ```bash
   composer install
   ```

2. Create the environment file:

   ```bash
   cp .env.example .env
   ```

   On Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Generate the application key:

   ```bash
   php artisan key:generate
   ```

4. Configure the MySQL connection in `.env`:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=koperasi
   DB_USERNAME=root
   DB_PASSWORD=your-mysql-password
   ```

5. Run the migrations:

   ```bash
   php artisan migrate
   ```

6. Seed the application:

   ```bash
   php artisan db:seed
   ```

7. Build the frontend assets:

   ```bash
   npm install
   npm run build
   ```

## Run the application

```bash
composer run dev
```

- Application: `http://localhost:8000`
- User panel: `http://localhost:8000/`

### Default super administrator

The default `SuperAdminSeeder` creates this account for local development:

- Email: `super@koperasi.id`
- Password: `password`

Open `http://localhost:8000/` and sign in with these credentials. Change the password immediately in any shared, staging, or production environment. Do not use this default password in production.

These credentials are for local development only.

## Adminer

When using Docker MySQL, open `http://localhost:8081`:

- System: MySQL
- Server: `mysql`
- Username: `koperasi_user`
- Password: `password`
- Database: `koperasi`

Stop the Docker services when finished:

```bash
docker compose down
```

## Testing

```bash
composer test
```

## Useful commands

```bash
npm run build
```

```bash
npm run dev
```

```bash
php artisan serve
```

```bash
php artisan migrate:fresh
php artisan db:seed
```

> `migrate:fresh` deletes all data in the configured database.

## Troubleshooting

If frontend assets are missing, run:

```bash
npm install
npm run build
```

If MySQL refuses the connection, check the containers:

```bash
docker compose ps
```
