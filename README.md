# Tournament Management System

A Laravel-based web application for managing tournaments, participants, team rosters, seeding, match scheduling, and tournament results.

## Technology Stack

- **Backend:** PHP 8.5, Laravel 13
- **Frontend:** JavaScript, Alpine.js, Tailwind CSS
- **Build Tool:** Vite
- **Database:** SQLite
- **Dependency Management:** Composer and npm
- **Testing:** PHPUnit

## Prerequisites

Install the following before setting up the project:

- PHP 8.3 or later
- Composer
- Node.js and npm
- Git

## Installation and Setup

### 1. Clone the repository

```bash
git clone https://github.com/imanmeherinnisaaa/CSE470_Group9.git
cd CSE470_Group9
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure the environment

Create your environment file:

```bash
copy .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Set up the SQLite database

The project uses SQLite by default.

Make sure the following setting exists in your `.env` file:

```env
DB_CONNECTION=sqlite
```

Create the SQLite database file if it does not already exist:

```powershell
New-Item -ItemType File -Path database\database.sqlite -Force
```

Run the database migrations:

```bash
php artisan migrate
```

This creates the database tables required by the application.

### 5. Install frontend dependencies

```bash
npm install
```

Build the frontend assets:

```bash
npm run build
```

### 6. Start the application

```bash
php artisan serve
```

Open the application in your browser:

http://127.0.0.1:8000

## Running in Development Mode

For frontend development with Vite, open a separate terminal and run:

```bash
npm run dev
```

Keep the Vite development server running while working on frontend assets.

## Main Features

The system includes the following tournament management modules:

- **Tournament Configuration (F1):** Configure tournament details and competition settings.
- **Entry Management (F2):** Manage participant registration, eligibility, and waitlists.
- **Seeding Engine (F3):** Generate participant seeds using rating-based, random, manual, and teammate-grouping methods.
- **Bracket Generation (F4):** Generate tournament brackets.
- **Swiss Pairing Engine (F5):** Generate Swiss-system pairings while avoiding repeat matchups where possible and handling byes.
- **Scheduling and Availability (F6):** Manage tournament stations, time slots, and participant availability.
- **Result Reporting (F8):** Record and report match results.
- **Roster Management (F13):** Manage team rosters and substitutions.

## Running Tests

Run the automated test suite using:

```bash
php artisan test
```

To run the frontend production build:

```bash
npm run build
```

## Database Migrations

If database changes are introduced, apply the migrations using:

```bash
php artisan migrate
```

To check migration status:

```bash
php artisan migrate:status
```

## Project Structure

Important project directories:

```text
app/
  Http/Controllers/    Application controllers
  Models/              Eloquent models
  Services/            Business logic and services

database/
  migrations/          Database table migrations
  seeders/             Database seeders

resources/
  views/               Blade templates
  css/                 Stylesheets
  js/                  JavaScript files

routes/
  web.php              Web application routes

tests/
  Unit/                Unit tests
  Feature/              Feature tests
```

## Team Project

This application is developed as a group project for CSE470.

The system is developed collaboratively, with individual team members responsible for implementing different tournament management features and integrating them into the application.
