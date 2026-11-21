# HappieeBite 🍔

**HappieeBite** is a food ordering and restaurant management application built with Laravel. It provides a structured platform for managing restaurants, menus, customers, carts, orders, payments, notifications, and administrative operations.

The project focuses on maintainable architecture, secure payment processing, background job execution, real-time notifications, and automated business workflows.

## 🚀 Key Features

### 1. Authentication & Authorization
- Secure user registration and login.
- Role-based access control using Spatie Laravel Permission.
- Rate limiting to protect authentication endpoints.
- Policy-based authorization for model-level access control.
- Protected application features based on user roles and permissions.

### 2. Service-Oriented Architecture
Implemented dedicated service interfaces and service classes to separate business logic from controllers.

The application uses Laravel Service Container bindings for dependency injection.

**Implemented service interfaces:**
- `StorageServiceInterface`
- `RestaurantServiceInterface`
- `MenuServiceInterface`
- `OrderServiceInterface`
- `AddressServiceInterface`
- `CartServiceInterface`
- `RazorpayServiceInterface`

Used `bind()` and `singleton()` based on the service lifecycle requirements.

### 3. Payment Integration – Razorpay
- Razorpay payment gateway integration.
- Payment signature verification.
- Webhook integration for payment event handling.
- Payment refund functionality.
- Secure payment processing and verification workflows.

### 4. Cloud Storage – AWS S3
- Integrated AWS S3 bucket for file storage.
- Centralized storage handling through a dedicated storage service.
- Cloud-based file management.

### 5. Real-Time Notifications
- Integrated Laravel Reverb for real-time event broadcasting.
- Implemented real-time notification functionality.
- Event-driven communication for application updates.

### 6. Redis Caching & Queue Management
- Redis integration for caching frequently accessed and long-running query results.
- Background job processing using Laravel queues.
- Order dispatch through queued jobs.
- Queue monitoring and management using Laravel Horizon.

### 7. Automated Scheduled Tasks
Implemented scheduled commands to automate recurring business operations.

| Scheduled Task | Frequency |
|---|---|
| Expire old pending orders | Every 30 minutes |
| Automatically close restaurants | Daily at 12:00 AM |
| Automatically open scheduled restaurants | Daily at 8:00 AM |
| Send daily admin reports | Daily at 7:00 AM |
| Remove old notifications | Weekly |
| Prune old Laravel Telescope entries | Daily |

### 8. Model Observers & Activity Logs
- Implemented Laravel model observers.
- Maintained activity logs based on model events.
- Centralized tracking of important application changes.

### 9. Authorization & Security
- Laravel policies for model-based authorization.
- Role and permission management.
- Authentication rate limiting.
- Payment signature verification.
- Webhook-based payment event handling.

## 🛠️ Technology Stack

| Technology | Purpose |
|---|---|
| PHP | Backend development |
| Laravel | Application framework |
| MySQL | Database |
| Redis | Caching and queue backend |
| Laravel Horizon | Queue monitoring |
| Laravel Reverb | Real-time broadcasting |
| Razorpay | Payment gateway |
| AWS S3 | Cloud file storage |
| Spatie Laravel Permission | Role and permission management |
| Laravel Policies | Model authorization |
| Laravel Scheduler | Automated tasks |
| Laravel Telescope | Application debugging and monitoring |

## ⚙️ Installation & Setup

### Prerequisites
- PHP and Composer
- MySQL
- Node.js and npm
- Redis
- Git

### 1. Clone the Repository

```bash
git clone <repository-url>
cd happiee-bite
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Configure Environment

```bash
cp .env.example .env
```

Configure your database, Redis, AWS S3, Razorpay, and Reverb credentials in the `.env` file.

### 4. Run Application Setup

```bash
php artisan app:fresh-start
```

This custom command handles the initial application setup, including:
- Application key generation.
- Cache clearing.
- Database migrations.
- Initial role and permission seeding.
- Storage symbolic link creation.
- Application optimization.

**Note:** The setup command uses `migrate:fresh`, which drops existing database tables. Use it only with a fresh development database.

### 5. Start Development Servers

```bash
npm run dev:all
```

This starts the Laravel development server, Vite development server, and Laravel Reverb server together using Concurrently.

## 🔄 Background Processing

Make sure Redis is running before starting queue processing.

Laravel Horizon is used to monitor and manage background jobs.

```bash
php artisan horizon
```

## ⏰ Task Scheduling

The application uses Laravel's task scheduler for recurring operations.

For local development, you can run:

```bash
php artisan schedule:work
```

For production, configure the Laravel scheduler using a server cron entry.

## 📡 Real-Time Communication

Laravel Reverb handles WebSocket connections and real-time event broadcasting.

```bash
php artisan reverb:start
```

## 🎯 Project Objectives

- Implement maintainable Laravel application architecture.
- Apply dependency injection and service container bindings.
- Build secure payment and webhook workflows.
- Handle background jobs and queue monitoring.
- Implement real-time event broadcasting.
- Automate recurring business operations.
- Apply role-based and policy-based authorization.
- Integrate cloud storage and Redis caching.

## 👨‍💻 Developer

Developed as a portfolio project to demonstrate practical Laravel application development, backend architecture, third-party integrations, and scalable application workflows.
