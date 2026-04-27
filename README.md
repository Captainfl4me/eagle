# Eagle - Personal Budget Management Application

Eagle is a Laravel 13 personal budget management web application.

## Table of Contents

- [Overview](#overview)
- [Technology Stack](#technology-stack)
- [Features Implemented](#features-implemented)
- [Installation](#installation)
- [Usage](#usage)
- [Testing](#testing)
- [Project Structure](#project-structure)
- [License](#license)

## Overview

Eagle is a web application designed to help users manage their personal budgets. The application allows users to:
- Create accounts and log in securely
- Manage their profile (view username, change password, log out)
- [Future: Create and manage budgets with envelope budgeting system]

## Technology Stack

- **Backend**: Laravel 13 (PHP)
- **Database**: SQLite (default), MySQL or PostgreSQL configurable
- **Frontend**: Blade templates with Tailwind CSS 4 via Vite
- **Testing**: PHPUnit for unit and feature tests
- **Build Tools**: Composer (PHP), npm/Vite (JavaScript/assets)

## Features Implemented

### Authentication System ✅
- User registration with username and password validation
- Secure login/logout functionality
- Password confirmation and strength validation
- Username uniqueness enforcement
- Session-based authentication

### Profile Management ✅
- View username and account information
- Change password with current password verification
- Secure password update form
- Logout access from profile page and main navigation

### Navigation & UI ✅
- Responsive design with Tailwind CSS
- Clear navigation between login, register, and profile pages
- "Back to Home" links in authentication views
- Dynamic header showing login/register for guests and profile/logout for authenticated users
- Consistent styling across all pages
- Budget listing page ✅
- Month‑level budgeting (create, edit, contiguous months, total amount with green/red coloring) ✅

### Testing ✅
- Comprehensive unit tests for all authentication functionality:
  - Registration validation and user creation (6 tests)
  - Login authentication and session handling (5 tests)
  - Profile viewing and password update (7 tests)
- Total: 20 passing unit tests (41 assertions)
- Tests use SQLite in-memory database for isolation
- Automatic cache clearing before test runs

### Technical Implementation ✅
- Custom users table schema using username as primary identifier (instead of Laravel's default name/email)
- Proper validation rules for all forms
- Secure password hashing using Laravel's Hash facade
- Middleware protection for authenticated routes
- Factory-based test data generation
- Database migrations with proper rollback support

## Installation

### Prerequisites
- PHP 8.3+
- Composer
- Node.js & npm (for asset compilation)
- SQLite (or MySQL/PostgreSQL if preferred)

### Setup Instructions

1. Clone the repository
```bash
git clone <repository-url>
cd eagle
```

2. Install PHP dependencies
```bash
composer install
```

3. Install JavaScript dependencies
```bash
npm install
```

4. Copy environment configuration
```bash
cp .env.example .env
```

5. Generate application key
```bash
php artisan key:generate
```

6. Configure database (SQLite by default in .env)
   - For SQLite: No additional setup needed (uses database/database.sqlite)
   - For MySQL/PostgreSQL: Update .env with database credentials

7. Run database migrations
```bash
php artisan migrate
```

8. Compile frontend assets
```bash
# For development
npm run dev

# For production
npm run build
```

## Usage

### Development Server
```bash
# Start all services (PHP server, queue, logs, Vite)
composer dev
```
Or separately:
```bash
# PHP development server
php artisan serve

# Vite development server (in another terminal)
npm run dev
```

### Production Build
```bash
# Build frontend assets for production
npm run build

# Start Laravel server (use proper web server like Nginx/Apache in production)
php artisan serve
```

### Default Accounts
No default accounts are created. Users must register first through the registration page.

## Testing

Run all tests:
```bash
composer test
# or directly
php artisan test
```

Test suite includes:
- Unit tests for registration, login, and profile functionality
- Tests use SQLite in-memory database for isolation
- Automatic config cache clearing before tests

## Project Structure

```
eagle/
├── app/                    # Application core
│   ├── Http/               # Controllers and middleware
│   │   ├── Controllers/    # Authentication controllers (Login, Register, Profile)
│   │   └── Middleware/
│   ├── Models/             # Eloquent models (User)
│   └── Providers/
├── database/
│   ├── migrations/         # Database schema (users table with username)
│   ├── factories/          # Model factories for testing
│   └── seeders/
├── public/                 # Compiled assets
├── resources/
│   ├── views/              # Blade templates
│   │   ├── auth/           # Login and register views
│   │   ├── profile.blade.php
│   │   └── welcome.blade.php
│   ├── css/                # Tailwind CSS
│   └── js/                 # Application JavaScript
├── routes/                 # Route definitions
│   └── web.php             # Authentication routes
├── tests/
│   └── Unit/               # Unit tests
│       ├── LoginTest.php
│       ├── RegisterTest.php
│       └── ProfileTest.php
├── .env                    # Environment configuration
├── composer.json           # PHP dependencies
├── package.json            # JavaScript dependencies
├── vite.config.js          # Vite configuration
└── phpunit.xml             # PHPUnit configuration
```

## Features Roadmap (Planned)

Based on the original SPECS.md, the following features are planned for future implementation:

- [x] Budget creation and management
- [ ] Envelope budgeting system with monthly updates
- [ ] Budget sharing between users
- [ ] Dashboard with financial summaries and alerts
- [ ] Detailed budget and month views
- [ ] Negative budget protection and borrowing system

## License

This project is open source and available under the [MIT License](LICENSE).

## Acknowledgments

- Built with Laravel 13
- Frontend styling with Tailwind CSS 4
- Asset bundling with Vite
- Testing with PHPUnit