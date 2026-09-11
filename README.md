# CareerHub

> A full-stack recruitment and job management platform built with Vue 3, Laravel, MySQL, and RESTful APIs.

![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-06B6D4?logo=tailwindcss&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8-777BB4?logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?logo=javascript&logoColor=black)

> [!NOTE]
> CareerHub is currently under active development. New features are being added progressively as the project grows.

---

## 📌 About CareerHub

**CareerHub** is a full-stack recruitment platform designed to simulate a real-world job management application.

I am building the project to strengthen my understanding of full-stack development and software engineering by connecting a modern frontend application to a backend REST API and relational database.

The application uses **Vue 3** for the frontend, **Laravel 12** for the backend, and **MySQL** for data storage.

CareerHub focuses on practical software development concepts including REST API development, frontend/backend integration, relational database design, CRUD operations, validation, authentication, authorization, search, filtering, pagination, and maintainable application structure.

---

## 🛠 Tech Stack

### Frontend

- Vue 3
- JavaScript
- Vue Router
- Axios
- Tailwind CSS
- Vite

### Backend

- Laravel 12
- PHP
- RESTful APIs
- Eloquent ORM

### Database

- MySQL

### Development Tools

- Git
- GitHub
- Visual Studio Code

---

## ✅ Implemented Features

CareerHub currently includes:

- Browse available job listings
- View individual job details
- Job creation and editing forms
- Vue frontend connected to Laravel
- REST API communication using Axios
- Laravel connected to MySQL
- Relational job data storage
- Basic responsive user interface

---

## 🚧 In Progress

The following functionality is currently being developed:

- Complete job CRUD operations
- Authentication and authorization
- Search and filtering
- Pagination
- Form validation
- Loading states
- Error handling
- Improved responsive design

---

## 🏗 Architecture

CareerHub follows a separated frontend/backend architecture.

```text
┌─────────────────────────┐
│       Vue 3 App         │
│       Frontend          │
└────────────┬────────────┘
             │
             │ Axios / HTTP
             │ REST API
             ▼
┌─────────────────────────┐
│     Laravel Backend     │
│        REST API         │
└────────────┬────────────┘
             │
             │ Eloquent ORM
             ▼
┌─────────────────────────┐
│         MySQL           │
│        Database         │
└─────────────────────────┘
```

The **Vue frontend** handles the user interface and client-side interactions.

The **Laravel backend** handles API requests, application logic, validation, and database communication.

**MySQL** stores the application's persistent relational data.

---

## 📁 Project Structure

```text
CareerHub/
│
├── frontend/
│   └── Vue 3 application
│
├── backend/
│   └── Laravel REST API
│
└── README.md
```

### Frontend

```text
frontend/
├── src/
│   ├── components/
│   ├── views/
│   ├── router/
│   ├── assets/
│   └── App.vue
│
├── package.json
└── vite.config.js
```

### Backend

```text
backend/
├── app/
├── bootstrap/
├── config/
├── database/
├── routes/
├── resources/
├── tests/
└── artisan
```

---

## 🔌 REST API

The Vue frontend communicates with Laravel through RESTful API endpoints.

### Implemented Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/jobs` | Retrieve available job listings |
| `GET` | `/api/jobs/{id}` | Retrieve a specific job |

### Planned Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/jobs` | Create a new job |
| `PUT` | `/api/jobs/{id}` | Update an existing job |
| `DELETE` | `/api/jobs/{id}` | Delete a job |

Additional endpoints will be added for authentication, companies, applications, saved jobs, and user profiles as development continues.

---

## 🗄 Database

CareerHub uses **MySQL** as its relational database.

Laravel's **Eloquent ORM** is used to interact with the database and manage relationships between application entities.

The database is being designed around entities such as:

```text
Users
 │
 ├── Applications
 │
 └── Saved Jobs

Companies
 │
 └── Jobs
      │
      └── Applications
```

Additional tables and relationships will be introduced as the application develops.

---

## 🗺 Roadmap

### Phase 1 — Project Foundation

- [x] Create Vue frontend
- [x] Create Laravel backend
- [x] Configure MySQL
- [x] Connect Laravel to MySQL
- [x] Connect Vue frontend to Laravel API
- [x] Display job listings
- [x] Display individual job details

### Phase 2 — Job Management

- [ ] Complete job creation
- [ ] Complete job editing
- [ ] Delete jobs
- [ ] Backend validation
- [ ] Frontend validation

### Phase 3 — Authentication

- [ ] User registration
- [ ] User login
- [ ] Logout
- [ ] Protected frontend routes
- [ ] Protected API routes

### Phase 4 — Roles & Authorization

- [ ] Applicant accounts
- [ ] Employer accounts
- [ ] Role-based authorization
- [ ] Laravel authorization policies

### Phase 5 — Companies & Applications

- [ ] Company profiles
- [ ] Employer job management
- [ ] Apply for jobs
- [ ] View submitted applications
- [ ] Employer application management
- [ ] Application status tracking
- [ ] Saved jobs

### Phase 6 — Search & User Experience

- [ ] Search jobs
- [ ] Filter jobs
- [ ] Sort jobs
- [ ] Pagination
- [ ] Loading states
- [ ] Error states
- [ ] Improved responsive design

### Phase 7 — Advanced Features

- [ ] CV / résumé uploads
- [ ] Applicant dashboard
- [ ] Employer dashboard
- [ ] Email notifications

---

## ⚙️ Development Setup

### Requirements

Before running CareerHub locally, make sure the following are installed:

- Git
- Node.js
- npm
- PHP
- Composer
- MySQL

---

### 1. Clone the Repository

```bash
git clone https://github.com/myehya812/CareerHub.git
cd CareerHub
```

---

### 2. Backend Setup

Move into the Laravel backend:

```bash
cd backend
```

Install PHP dependencies:

```bash
composer install
```

Create the Laravel environment file.

#### Windows PowerShell

```powershell
Copy-Item .env.example .env
```

#### Linux / macOS

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

---

### 3. Configure MySQL

Create a database for CareerHub:

```sql
CREATE DATABASE careerhub;
```

Then open:

```text
backend/.env
```

Configure the database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=careerhub
DB_USERNAME=root
DB_PASSWORD=
```

Use the correct MySQL username and password for your environment.

---

### 4. Run Database Migrations

From the `backend` directory:

```bash
php artisan migrate
```

If database seeders are available:

```bash
php artisan db:seed
```

---

### 5. Start the Laravel Backend

```bash
php artisan serve
```

The Laravel backend will normally run at:

```text
http://127.0.0.1:8000
```

---

### 6. Start the Vue Frontend

Open another terminal.

Move into the frontend directory:

```bash
cd frontend
```

Install JavaScript dependencies:

```bash
npm install
```

Start the development server:

```bash
npm run dev
```

Vite will display the frontend URL in the terminal.

It will usually be:

```text
http://localhost:5173
```

---

## 📸 Screenshots

Screenshots will be added once the frontend interface is more complete.

<!--

When ready, create:

docs/
└── screenshots/
    ├── job-listings.png
    ├── job-details.png
    └── job-form.png

Then replace this section with:

### Job Listings

![CareerHub Job Listings](docs/screenshots/job-listings.png)

### Job Details

![CareerHub Job Details](docs/screenshots/job-details.png)

### Job Form

![CareerHub Job Form](docs/screenshots/job-form.png)

-->

---

## 📈 Project Status

🚧 **CareerHub is currently under active development.**

The application is being built progressively as additional full-stack development and software engineering concepts are implemented.

Features, architecture, and documentation will continue to evolve as development progresses.
