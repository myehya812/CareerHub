# CareerHub

A full-stack recruitment and job management platform built with **Vue 3, Laravel 12, MySQL, and RESTful APIs**.

![Vue](https://img.shields.io/badge/Vue-3-42b883?logo=vuedotjs&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-CSS-38B2AC?logo=tailwindcss&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?logo=javascript&logoColor=black)

> **Project Status**
>
> CareerHub is currently under active development.  
> The core job management, authentication, roles, and ownership systems are complete.

---

## About CareerHub

CareerHub is a full-stack recruitment platform designed to simulate a real-world job management application.

The project allows two types of users to interact with the platform:

- **Job Seekers**
- **Companies**

Companies can create and manage their own job listings, while job seekers can browse available opportunities.

The project is being developed incrementally to practice practical full-stack software development concepts including:

- Frontend/backend communication
- REST API design
- Relational database design
- CRUD operations
- Authentication
- Authorization
- Resource ownership
- State management
- Protected routes
- Validation
- Error handling
- Git and GitHub workflows

---

# Tech Stack

## Frontend

- Vue 3
- JavaScript
- Vue Router
- Pinia
- Axios
- Tailwind CSS
- Vite

## Backend

- PHP
- Laravel 12
- Laravel Sanctum
- Eloquent ORM
- RESTful APIs

## Database

- MySQL 8

## Development Tools

- Git
- GitHub
- Visual Studio Code
- Composer
- npm

---

# Implemented Features

CareerHub currently includes:

- Browse available job listings
- View individual job details
- Create job listings
- Edit job listings
- Delete job listings
- User registration
- User login
- User logout
- Session-based authentication
- Laravel Sanctum authentication
- Persistent authentication after page refresh
- Job Seeker and Company roles
- Protected frontend routes
- Protected API routes
- Role-aware navigation
- Company-only job creation
- Job ownership
- Owner-only job editing
- Owner-only job deletion
- Backend authorization checks
- Vue + Laravel API integration
- MySQL database integration
- Pinia authentication state management

---

# User Roles

CareerHub currently supports two account types.

## Job Seeker

Job seekers can currently:

- Create an account
- Log in
- Log out
- Browse jobs
- View individual job listings

Future phases will allow job seekers to:

- Apply to jobs
- Track applications
- Save jobs
- Create a profile
- Upload a CV

## Company

Companies can currently:

- Create an account
- Log in
- Create job listings
- Edit their own jobs
- Delete their own jobs

A company cannot modify a job created by another company.

---

# Authentication & Authorization

CareerHub uses **Laravel Sanctum** for SPA authentication.

Authentication is handled through Laravel sessions and cookies.

The application currently uses three layers of access control.

### Authentication

Determines whether the user is logged in.

```text
Guest
  |
  v
Login
  |
  v
Authenticated User
```

### Role Authorization

Determines which type of account is logged in.

```text
User
 |
 +---- Job Seeker
 |
 +---- Company
```

### Ownership Authorization

A company can only modify jobs that belong to that company.

```text
Company A
   |
   v
Creates Job #5
   |
   v
job_listings.user_id = Company A ID
   |
   v
Company A can edit/delete Job #5
```

If another company tries to modify the same job:

```text
Company B
   |
   v
PUT /api/jobs/5
   |
   v
403 Forbidden
```

The Laravel backend performs the final authorization checks.

---

# Current Database Relationships

The main relationship currently implemented is:

```text
User
 |
 | hasMany
 v
JobListing
```

And:

```text
JobListing
 |
 | belongsTo
 v
User
```

Each company-owned job stores its creator through:

```text
job_listings.user_id
```

Older jobs may temporarily contain:

```text
user_id = NULL
```

because job ownership was introduced after the first job records were created.

More relationships will be introduced as development continues.

---

# API Overview

## Public Authentication Routes

```http
POST /api/register
POST /api/login
```

## Public Job Routes

```http
GET /api/jobs
GET /api/jobs/{id}
```

## Authenticated Routes

```http
GET  /api/user
POST /api/logout
```

## Protected Job Management

```http
POST   /api/jobs
PUT    /api/jobs/{id}
DELETE /api/jobs/{id}
```

Job creation is restricted to company accounts.

Job updates and deletion also require the authenticated company to own the requested job.

---

# Project Structure

```text
CareerHub/
|
|-- backend/
|   |
|   |-- app/
|   |   |-- Http/
|   |   |   `-- Controllers/
|   |   |
|   |   `-- Models/
|   |
|   |-- database/
|   |   `-- migrations/
|   |
|   `-- routes/
|       `-- api.php
|
|-- frontend/
|   |
|   `-- src/
|       |-- components/
|       |-- router/
|       |-- stores/
|       `-- views/
|
`-- README.md
```

The frontend and backend are separated.

Vue communicates with Laravel using HTTP requests through Axios.

---

# Development Roadmap

## Phase 1 — Project Foundation

- [x] Create Vue frontend
- [x] Create Laravel backend
- [x] Configure MySQL
- [x] Connect Laravel to MySQL
- [x] Connect Vue frontend to Laravel API
- [x] Create Git repository
- [x] Connect project to GitHub
- [x] Establish frontend/backend project structure
- [x] Display job listings
- [x] Display individual job details

---

## Phase 2 — Job Management

- [x] Create `job_listings` database table
- [x] Create JobListing model
- [x] Build job listing API
- [x] Complete job creation
- [x] Complete job editing
- [x] Complete job deletion
- [x] Connect Vue forms to Laravel
- [x] Add backend validation
- [x] Handle API errors
- [x] Display loading states

---

## Phase 3 — Authentication

- [x] Install Laravel Sanctum
- [x] Configure SPA authentication
- [x] User registration
- [x] User login
- [x] User logout
- [x] Authentication Pinia store
- [x] Restore user session after page refresh
- [x] Protected frontend routes
- [x] Protected API routes
- [x] CSRF protection
- [x] Authentication-aware navigation

---

## Phase 4 — Roles & Authorization

- [x] Add user role column
- [x] Job Seeker accounts
- [x] Company accounts
- [x] Role selection during registration
- [x] Role-aware frontend interface
- [x] Restrict job creation to companies
- [x] Add `user_id` to job listings
- [x] User `hasMany` JobListing relationship
- [x] JobListing `belongsTo` User relationship
- [x] Automatically assign job ownership
- [x] Owner-only job editing
- [x] Owner-only job deletion
- [x] Reject unauthorized company modifications with `403 Forbidden`
- [ ] Replace repeated authorization checks with Laravel Policies

---

## Phase 5 — Job Applications

**Current next phase**

- [ ] Create `applications` database table
- [ ] Create Application model
- [ ] Link applications to job seekers
- [ ] Link applications to job listings
- [ ] Add Eloquent relationships
- [ ] Allow job seekers to apply
- [ ] Prevent companies from applying
- [ ] Prevent duplicate applications
- [ ] Allow job seekers to view their applications
- [ ] Allow companies to view applicants
- [ ] Add application statuses
- [ ] Allow companies to update application status
- [ ] Protect application routes with authorization

Planned relationship:

```text
Job Seeker
    |
    v
Application
    |
    v
Job Listing
    |
    v
Company
```

---

## Phase 6 — Job Discovery

- [ ] Job search
- [ ] Search by title
- [ ] Search by location
- [ ] Filter by job type
- [ ] Filter by salary
- [ ] Sorting
- [ ] Pagination
- [ ] Improved empty states
- [ ] Search result count

---

## Phase 7 — Profiles

### Job Seeker Profile

- [ ] Personal information
- [ ] Skills
- [ ] Experience
- [ ] Profile editing
- [ ] CV upload
- [ ] CV download

### Company Profile

- [ ] Company name
- [ ] Company description
- [ ] Location
- [ ] Website
- [ ] Company profile page
- [ ] Display company details on job listings

---

## Phase 8 — Saved Jobs

- [ ] Create saved jobs relationship
- [ ] Save a job
- [ ] Remove saved job
- [ ] View saved jobs
- [ ] Prevent duplicate saved jobs

---

## Phase 9 — Dashboards

### Job Seeker Dashboard

- [ ] Application overview
- [ ] Application statuses
- [ ] Saved jobs
- [ ] Profile completion

### Company Dashboard

- [ ] Published jobs
- [ ] Applicant counts
- [ ] Active jobs
- [ ] Closed jobs
- [ ] Application management
- [ ] Basic statistics

---

## Phase 10 — Production Quality

- [ ] Laravel Policies
- [ ] Form Request validation
- [ ] Improved frontend validation
- [ ] Improved API error handling
- [ ] Reusable frontend components
- [ ] Better loading states
- [ ] Better empty states
- [ ] Responsive UI improvements
- [ ] Backend refactoring
- [ ] Frontend refactoring
- [ ] Automated backend tests
- [ ] Frontend tests

---

## Phase 11 — Deployment

- [ ] Production environment configuration
- [ ] Deploy Vue frontend
- [ ] Deploy Laravel backend
- [ ] Deploy MySQL database
- [ ] Configure production environment variables
- [ ] Configure HTTPS
- [ ] Security review
- [ ] Add project screenshots
- [ ] Add live demo link
- [ ] Final README cleanup

---

# Current Progress

```text
Phase 1  Project Foundation       COMPLETE
Phase 2  Job Management          COMPLETE
Phase 3  Authentication          COMPLETE
Phase 4  Roles & Authorization   COMPLETE
Phase 5  Job Applications        NEXT
Phase 6  Job Discovery           PLANNED
Phase 7  Profiles                PLANNED
Phase 8  Saved Jobs              PLANNED
Phase 9  Dashboards              PLANNED
Phase 10 Production Quality      PLANNED
Phase 11 Deployment              PLANNED
```

The only remaining item intentionally left open in Phase 4 is migrating the current authorization logic to Laravel Policies.

That refactor can be introduced later after the core application features are implemented.

---

# Local Development Setup

## Requirements

Make sure the following are installed:

- PHP 8.2+
- Composer
- Node.js
- npm
- MySQL 8
- Git

---

## Clone the Repository

```bash
git clone https://github.com/myehya812/CareerHub.git
cd CareerHub
```

---

## Backend Setup

Enter the Laravel project:

```bash
cd backend
```

Install dependencies:

```bash
composer install
```

Create the environment file:

```bash
copy .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Create a MySQL database:

```text
careerhub
```

Configure the database inside `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=careerhub
DB_USERNAME=your_mysql_user
DB_PASSWORD=your_mysql_password
```

Run migrations:

```bash
php artisan migrate
```

Start Laravel:

```bash
php artisan serve
```

Default backend URL:

```text
http://127.0.0.1:8000
```

---

## Frontend Setup

Open another terminal and enter:

```bash
cd frontend
```

Install dependencies:

```bash
npm install
```

Run the development server:

```bash
npm run dev
```

Default frontend URL:

```text
http://localhost:3000
```

---

# Learning Goals

CareerHub is being developed as a practical full-stack software engineering project.

The main goals are to gain experience with:

- Building REST APIs
- Designing relational databases
- Vue component architecture
- Vue Router
- Pinia state management
- Axios
- Laravel controllers
- Eloquent models and relationships
- Database migrations
- Authentication
- Authorization
- Resource ownership
- HTTP status codes
- Validation
- Error handling
- Git workflows
- Debugging full-stack applications

The project is developed feature-by-feature so that each part of the architecture can be understood, implemented, tested, and improved before moving to the next phase.

---

# Project Status

CareerHub is currently under active development.

The foundation, job management system, authentication system, user roles, and job ownership system are complete.

The next major development milestone is:

**Phase 5 — Job Applications**