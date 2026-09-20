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
>
> **Phases 1–7 are complete. Phase 8 — Saved Jobs is next.**

---

## About CareerHub

CareerHub is a full-stack recruitment platform designed to simulate a real-world job management application.

The project supports two types of users:

- **Job Seekers**
- **Companies**

Companies can create and manage their own job listings, review applicants, update application statuses, manage a company profile, and securely download applicant CVs.

Job seekers can discover jobs, apply to positions, track their applications, manage a professional profile, and upload a private CV.

The project is being developed incrementally to practice practical full-stack software development concepts including:

- Frontend/backend communication
- REST API design
- Relational database design
- CRUD operations
- Authentication
- Authorization
- Resource ownership
- Eloquent relationships
- State management
- Protected routes
- Validation
- File uploads and private storage
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

- PHP 8.2+
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

### Jobs

- Browse available job listings
- View individual job details
- Create job listings
- Edit job listings
- Delete job listings
- Company-only job creation
- Job ownership
- Owner-only job editing
- Owner-only job deletion

### Authentication & Authorization

- User registration
- User login
- User logout
- Session-based authentication
- Laravel Sanctum SPA authentication
- CSRF protection
- Persistent authentication after page refresh
- Job Seeker and Company roles
- Protected frontend routes
- Protected API routes
- Role-aware navigation
- Backend role checks
- Backend ownership checks

### Applications

- Job seekers can apply to jobs
- Companies cannot apply to jobs
- Duplicate applications are prevented
- Job seekers can view their applications
- Companies can view applicants for their own jobs
- Application statuses: `pending`, `accepted`, `rejected`
- Companies can update application statuses
- Application routes are protected with role and ownership checks

### Job Discovery

- Search jobs by title
- Search by location
- Filter by job type
- Sort by newest
- Sort by oldest
- Sort by lowest salary
- Sort by highest salary
- Pagination
- Search result count
- Loading and empty states

### Profiles

- Job seeker profiles
- Company profiles
- Profile editing
- Role-specific profile fields
- Public profile pages for authenticated users
- Applicant profile links from company applicant views

### Resume Management

- PDF CV upload
- 5 MB upload limit
- Private file storage
- Resume replacement
- Resume download
- Resume deletion
- Original filename storage
- Internal storage path hidden from API responses
- Companies can download a candidate CV only through an application to a job they own

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
- Search and filter jobs
- Apply to jobs
- Track their applications
- View application statuses
- Create and edit a profile
- Add a headline
- Add a bio
- Add a location
- Add skills
- Add experience
- Upload a PDF CV
- Download their CV
- Replace their CV
- Delete their CV
- View public profiles

## Company

Companies can currently:

- Create an account
- Log in
- Log out
- Create job listings
- Edit their own jobs
- Delete their own jobs
- View applicants for their own jobs
- Accept applications
- Reject applications
- View applicant profiles
- Download applicant CVs when authorized
- Create and edit a company profile
- Add a company name
- Add a company description
- Add a location
- Add a website

A company cannot modify another company's jobs or access applicant CVs through jobs it does not own.

---

# Authentication & Authorization

CareerHub uses **Laravel Sanctum** for SPA authentication.

Authentication is handled through Laravel sessions and cookies rather than storing an authentication token in `localStorage`.

The application currently uses three main layers of access control.

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

The Laravel backend verifies whether the authenticated user owns or is allowed to access a resource.

Example for a company-owned job:

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

Resume access uses the same principle:

```text
Authenticated Company
        |
        v
Application
        |
        v
Job Listing
        |
        v
Does the company own this job?
        |
        +---- No  -> 403 Forbidden
        |
        +---- Yes -> Applicant Profile -> Private Resume
```

The Laravel backend performs the final authorization checks. Frontend route guards and hidden UI controls are used for user experience, not as the security boundary.

---

# Current Database Relationships

CareerHub currently uses these main Eloquent relationships:

```text
User
 |
 | hasMany
 v
JobListing
```

```text
JobListing
 |
 | belongsTo
 v
User
```

```text
User
 |
 | hasMany
 v
Application
```

```text
JobListing
 |
 | hasMany
 v
Application
```

```text
Application
 |                |
 | belongsTo      | belongsTo
 v                v
User           JobListing
```

```text
User
 |
 | hasOne
 v
Profile
```

```text
Profile
 |
 | belongsTo
 v
User
```

Important ownership fields include:

```text
job_listings.user_id
applications.user_id
applications.job_listing_id
profiles.user_id
```

Older development data may contain job records with `user_id = NULL` because job ownership was introduced after some early test records were created.

---

# API Overview

## Public Authentication Routes

```text
POST /api/register
POST /api/login
```

## Public Job Routes

```text
GET /api/jobs
GET /api/jobs/{id}
```

## Authenticated User Routes

```text
GET  /api/user
POST /api/logout
```

## Protected Job Management

```text
POST   /api/jobs
PUT    /api/jobs/{id}
DELETE /api/jobs/{id}
```

Job creation is restricted to company accounts.

Job updates and deletion require the authenticated company to own the requested job.

## Job Applications

```text
GET  /api/applications
POST /api/jobs/{id}/applications
GET  /api/jobs/{id}/application-status
```

## Company Applicant Management

```text
GET   /api/jobs/{id}/applications
PATCH /api/applications/{id}/status
GET   /api/applications/{id}/resume
```

## Profile Management

```text
GET   /api/profile
PATCH /api/profile
```

## Resume Management

```text
POST   /api/profile/resume
GET    /api/profile/resume
DELETE /api/profile/resume
```

## Public Profile

```text
GET /api/users/{id}/profile
```

The public-profile route is currently inside authenticated routes, so "public" means visible to logged-in CareerHub users rather than anonymous internet users.

---

# Request Flow

A typical CareerHub request follows this structure:

```text
User Action
    |
    v
Vue Component / View
    |
    v
Frontend Function
    |
    v
Axios Request
    |
    v
Laravel API Route
    |
    v
Authentication Middleware
    |
    v
Validation + Authorization
    |
    v
Controller
    |
    v
Eloquent Models / Relationships
    |
    v
MySQL / Private Storage
    |
    v
JSON or File Response
    |
    v
Axios
    |
    v
Vue State
    |
    v
UI Update
```

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
|   |   |       `-- Api/
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

## Phase 1 — Project Foundation — COMPLETE

- Create Vue frontend
- Create Laravel backend
- Configure MySQL
- Connect Laravel to MySQL
- Connect Vue frontend to Laravel API
- Create Git repository
- Connect project to GitHub
- Establish frontend/backend project structure
- Display job listings
- Display individual job details

---

## Phase 2 — Job Management — COMPLETE

- Create `job_listings` database table
- Create JobListing model
- Build job listing API
- Complete job creation
- Complete job editing
- Complete job deletion
- Connect Vue forms to Laravel
- Add backend validation
- Handle API errors
- Display loading states

---

## Phase 3 — Authentication — COMPLETE

- Install Laravel Sanctum
- Configure SPA authentication
- User registration
- User login
- User logout
- Authentication Pinia store
- Restore user session after page refresh
- Protected frontend routes
- Protected API routes
- CSRF protection
- Authentication-aware navigation

---

## Phase 4 — Roles & Authorization — COMPLETE

- Add user role column
- Job Seeker accounts
- Company accounts
- Role selection during registration
- Role-aware frontend interface
- Restrict job creation to companies
- Add `user_id` to job listings
- User `hasMany` JobListing relationship
- JobListing `belongsTo` User relationship
- Automatically assign job ownership
- Owner-only job editing
- Owner-only job deletion
- Reject unauthorized company modifications with `403 Forbidden`

**Deferred to Phase 10:** replace repeated manual authorization checks with Laravel Policies.

---

## Phase 5 — Job Applications — COMPLETE

- Create `applications` database table
- Create Application model
- Link applications to job seekers
- Link applications to job listings
- Add Eloquent relationships
- Allow job seekers to apply
- Prevent companies from applying
- Prevent duplicate applications
- Allow job seekers to view their applications
- Allow companies to view applicants
- Add application statuses
- Allow companies to update application status
- Protect application routes with authorization

Relationship:

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

## Phase 6 — Job Discovery — COMPLETE

- Job search
- Search by title
- Search by location
- Filter by job type
- Sorting
- Pagination
- Improved empty states
- Search result count

---

## Phase 7 — Profiles & Resume Management — COMPLETE

### Job Seeker Profile

- Headline
- Bio
- Location
- Skills
- Experience
- Profile editing
- CV upload
- CV download
- CV replacement
- CV deletion
- Private resume storage
- Public profile page

### Company Profile

- Company name
- Company description / bio
- Location
- Website
- Profile editing
- Public company profile page

### Recruiter Access

- Applicant names link to public profiles
- Companies can download applicant CVs
- CV access is authorized through the application and job owner
- Unauthorized companies receive `403 Forbidden`

---

## Phase 8 — Saved Jobs — NEXT

- Create saved-jobs relationship
- Save a job
- Remove a saved job
- View saved jobs
- Prevent duplicate saved jobs

---

## Phase 9 — Dashboards — PLANNED

### Job Seeker Dashboard

- Application overview
- Application statuses
- Saved jobs
- Profile completion

### Company Dashboard

- Published jobs
- Applicant counts
- Active jobs
- Closed jobs
- Application management
- Basic statistics

---

## Phase 10 — Production Quality — PLANNED

- Laravel Policies
- Form Request validation
- Improved frontend validation
- Improved API error handling
- Reusable frontend components
- Better loading states
- Better empty states
- Responsive UI improvements
- Backend refactoring
- Frontend refactoring
- Automated backend tests
- Frontend tests

---

## Phase 11 — Deployment — PLANNED

- Production environment configuration
- Deploy Vue frontend
- Deploy Laravel backend
- Deploy MySQL database
- Configure production environment variables
- Configure HTTPS
- Security review
- Add project screenshots
- Add live demo link
- Final README cleanup

---

# Current Progress

```text
Phase 1  Project Foundation          COMPLETE
Phase 2  Job Management             COMPLETE
Phase 3  Authentication             COMPLETE
Phase 4  Roles & Authorization      COMPLETE
Phase 5  Job Applications           COMPLETE
Phase 6  Job Discovery              COMPLETE
Phase 7  Profiles & Resumes         COMPLETE
Phase 8  Saved Jobs                 NEXT
Phase 9  Dashboards                 PLANNED
Phase 10 Production Quality         PLANNED
Phase 11 Deployment                 PLANNED
```

The main authorization refactor intentionally left open from Phase 4 is migrating repeated manual authorization checks to Laravel Policies. That work remains planned for Phase 10 after the core product features are complete.

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

# Production Build

The frontend can be compiled using:

```bash
cd frontend
npm run build
```

The current Phase 7 frontend builds successfully with Vite.

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
- Role-based interfaces
- Query parameters
- Pagination
- File uploads
- FormData
- Private file storage
- Browser Blob downloads
- HTTP status codes
- Validation
- Error handling
- Git workflows
- Debugging full-stack applications

The project is developed feature-by-feature so that each part of the architecture can be understood, implemented, tested, and improved before moving to the next phase.

The goal is not only to finish CareerHub, but to understand the architecture well enough to build future full-stack applications independently.

---

# Project Status

CareerHub is currently under active development.

The following major systems are now complete:

- Project foundation
- Job management
- Authentication
- Roles and ownership authorization
- Job applications
- Job discovery
- User profiles
- Resume management

The next development milestone is:

**Phase 8 — Saved Jobs**
