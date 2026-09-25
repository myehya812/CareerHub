# CareerHub

A full-stack recruitment and job management platform built with **Vue 3, Laravel 12, MySQL, and RESTful APIs**.

![Vue](https://img.shields.io/badge/Vue-3-42b883?logo=vuedotjs&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-CSS-38B2AC?logo=tailwindcss&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?logo=javascript&logoColor=black)

---

# Project Overview

CareerHub is a full-stack recruitment platform that connects companies with job seekers.

The platform provides a realistic hiring workflow where:

- Companies can publish and manage job opportunities
- Job seekers can discover jobs and apply
- Companies can review applicants and manage applications
- Users can create professional profiles and manage resumes


The project was built to practice real-world full-stack development concepts:

- Frontend and backend communication
- REST API architecture
- Authentication
- Authorization
- Role-based access control
- Database relationships
- CRUD operations
- File management
- State management
- Protected routes
- Validation
- Error handling

---

# Features

## Authentication & Authorization

- User registration
- User login
- User logout
- Laravel Sanctum authentication
- Session-based authentication
- CSRF protection
- Protected frontend routes
- Protected backend routes
- Role-based permissions

Supported roles:

```
User

├── Job Seeker

└── Company
```

---

# Job Management

Companies can:

- Create job listings
- Edit their own jobs
- Delete their own jobs
- View created jobs


Job listings include:

- Title
- Description
- Location
- Job type
- Salary range
- Currency
- Status


Ownership protection prevents companies from modifying jobs created by other users.

---

# Job Discovery

Job seekers can:

- Browse available jobs
- Search jobs
- Filter jobs
- View job details
- Navigate through paginated results


Implemented features:

- Search
- Filtering
- Sorting
- Pagination
- Loading states
- Empty states

---

# Applications System

Job seekers can:

- Apply to jobs
- View submitted applications
- Track application status


Companies can:

- View applicants for their jobs
- Review applications
- Update application status


Application statuses:

```
Pending
Accepted
Rejected
```

Security rules:

- Users cannot apply as companies
- Companies cannot access other companies' applicants
- Companies cannot modify unauthorized applications

---

# Saved Jobs

Job seekers can:

- Save jobs
- Remove saved jobs
- View saved jobs


Features:

- Prevent duplicate saves
- Dedicated saved jobs page
- Persistent saved jobs

---

# Profile System

## Job Seeker Profiles

Includes:

- Headline
- Bio
- Location
- Skills
- Experience
- Resume


## Company Profiles

Includes:

- Company name
- Website
- Location
- Company information


Users can:

- Update profiles
- View public profiles

---

# Resume Management

Implemented resume system:

- PDF upload
- Resume replacement
- Resume deletion
- Private file storage
- Secure resume downloads


Resume access rules:

- Job seekers manage their own resumes
- Companies can download resumes only from applicants of their own jobs

---

# Dashboards

## Job Seeker Dashboard

Provides:

- Total applications
- Saved jobs count
- Recent applications
- Recent saved jobs


## Company Dashboard

Provides:

- Total jobs created
- Total applications received
- Recent job listings
- Recent applicants

---

# Technology Stack

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
- RESTful API


## Database

- MySQL


## Tools

- Git
- GitHub
- Composer
- npm
- Visual Studio Code

---

# Database Design

Main relationships:


```
User
 |
 | hasMany
 |
 v

JobListing
```


```
User
 |
 | hasMany
 |
 v

Application
```


```
JobListing
 |
 | hasMany
 |
 v

Application
```


```
User
 |
 | hasOne
 |
 v

Profile
```


Important database connections:

```
job_listings.user_id

applications.user_id

applications.job_listing_id

profiles.user_id
```

---

# API Structure


## Authentication

```
POST /api/register

POST /api/login

POST /api/logout
```


## Jobs

```
GET    /api/jobs

GET    /api/jobs/{id}

POST   /api/jobs

PUT    /api/jobs/{id}

DELETE /api/jobs/{id}
```


## Applications

```
GET   /api/applications

POST  /api/jobs/{id}/applications

GET   /api/jobs/{id}/applications

PATCH /api/applications/{id}/status
```


## Profiles

```
GET   /api/profile

PATCH /api/profile

GET   /api/users/{id}/profile
```


## Resume

```
POST   /api/profile/resume

GET    /api/profile/resume

DELETE /api/profile/resume

GET    /api/applications/{id}/resume
```

---

# Project Structure


```
CareerHub/

│

├── backend/

│   ├── app/

│   ├── database/

│   └── routes/

│

├── frontend/

│   └── src/

│       ├── components/

│       ├── router/

│       ├── stores/

│       └── views/

│

└── README.md
```

---

# Development Roadmap


## Phase 1 — Project Foundation ✅

- Vue frontend setup
- Laravel backend setup
- Database connection
- Initial project structure


## Phase 2 — Job Management ✅

- Job CRUD operations
- Validation
- API integration


## Phase 3 — Authentication ✅

- Laravel Sanctum
- Login/register
- Authentication store
- Protected routes


## Phase 4 — Roles & Authorization ✅

- Job seeker role
- Company role
- Permission checks
- Ownership protection


## Phase 5 — Job Applications ✅

- Application workflow
- Applicant management
- Application statuses


## Phase 6 — Job Discovery ✅

- Search
- Filters
- Sorting
- Pagination


## Phase 7 — Profiles & Resume Management ✅

- User profiles
- Company profiles
- Resume upload
- Resume download
- Private storage


## Phase 8 — Saved Jobs ✅

- Save jobs
- Remove saved jobs
- Saved jobs page


## Phase 9 — Dashboards ✅

- Job seeker dashboard
- Company dashboard
- Statistics


## Phase 10 — Production Quality ✅

- API resources
- Frontend refactoring
- Improved error handling
- Cleaner architecture

---

# Current Progress


```
Phase 1   Project Foundation          COMPLETE
Phase 2   Job Management              COMPLETE
Phase 3   Authentication              COMPLETE
Phase 4   Roles & Authorization       COMPLETE
Phase 5   Job Applications            COMPLETE
Phase 6   Job Discovery               COMPLETE
Phase 7   Profiles & Resumes          COMPLETE
Phase 8   Saved Jobs                  COMPLETE
Phase 9   Dashboards                  COMPLETE
Phase 10  Production Quality          COMPLETE
```

---

# Installation


## Requirements

- PHP 8.2+
- Composer
- Node.js
- npm
- MySQL


---

# Backend Setup


```bash
cd backend

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate

php artisan serve
```


Backend runs at:

```
http://127.0.0.1:8000
```

---

# Frontend Setup


```bash
cd frontend

npm install

npm run dev
```


Frontend runs at:

```
http://localhost:3000
```

---

# Learning Outcomes

Through CareerHub, the project demonstrates practical experience with:

- Full-stack application architecture
- Vue component development
- State management with Pinia
- REST API development
- Laravel controllers
- Eloquent relationships
- Authentication systems
- Authorization logic
- Database design
- File uploads
- Secure storage
- Validation
- Git workflow

---

# Author

M Yehya