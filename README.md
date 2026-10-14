# CareerHub

**A full-stack recruitment platform with separate workflows for companies and job seekers.**

Built with Vue 3, Laravel 12, and MySQL to practice software engineering through a complete web application: authentication, authorization, REST APIs, relational data, file handling, and frontend state management.

![Vue 3](https://img.shields.io/badge/Vue-3-42b883?logo=vuedotjs&logoColor=white)
![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?logo=tailwindcss&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?logo=javascript&logoColor=black)

**Status:** Completed personal project.

[Features](#features) · [Screenshots](#screenshots) · [Architecture](#architecture) · [Run locally](#run-locally) · [API](#api-overview)

## Overview

CareerHub supports two user roles:

- **Companies** publish and manage jobs, review applicants, update application statuses, and access authorized applicant résumés.
- **Job seekers** discover opportunities, submit and track applications, save jobs, and maintain a profile and PDF résumé.

The project includes both the Vue interface and the Laravel backend.

## Screenshots



### Job discovery
<img width="2560" height="1440" alt="image" src="https://github.com/user-attachments/assets/c9102774-f04f-4e01-aa69-a6a41065669f" />


### Company dashboard
<img width="2560" height="1440" alt="image" src="https://github.com/user-attachments/assets/b0d0cf25-4381-4547-b462-ed631837976f" />



## Features

| Area | Implemented functionality |
| --- | --- |
| Authentication | Registration, login, logout, session-based authentication with Laravel Sanctum, and CSRF protection |
| Authorization | Company/job-seeker roles, protected API endpoints, frontend route guards, and job ownership checks |
| Job management | Create, view, edit, and delete job listings |
| Job discovery | Search by title, filter by location and job type, sort results, and browse paginated listings |
| Applications | Submit applications, check previous applications, view applicants, and manage pending/accepted/rejected statuses |
| Saved jobs | Save and remove jobs, view saved listings, and check saved status |
| Profiles | Update job-seeker and company information and view other user profiles while authenticated |
| Résumés | Upload, replace, delete, and download PDFs using private storage and access checks |
| Dashboards | Role-specific statistics, recent jobs, saved jobs, and applications |

### Access-control examples

- Only companies can create job listings.
- Companies can edit or delete only their own jobs.
- Only job seekers can submit applications.
- Duplicate applications are checked before creation.
- Companies can review applicants and download résumés only for their own jobs.
- Résumé uploads accept PDFs up to 5 MB.

Frontend route guards support navigation; backend authorization enforces access to application data.

## Architecture

| Layer | Technologies and responsibilities |
| --- | --- |
| Frontend | Vue 3, Vue Router, Pinia, Axios, Tailwind CSS, and Vite |
| Backend | PHP, Laravel 12, Sanctum, controllers, validation requests, API resources, and policies |
| Database | MySQL, Eloquent models, migrations, and model relationships |
| File storage | Private local storage for uploaded résumés |
| Development tools | Git, GitHub, Composer, and npm |

The Vue frontend sends requests to the Laravel API. During local development, Vite proxies `/api` and `/sanctum` requests to the backend at `http://127.0.0.1:8000`.

### Data model

| Relationship | Purpose |
| --- | --- |
| User → Job listings | Associate company users with the jobs they publish |
| User → Applications | Associate job seekers with submitted applications |
| Job listing → Applications | Associate each application with its target job |
| User → Profile | Store profile information and résumé metadata |

### Repository layout

| Path | Contents |
| --- | --- |
| `backend/app/Http/Controllers/Api/` | Authentication, jobs, applications, profiles, saved jobs, and dashboards |
| `backend/app/Http/Requests/` | Job creation and update validation |
| `backend/app/Http/Resources/` | API response representations |
| `backend/app/Models/` | Eloquent models and relationships |
| `backend/app/Policies/` | Job authorization policies |
| `backend/database/migrations/` | Database schema |
| `backend/routes/api.php` | API routes |
| `backend/tests/` | Current backend test suite |
| `frontend/src/components/` | Shared interface components |
| `frontend/src/views/` | Application pages |
| `frontend/src/stores/` | Pinia authentication state |
| `frontend/src/router/` | Routes and navigation guards |

## Run locally

### Requirements

- PHP 8.2 or newer, with Laravel's required extensions and MySQL support
- Composer
- Node.js and npm compatible with the Vite version in `frontend/package.json`
- A running MySQL server
- Git

### 1. Clone the repository

```bash
git clone https://github.com/myehya812/CareerHub.git
cd CareerHub
```

### 2. Configure the backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Create an empty MySQL database named `careerhub`. Then edit `backend/.env` to match your local database credentials:

```dotenv
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=careerhub
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_mysql_password

SESSION_DRIVER=database
SESSION_DOMAIN=null
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
```

The username and password above are placeholders. Replace them with your own local values.

Apply the migrations and start the backend:

```bash
php artisan config:clear
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Leave this terminal running.

### 3. Start the frontend

Open a second terminal at the repository root:

```bash
cd frontend
npm ci
npm run dev
```

Open **http://localhost:3000**. Keep using the same frontend hostname during a session so authentication cookies remain consistent.

The backend runs at **http://127.0.0.1:8000**. Check Vite's terminal output if port 3000 is already occupied.

### 4. Try the main workflow

1. Register a company account.
2. Sign in and create a job listing.
3. Log out and register a job-seeker account.
4. Browse the listing, save it, and submit an application.
5. Add a profile and upload a sample PDF résumé.
6. Sign in as the company to review the application and update its status.

Use separate browser profiles if you want to keep both accounts signed in simultaneously.

## API overview

Registration, login, job browsing, and job details are public. The remaining `/api` endpoints below require an authenticated session; role and ownership checks apply to protected actions.

| Area | Method | Endpoint |
| --- | --- | --- |
| Register | POST | `/api/register` |
| Login / logout | POST | `/api/login`, `/api/logout` |
| Current user | GET | `/api/user` |
| Browse jobs | GET | `/api/jobs` |
| Job details | GET | `/api/jobs/{id}` |
| Create a job | POST | `/api/jobs` |
| Update / delete a job | PUT / DELETE | `/api/jobs/{id}` |
| My applications | GET | `/api/applications` |
| Apply / view applicants | POST / GET | `/api/jobs/{id}/applications` |
| Check application | GET | `/api/jobs/{id}/application-status` |
| Update application status | PATCH | `/api/applications/{id}/status` |
| My profile | GET / PATCH | `/api/profile` |
| View another profile | GET | `/api/users/{id}/profile` |
| Manage my résumé | POST / GET / DELETE | `/api/profile/resume` |
| Download applicant résumé | GET | `/api/applications/{id}/resume` |
| Save / unsave a job | POST / DELETE | `/api/jobs/{id}/save` |
| Check saved status | GET | `/api/jobs/{id}/save-status` |
| Saved jobs | GET | `/api/saved-jobs` |
| Job-seeker dashboard | GET | `/api/dashboard/job-seeker` |
| Company dashboard | GET | `/api/dashboard/company` |

Job discovery accepts `search`, `location`, `type`, `sort`, and `page` query parameters. Sorting supports `newest`, `oldest`, `salary_low`, and `salary_high`.

## Development checks

Build the frontend:

```bash
cd frontend
npm run build
```

Run the backend tests:

```bash
cd backend
php artisan test
```

The current backend tests are Laravel starter examples. They do not yet validate the recruitment workflows or access-control behavior.

## What I learned

- Connecting a Vue application to a Laravel REST API
- Managing authentication state with Pinia
- Modeling relational data with MySQL and Eloquent
- Applying validation, authorization, and ownership checks
- Handling private file uploads and controlled downloads
- Structuring frontend routes, components, and backend responses

## Author

**Mohammad Hussein Yehya**

[GitHub](https://github.com/myehya812) · [LinkedIn](https://www.linkedin.com/in/mohammad-hussein-yehya)

