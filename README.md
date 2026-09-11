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

The project focuses on practical concepts such as frontend/backend integration, REST API development, relational database design, CRUD operations, authentication, authorization, validation, search, filtering, pagination, and maintainable application structure.

---

## 📸 Preview

Screenshots will be added as the frontend develops further.

<!--

Later you can add:

### Job Listings

![CareerHub Job Listings](docs/screenshots/job-listings.png)

### Job Details

![CareerHub Job Details](docs/screenshots/job-details.png)

-->

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

The application currently includes:

- Browse available job listings
- View individual job details
- Job creation and editing forms
- Vue frontend connected to Laravel
- REST API communication using Axios
- Laravel connected to MySQL
- Job data stored in a relational database
- Basic responsive frontend interface

---

## 🚧 In Progress

The following features are currently being developed:

- Complete job CRUD operations
- Authentication and authorization
- Search and filtering
- Pagination
- Improved validation
- Improved loading and error states

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
| `GET` | `/api/jobs` | Retrieve job listings |
| `GET` | `/api/jobs/{id}` | Retrieve a specific job |

### Planned Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/jobs` | Create a new job |
| `PUT` | `/api/jobs/{id}` | Update an existing job |
| `DELETE` | `/api/jobs/{id}` | Delete a job |

More endpoints will be introduced as authentication, companies, applications, saved jobs, and user profiles are added.

---

## 🗄 Database

CareerHub uses **MySQL** as its relational database.

Laravel's **Eloquent ORM** is used to interact with the database.

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

Additional relationships will be introduced as the application develops.

---

## 🗺 Roadmap

### Phase 1 — Project Foundation

- [x] Create Vue frontend
- [x] Create Laravel backend
- [x] Configure MySQL
- [x] Connect Laravel to MySQL
- [x] Connect Vue to Laravel
- [x] Display job listings
- [x] Display individual job details

### Phase 2 — Job Management

- [ ] Complete Create Job
- [ ] Complete Edit Job
- [ ] Delete jobs
- [ ] Backend validation
- [ ] Frontend validation

### Phase 3 — Authentication

- [ ] User registration
- [ ] User login
- [ ] Logout
- [ ] Protected frontend routes
- [ ] Protected API routes

### Phase 4 — User Roles

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

- [ ] Search
- [ ] Filtering
- [ ] Sorting
- [ ] Pagination
- [ ] Loading states
- [ ] Error states
- [ ] Improved responsive design

### Phase 7 — Advanced Features

- [ ] CV uploads
- [ ] Applicant dashboard
- [ ] Employer dashboard
- [ ] Email notifications

---

## ⚙️ Development Setup

### Requirements

Make sure the following tools are installed:

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

Create the environment file.

#### Windows PowerShell

```powershell
Copy-Item .env.example .env
```

#### Linux / macOS

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

---

### 3. Configure MySQL

Create the CareerHub database:

```sql
CREATE DATABASE careerhub;
```

Then update:

```text
backend/.env
```

Example configuration:

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

```bash
php artisan migrate
```

If seeders are available:

```bash
php artisan db:seed
```

---

### 5. Start Laravel

```bash
php artisan serve
```

The backend should normally run at:

```text
http://127.0.0.1:8000
```

---

### 6. Frontend Setup

Open another terminal and move into the frontend:

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

Vite will display the frontend URL in the terminal.

It will usually be:

```text
http://localhost:5173
```

---

## 🎯 What I'm Learning

CareerHub is a practical learning project focused on applying software engineering concepts through a complete full-stack application.

While developing the project, I am gaining experience with:

- Vue component architecture
- Vue Router
- API communication with Axios
- Laravel controllers and routes
- REST API design
- Database migrations
- Eloquent models and relationships
- MySQL relational database design
- CRUD operations
- Authentication and authorization
- Form validation
- Error handling
- Git workflows
- Organizing larger software projects

The goal is not only to make the application work, but also to understand how a maintainable full-stack system is designed, developed, and improved over time.

---

## 📈 Project Status

🚧 **CareerHub is currently under active development.**

The application is being built progressively as new full-stack development and software engineering concepts are implemented.

The README will continue to be updated as features are completed.
