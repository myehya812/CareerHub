# CareerHub

CareerHub is a full-stack job platform built with Vue 3 and Laravel 12.

The platform allows job seekers to discover jobs, apply to positions, manage their professional profiles, upload resumes, and track applications. Companies can create and manage job listings, review applicants, update application statuses, view candidate profiles, and securely download applicant resumes.

This project is also being built as a practical full-stack learning project. The main goal is not only to finish the application, but to understand how the frontend, backend, authentication, authorization, database, file storage, APIs, and application architecture work together.

---

## Current Project Status

CareerHub is currently complete through **Phase 7 — Profiles & Resume Management**.

### Completed

- Project foundation
- Job CRUD
- Authentication
- User roles
- Authorization
- Job applications
- Application status management
- Job search
- Job filtering
- Job sorting
- Pagination
- Job seeker profiles
- Company profiles
- Resume upload
- Resume replacement
- Resume download
- Resume deletion
- Private resume storage
- Public user profiles
- Applicant profile viewing
- Secure recruiter resume access

### Coming Next

- Saved jobs
- Job seeker dashboard
- Company dashboard
- Laravel Policies
- Form Requests
- Improved validation
- Automated testing
- Responsive improvements
- Deployment

---

# Tech Stack

## Frontend

- Vue 3
- Vue Router
- Pinia
- Axios
- Tailwind CSS
- Vite
- Vue Toastification
- Vue Spinner

## Backend

- Laravel 12
- PHP
- Laravel Sanctum
- Eloquent ORM
- REST API

## Database

- MySQL

---

# Authentication

CareerHub uses Laravel Sanctum SPA authentication.

Authentication is handled using cookies and sessions instead of storing authentication tokens in localStorage.

Protected API routes use:

```php
auth:sanctum

The authenticated user is retrieved on the backend using:

$request->user()

This means ownership information is not trusted from the frontend.

User Roles

CareerHub currently supports two account roles:

job_seeker
company
Job Seeker

Job seekers can:

Browse jobs
Search jobs
Filter jobs
Sort jobs
View job details
Apply to jobs
Prevent duplicate applications
View their applications
Track application status
Create a professional profile
Add a headline
Add a bio
Add a location
Add skills
Add experience
Upload a PDF resume
Replace their resume
Download their resume
Delete their resume
View other CareerHub profiles
Company

Companies can:

Create job listings
Edit their own jobs
Delete their own jobs
View applicants
Accept applications
Reject applications
View candidate profiles
Download applicant resumes
Create a company profile
Add company information
Add a website
Job Management

Companies can create and manage job listings.

A company can only edit or delete jobs that belong to its authenticated account.

Job listings currently contain fields such as:

Title
Description
Location
Job type
Minimum salary
Maximum salary
Currency
Status

Ownership is checked on the Laravel backend.

The frontend never decides whether a company owns a job.

Job Discovery

CareerHub includes a job discovery system with:

Search by title
Filter by location
Filter by job type
Sort by newest
Sort by oldest
Sort by lowest salary
Sort by highest salary
Pagination

Example request:

GET /api/jobs?search=Laravel&location=Beirut&type=Full-Time&sort=salary_high&page=1

Laravel builds the database query based on the query parameters and returns paginated results.

The frontend receives values such as:

data
current_page
last_page
total

and uses them to render the job list and pagination controls.

Applications

Job seekers can apply to available jobs.

Each application connects:

User
  ↓
Application
  ↓
JobListing

Application statuses currently include:

pending
accepted
rejected

Duplicate applications are prevented using both application logic and a database uniqueness constraint.

Companies can only manage applications belonging to jobs they own.

Profiles

Each user can have one profile.

Relationship:

User
  ↓ hasOne
Profile

And:

Profile
  ↓ belongsTo
User

Common profile fields include:

Bio
Location

Job seeker profile fields include:

Headline
Skills
Experience
Resume

Company profile fields include:

Company name
Website

The backend determines which fields are allowed based on the authenticated user's role.

Role-Specific Profiles

The frontend displays different profile fields depending on the user role.

A job seeker sees:

Headline
Location
Bio
Skills
Experience
Resume

A company sees:

Location
Bio
Company Name
Website

The frontend controls what the user sees.

The backend controls what the user is actually allowed to update.

This means hiding a field in Vue is not treated as security.

Resume Management

Job seekers can manage their resume from their profile.

Supported actions:

Upload
Replace
Download
Delete

Resume validation currently requires:

PDF format
Maximum file size: 5 MB

The resume is sent from Vue using:

File
↓
FormData
↓
Axios
↓
Laravel

Laravel validates the uploaded file and stores it privately.

The database stores:

resume_path
resume_original_name

The internal storage path is hidden from API responses.

The original file name can still be displayed to the user.

Private Resume Storage

Resumes are not exposed directly through a public storage URL.

Instead, Laravel serves them through authenticated API routes.

This makes it possible to perform authorization before returning the file.

The flow is:

Authenticated request
        ↓
Authorization
        ↓
Private storage
        ↓
PDF response
Secure Recruiter Resume Access

Companies can download the resume of an applicant only when that applicant applied to one of the company's own jobs.

Endpoint:

GET /api/applications/{id}/resume

Authorization flow:

Authenticated user
        ↓
Is the user a company?
        ↓
Find Application
        ↓
Find related JobListing
        ↓
Does the company own this job?
        ↓
Find applicant
        ↓
Find applicant Profile
        ↓
Does the profile have a resume?
        ↓
Does the physical PDF exist?
        ↓
Download allowed

A company cannot simply guess a user ID and download that user's resume.

The application establishes the relationship that grants permission.

Public Profiles

Authenticated CareerHub users can view other user profiles.

Endpoint:

GET /api/users/{id}/profile

Job seeker public profiles can display:

Name
Headline
Location
Bio
Skills
Experience
Resume availability

Company public profiles can display:

Account name
Location
Bio
Company name
Website

The public profile API explicitly selects which profile fields should be returned instead of exposing the entire database model.

Main API Routes
Authentication
POST   /api/register
POST   /api/login
POST   /api/logout
GET    /api/user
Jobs
GET     /api/jobs
GET     /api/jobs/{id}
POST    /api/jobs
PUT     /api/jobs/{id}
DELETE  /api/jobs/{id}
Job Seeker Applications
GET    /api/applications
POST   /api/jobs/{id}/applications
GET    /api/jobs/{id}/application-status
Company Applicant Management
GET     /api/jobs/{id}/applications
PATCH   /api/applications/{id}/status
GET     /api/applications/{id}/resume
Profile
GET    /api/profile
PATCH  /api/profile
Resume
POST    /api/profile/resume
GET     /api/profile/resume
DELETE  /api/profile/resume
Public Profiles
GET /api/users/{id}/profile
Main Database Relationships
User
├── hasMany JobListings
├── hasMany Applications
└── hasOne Profile
JobListing
├── belongsTo User
└── hasMany Applications
Application
├── belongsTo User
└── belongsTo JobListing
Profile
└── belongsTo User
Full Request Flow

A typical CareerHub request follows this structure:

User action
    ↓
Vue component
    ↓
Frontend function
    ↓
Axios
    ↓
Laravel API route
    ↓
Authentication middleware
    ↓
Validation
    ↓
Authorization
    ↓
Controller
    ↓
Eloquent model / relationships
    ↓
MySQL
    ↓
JSON or file response
    ↓
Axios
    ↓
Vue state
    ↓
UI rerender

Different types of request data are used for different purposes.

Example:

/api/jobs/5

The 5 is a route parameter identifying a resource.

Example:

?search=Laravel&location=Beirut

These are query parameters used for searching and filtering.

Example:

{
  "status": "accepted"
}

This is request-body data used to modify a resource.

The authenticated actor comes from:

$request->user()
Project Structure
CareerHub/
│
├── backend/
│   │
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       └── Api/
│   │   │
│   │   └── Models/
│   │
│   ├── database/
│   │   └── migrations/
│   │
│   └── routes/
│       └── api.php
│
└── frontend/
    │
    └── src/
        ├── components/
        ├── router/
        ├── stores/
        └── views/
Important Frontend Views

Current important Vue views include:

JobsView.vue
JobView.vue
MyApplicationsView.vue
ApplicantsView.vue
ProfileView.vue
PublicProfileView.vue

Important reusable components include:

JobListings.vue
JobListing.vue
Navbar.vue
Security Decisions

CareerHub currently includes several important security rules.

Authentication

Protected routes use Laravel Sanctum.

Ownership

The backend derives ownership from:

$request->user()

The frontend does not provide trusted ownership IDs.

Job Ownership

Companies can only modify jobs belonging to their account.

Application Authorization

Companies can only manage applications for jobs they own.

Role Authorization

Job seekers cannot perform company-only actions.

Companies cannot perform job-seeker-only actions.

Resume Privacy

Resume files are stored privately.

Internal file paths are not exposed to the frontend.

Recruiter Resume Access

Companies must own the job connected to an application before downloading that applicant's resume.

Running CareerHub
Requirements

Install:

PHP
Composer
Node.js
npm
MySQL
Backend Setup

Enter the backend directory:

cd backend

Install dependencies:

composer install

Create the environment file:

cp .env.example .env

Generate an application key:

php artisan key:generate

Configure the MySQL database inside .env.

Run migrations:

php artisan migrate

Start Laravel:

php artisan serve

Backend default URL:

http://127.0.0.1:8000
Frontend Setup

Open another terminal:

cd frontend

Install dependencies:

npm install

Start Vite:

npm run dev

Frontend URL:

http://localhost:3000
Production Build

Build the frontend using:

cd frontend
npm run build

The current Phase 7 frontend successfully builds using Vite.

Development Roadmap
Phase 1  — Foundation                         ✅
Phase 2  — Job CRUD                           ✅
Phase 3  — Authentication                     ✅
Phase 4  — Roles & Authorization              ✅
Phase 5  — Applications                       ✅
Phase 6  — Job Discovery                      ✅
Phase 7  — Profiles & Resume Management       ✅

Phase 8  — Saved Jobs                         ⏳
Phase 9  — Dashboards                         ⏳
Phase 10 — Production Quality                 ⏳
Phase 11 — Deployment                         ⏳
Phase 7 Completed Features

Phase 7 introduced the profile system.

Completed work includes:

Profile database and relationships
        ↓
Own profile API
        ↓
Role-specific profile fields
        ↓
Job seeker profiles
        ↓
Company profiles
        ↓
Private resume storage
        ↓
Resume upload
        ↓
Resume replacement
        ↓
Resume download
        ↓
Resume deletion
        ↓
Public profiles
        ↓
Applicant profile navigation
        ↓
Authorized recruiter resume downloads
Learning Goals

CareerHub is being developed as a practical software-engineering project.

The project is being used to understand:

HTML and frontend structure
Vue components
Vue reactivity
Vue Router
Pinia
Axios
REST APIs
HTTP methods
Request parameters
Query parameters
Request bodies
Authentication
Authorization
Laravel controllers
Laravel routing
Validation
Eloquent ORM
Database migrations
Database relationships
File uploads
FormData
Private file storage
Binary file responses
Browser Blobs
Error handling
Role-based interfaces
Git workflows
Full-stack debugging
Application architecture

The goal is not simply to finish CareerHub.

The goal is to understand the project well enough to eventually design and build another full-stack application independently.

Current Learning Progress

The project now includes several complete end-to-end flows.

Example:

Company clicks Download CV
        ↓
Vue receives application object
        ↓
application.id
        ↓
Axios GET request
        ↓
Laravel API route
        ↓
Sanctum authentication
        ↓
ApplicationController
        ↓
Company role check
        ↓
Application relationship
        ↓
Job ownership check
        ↓
Applicant relationship
        ↓
Profile relationship
        ↓
Private resume storage
        ↓
PDF response
        ↓
Axios Blob
        ↓
Browser download

Understanding flows like this is one of the main goals of the project.

Future Improvements

Planned improvements include:

Saved jobs
Job seeker dashboard
Company dashboard
Better reusable Vue components
Laravel Policies
Form Request validation
Automated backend tests
Frontend testing
Better validation messages
Better error pages
Responsive improvements
Loading and empty states
Improved accessibility
Deployment
Production configuration
License

CareerHub is currently being developed for learning and portfolio purposes.