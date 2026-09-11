# CareerHub

CareerHub is a full-stack recruitment and job management platform built with **Vue 3, Laravel, and MySQL**.

The project is being developed as a practical full-stack application focused on real-world concepts such as REST APIs, database design, CRUD operations, authentication, authorization, job applications, and responsive frontend development.

## Tech Stack

### Frontend
- Vue 3
- Vue Router
- Axios
- Tailwind CSS

### Backend
- Laravel 12
- PHP
- Laravel Sanctum

### Database
- MySQL 8.4

### Development
- Git
- GitHub
- Vite

## Current Features

- Browse job listings
- View individual job details
- Responsive CareerHub interface
- Job creation and editing forms
- Vue frontend connected to Laravel REST API
- Laravel connected to MySQL
- Job listings stored in a relational database

## Current Architecture

```text
Browser
   ↓
Vue 3 Frontend
   ↓ Axios
Laravel REST API
   ↓ Eloquent
MySQL Database
```

## Project Structure

```text
CareerHub/
├── frontend/       # Vue 3 application
└── backend/        # Laravel REST API
```

## API

Currently implemented:

```text
GET /api/jobs
GET /api/jobs/{id}
```

Planned CRUD endpoints:

```text
POST   /api/jobs
PUT    /api/jobs/{id}
DELETE /api/jobs/{id}
```

## Roadmap

CareerHub will gradually include:

- Full job CRUD
- User authentication
- Job seeker and company accounts
- Role-based authorization
- Company profiles
- Job applications
- Saved jobs
- Search and filtering
- Pagination
- CV uploads
- Employer and applicant dashboards

## Development Setup

### Backend

```bash
cd backend
composer install
php artisan serve
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

The frontend runs on:

```text
http://localhost:3000
```

The Laravel API runs on:

```text
http://127.0.0.1:8000
```

---

CareerHub is currently under active development.
