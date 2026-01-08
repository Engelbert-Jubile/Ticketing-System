Ticketing System (TICKORA)

Ticketing System is a web-based application for managing issue tickets, task assignments, and workflow status within a team or internal organization.

Status: In active development


OVERVIEW
--------

This project is built as a structured Laravel application with a modern frontend pipeline using Vite.
The system focuses on clarity, maintainability, and clean separation between core logic, tooling, and documentation.

Key goals:
- Centralized ticket management
- Clear task ownership and status tracking
- Maintainable backend architecture
- Clean, auditable Git history


TECH STACK
----------

Backend:
- PHP 8.x
- Laravel
- MySQL / SQLite (development)

Frontend:
- Vite
- JavaScript
- CSS (via resources/css)

Tooling:
- Composer
- NPM
- Vite
- PHPUnit


PROJECT STRUCTURE
-----------------

app/                Core application logic
bootstrap/
config/
database/
public/
resources/
routes/
tests/
tools/              Maintenance / utility scripts
docs/               Technical notes & design references


LOCAL DEVELOPMENT SETUP
-----------------------

Requirements:
- PHP >= 8.1
- Composer
- Node.js >= 18
- NPM

Installation steps:

1. Clone repository
   git clone https://github.com/Engelbert-Jubile/Ticketing-System.git
   cd ticketing-system

2. Install dependencies
   composer install
   npm install

3. Environment setup
   cp .env.example .env
   php artisan key:generate
   php artisan migrate

4. Run application
   npm run dev
   php artisan serve


ENVIRONMENT VARIABLES
---------------------

All sensitive configuration is stored in the .env file.
See .env.example for the required variables.


GIT WORKFLOW
------------

- main : stable branch
- dev  : active development
- Feature work is committed incrementally with clear commit messages


DOCUMENTATION
-------------

Additional technical notes and design references are available in the docs/ directory.


ROADMAP
-------

- Authentication & roles
- Ticket CRUD
- Status workflow
- Assignment & ownership
- Activity logging
- UI refinement


LICENSE
-------

This project is licensed under the MIT License.
