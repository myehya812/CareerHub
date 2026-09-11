# CareerHub

> A full-stack recruitment and job management platform built with Vue.js, Laravel, MySQL, and RESTful APIs.

![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-06B6D4?logo=tailwindcss&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8-777BB4?logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?logo=javascript&logoColor=black)

> [!NOTE]
> CareerHub is currently under active development. Features and architecture will continue to evolve as the project progresses.

---

## 📌 About the Project

**CareerHub** is a full-stack recruitment platform designed to simulate a real-world job management system.

I am building this project to strengthen my understanding of software engineering and full-stack development by connecting a modern frontend application to a REST API and relational database.

The project currently uses **Vue.js** for the frontend, **Laravel** for the backend, and **MySQL** for persistent data storage.

Through CareerHub, I am practicing concepts such as:

- Frontend and backend integration
- RESTful API development
- Relational database design
- CRUD operations
- Form handling and validation
- Authentication and authorization
- Search, filtering, and pagination
- Reusable frontend components
- Software architecture
- Git and version control

---

## 📸 Preview

Screenshots of the application will be added as the user interface develops further.

<!--
When screenshots are ready, create:

docs/screenshots/job-listings.png
docs/screenshots/job-details.png

Then replace the text above with:

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

### Database

- MySQL

### Development Tools

- Git
- GitHub
- Visual Studio Code

---

## ✅ Implemented Features

The following functionality is currently implemented:

- Browse available job listings
- View individual job details
- Job creation and editing forms
- Vue frontend connected to the Laravel backend
- REST API communication using Axios
- Laravel connected to MySQL
- Job data stored in a relational database
- Basic responsive frontend interface

---

## 🚧 In Progress / Planned Features

CareerHub will gradually include:

- [ ] Complete job CRUD operations
- [ ] User registration and login
- [ ] Authentication
- [ ] Role-based authorization
- [ ] Applicant accounts
- [ ] Employer accounts
- [ ] Company profiles
- [ ] Job applications
- [ ] Saved jobs
- [ ] Search functionality
- [ ] Job filtering
- [ ] Sorting
- [ ] Pagination
- [ ] CV / résumé uploads
- [ ] Applicant dashboard
- [ ] Employer dashboard
- [ ] Improved form validation
- [ ] Improved loading and error states

---

## 🏗 Application Architecture

CareerHub follows a separated frontend/backend architecture.

```text
┌──────────────────────┐
│      Vue.js App      │
│      Frontend        │
└──────────┬───────────┘
           │
           │ HTTP / REST API
           │ Axios
           ▼
┌──────────────────────┐
│    Laravel Backend   │
│     REST API         │
└──────────┬───────────┘
           │
           │ Eloquent ORM
           ▼
┌──────────────────────┐
│        MySQL         │
│       Database       │
└──────────────────────┘
```

The Vue frontend is responsible for the user interface and client-side interactions.

Laravel handles API requests, application logic, validation, and communication with the database.

MySQL stores the application's persistent relational data.

---

## 📁 Project Structure

```text
CareerHub/
│
├── frontend/
│   └── Vue.js application
│
├── backend/
│   └── Laravel REST API
│
└── README.md
```

### Frontend

The frontend contains the Vue application, including:

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

The backend contains the Laravel application, including:

```text
backend/
├── app/
├── config/
├── database/
├── routes/
├── resources/
├── tests/
└── artisan
```

---

## 🔌 REST API

The frontend communicates with Laravel through RESTful API endpoints.

### Currently Implemented

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/jobs` | Retrieve available jobs |
| `GET` | `/api/jobs/{id}` | Retrieve a specific job |

### Planned

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/jobs` | Create a new job |
| `PUT` | `/api/jobs/{id}` | Update a job |
| `DELETE` | `/api/jobs/{id}` | Delete a job |

Additional endpoints for authentication, companies, applications, saved jobs, and user profiles will be added as development continues.

---

## 🗄 Database

CareerHub uses **MySQL** as its relational database.

Laravel's **Eloquent ORM** is used to communicate with the database.

The database will eventually contain relationships between entities such as:

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

As the project grows, additional tables and relationships will be introduced.

---

## 🗺 Roadmap

### Phase 1 — Foundation

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
- [ ] Protected API endpoints

### Phase 4 — Roles & Companies

- [ ] Applicant role
- [ ] Employer role
- [ ] Authorization policies
- [ ] Company profiles
- [ ] Employer job management

### Phase 5 — Applications

- [ ] Apply for jobs
- [ ] View submitted applications
- [ ] Employer application management
- [ ] Application status tracking
- [ ] Saved jobs

### Phase 6 — User Experience

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
- [ ] Additional application improvements

---

## ⚙️ Development Setup

### Prerequisites

Before running CareerHub locally, make sure you have installed:

- Git
- Node.js
- npm
- PHP
- Composer
- MySQL

---

## 1. Clone the Repository

```bash
git clone https://github.com/myehya812/CareerHub.git
```

Move into the project:

```bash
cd CareerHub
```

---

## 2. Backend Setup

Move into the Laravel backend:

```bash
cd backend
```

Install PHP dependencies:

```bash
composer install
```

Create your environment file:

### Windows PowerShell

```powershell
Copy-Item .env.example .env
```

### Linux / macOS

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

---

## 3. Configure MySQL

Create a MySQL database for CareerHub.

For example:

```sql
CREATE DATABASE careerhub;
```

Then update the database configuration inside:

```text
backend/.env
```

Example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=careerhub
DB_USERNAME=root
DB_PASSWORD=
```

Use the correct MySQL username and password for your computer.

---

## 4. Run Database Migrations

From the `backend` directory:

```bash
php artisan migrate
```

If the project contains database seeders, you can also run:

```bash
php artisan db:seed
```

---

## 5. Start Laravel

Run:

```bash
php artisan serve
```

The backend should normally be available at:

```text
http://127.0.0.1:8000
```

---

## 6. Frontend Setup

Open another terminal and return to the project directory.

```bash
cd frontend
```

Install JavaScript dependencies:

```bash
npm install
```

Start the Vue development server:

```bash
npm run dev
```

Vite will display the local frontend URL in the terminal.

It will usually look similar to:

```text
http://localhost:5173
```

---

## 💡 What I'm Learning

CareerHub is primarily a learning project.

While building it, I am developing practical experience with:

- Designing full-stack applications
- Vue component architecture
- Vue Router
- API calls with Axios
- Laravel controllers and routes
- REST API design
- Database migrations
- Eloquent models and relationships
- MySQL relational database design
- CRUD operations
- Authentication
- Authorization
- Validation
- Error handling
- Git workflows
- Organizing larger software projects

The goal is not only to make the application work, but also to understand how a maintainable full-stack application is designed and developed.

---

## 📈 Project Status

🚧 **CareerHub is currently under active development.**

The application is being built progressively as I learn and implement additional full-stack development concepts.

The README and project documentation will be updated as new features are completed.
