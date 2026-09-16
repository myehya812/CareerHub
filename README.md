# CareerHub

CareerHub is a full-stack recruitment platform built with Vue.js, Laravel, and MySQL.

The project is designed to simulate a real-world job platform where job seekers can discover opportunities and companies can create and manage job listings.

It is being developed as a portfolio project to practice full-stack development, REST APIs, authentication, authorization, database relationships, and modern frontend architecture.

## Current Status

CareerHub is currently under active development.

Implemented so far:

- Job listing CRUD
- User registration and login
- Session-based authentication with Laravel Sanctum
- Job Seeker and Company roles
- Protected frontend routes
- Company-only job creation
- Job ownership
- Owner-only job editing
- Owner-only job deletion
- Backend authorization checks
- Persistent authentication after page refresh
- Role-aware navigation
- Laravel API integration with Vue
- MySQL database integration

## Tech Stack

### Frontend

- Vue 3
- Vue Router
- Pinia
- Axios
- Tailwind CSS
- Vite

### Backend

- PHP
- Laravel 12
- Laravel Sanctum
- Eloquent ORM
- REST API

### Database

- MySQL 8

### Development Tools

- Git
- GitHub
- VS Code
- Composer
- npm

## Application Roles

CareerHub currently supports two user roles.

### Job Seeker

Job seekers can:

- Register and log in
- Browse available jobs
- View individual job listings

Additional job seeker features are planned as development continues.

### Company

Companies can:

- Register and log in
- Create job listings
- Edit their own job listings
- Delete their own job listings

Companies cannot modify jobs created by another company.

Authorization is enforced by the Laravel backend rather than relying only on frontend visibility.

## Authentication and Authorization

CareerHub uses Laravel Sanctum for SPA authentication.

The authentication flow uses Laravel sessions and cookies rather than storing authentication tokens manually in the browser.

The project currently applies three levels of access control:

1. Authentication  
   Determines whether a user is logged in.

2. Role authorization  
   Determines whether the user is a Job Seeker or Company.

3. Ownership authorization  
   Determines whether a company owns a specific job listing before allowing it to update or delete that job.

Example:

```text
Company A creates Job #5
        |
        v
job_listings.user_id = Company A ID
        |
        v
Company A can edit/delete Job #5

Company B
        |
        v
Attempts to modify Job #5
        |
        v
403 Forbidden
```

## Project Structure

```text
CareerHub/
|
|-- backend/
|   |-- app/
|   |   |-- Http/Controllers/
|   |   |-- Models/
|   |
|   |-- database/
|   |   |-- migrations/
|   |
|   |-- routes/
|       |-- api.php
|
|-- frontend/
|   |-- src/
|       |-- components/
|       |-- router/
|       |-- stores/
|       |-- views/
|
|-- README.md
```

The frontend and backend are kept separate so that Vue communicates with Laravel through HTTP API requests.


## Development Phases

CareerHub is being developed incrementally, with each phase introducing a new part of the full-stack architecture.

### Phase 1 — Project Foundation
**Completed**

- Set up Vue 3 frontend
- Set up Laravel backend
- Configure MySQL database
- Connect Vue and Laravel through REST API requests
- Configure Git and GitHub
- Establish frontend/backend project structure

---

### Phase 2 — Job Listing System
**Completed**

- Create the `job_listings` database table
- Build the JobListing model
- Display jobs from Laravel in Vue
- View individual job listings
- Create new job listings
- Edit existing job listings
- Delete job listings
- Connect Vue forms to Laravel CRUD endpoints

---

### Phase 3 — Authentication
**Completed**

- User registration
- User login
- User logout
- Laravel Sanctum SPA authentication
- Session-based authentication
- Restore authentication state after page refresh
- Pinia authentication store
- Protected Vue routes

---

### Phase 4 — Roles and Job Ownership
**Completed**

- Add `job_seeker` and `company` roles
- Role selection during registration
- Role-aware frontend navigation
- Company-only job creation
- Connect job listings to their creator
- User → JobListing Eloquent relationship
- Owner-only job editing
- Owner-only job deletion
- Backend ownership authorization
- Prevent companies from modifying another company's jobs

---

### Phase 5 — Job Applications
**Next**

- Create the applications database table
- Connect applications to users and jobs
- Allow job seekers to apply for jobs
- Prevent duplicate applications
- Allow job seekers to view their applications
- Allow companies to view applicants for their jobs
- Application status management

---

### Phase 6 — Job Discovery

- Search jobs
- Filter jobs
- Sort job results
- Pagination
- Improve job browsing experience

---

### Phase 7 — User and Company Profiles

- Job seeker profiles
- Company profiles
- Profile editing
- CV/resume uploads
- Company information on job listings

---

### Phase 8 — Dashboards

- Job seeker dashboard
- Company dashboard
- Application statistics
- Job management overview
- Application tracking

---

### Phase 9 — Production Quality

- Laravel Policies
- Improved validation
- Better error handling
- Loading and empty states
- Responsive UI improvements
- Refactoring
- Automated testing

---

### Phase 10 — Deployment

- Production configuration
- Deploy frontend
- Deploy backend
- Deploy database
- Environment and security configuration
- Final documentation
- Portfolio screenshots

## Current Data Model

The main entities currently implemented are:

```text
User
 |
 | hasMany
 v
JobListing
```

Each company-owned job stores the ID of the user that created it through:

```text
job_listings.user_id
```

Laravel Eloquent relationships are used to connect users and their job listings.

## API Overview

### Public Routes

```http
POST /api/register
POST /api/login

GET /api/jobs
GET /api/jobs/{id}
```

### Authenticated Routes

```http
GET  /api/user
POST /api/logout
```

### Company Job Management

```http
POST   /api/jobs
PUT    /api/jobs/{id}
DELETE /api/jobs/{id}
```

Job creation, modification, and deletion are protected by backend authentication and authorization.

## Local Setup

### 1. Clone the repository

```bash
git clone https://github.com/myehya812/CareerHub.git
cd CareerHub
```

### 2. Backend setup

Move into the Laravel project:

```bash
cd backend
```

Install PHP dependencies:

```bash
composer install
```

Create your local environment file:

```bash
copy .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Create a MySQL database named:

```text
careerhub
```

Configure the database connection inside `.env`.

Example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=careerhub
DB_USERNAME=your_mysql_user
DB_PASSWORD=your_mysql_password
```

Run the migrations:

```bash
php artisan migrate
```

Start Laravel:

```bash
php artisan serve
```

The backend will normally run at:

```text
http://127.0.0.1:8000
```

### 3. Frontend setup

Open another terminal:

```bash
cd frontend
```

Install dependencies:

```bash
npm install
```

Start the Vue development server:

```bash
npm run dev
```

The frontend will normally run at:

```text
http://localhost:3000
```

## Development Roadmap

Planned features include:

- Job applications
- Saved jobs
- Company profiles
- Job seeker profiles
- CV upload
- Search
- Filtering
- Sorting
- Pagination
- Application tracking
- Company dashboard
- Job seeker dashboard
- Notifications
- Improved validation
- Laravel Policies
- Automated testing
- Responsive UI improvements
- Deployment

## Main Learning Goals

CareerHub is being built to practice the workflow used in real full-stack applications, including:

- Designing REST APIs
- Connecting a Vue frontend to a Laravel backend
- Managing application state with Pinia
- Working with MySQL relational databases
- Using Eloquent ORM relationships
- Authentication and sessions
- Role-based authorization
- Resource ownership
- Protected routes
- Form handling
- HTTP status codes
- Error handling
- Git and GitHub workflows
- Full-stack debugging

## Project Direction

The goal is to gradually develop CareerHub into a complete recruitment platform while keeping the architecture understandable and maintainable.

Features are being added incrementally rather than generating the full system at once, allowing each part of the frontend, backend, and database architecture to be understood and tested independently.
