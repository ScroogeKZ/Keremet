# Хром-KZ Логистика - Shipment Management System

## Overview
This project is a full-stack web application designed for the logistics department of Хром-KZ company to manage and track logistics expenses efficiently. As the customer/client of logistics services, the system provides forms for creating local (Astana) and regional shipment orders, alongside a comprehensive admin panel for expense tracking and order management. The system aims to streamline internal logistics operations and provide detailed expense reporting for budget control.

## User Preferences
Preferred communication style: Simple, everyday language.

## Recent Changes
- **Migration Completed (2025-08-01)**: Successfully migrated from Replit Agent to Replit environment
- **Database Setup**: Created PostgreSQL database and restored schema with users and shipment_orders tables
- **Dependencies**: Installed PHP 8.3 and all required Composer packages
- **Security**: Verified proper client/server separation and security practices
- **UI Fixes (2025-08-01)**: Fixed sidebar layout issues across all admin pages and removed all emojis from CRM interface
- **Testing**: All main pages (/, /astana.php, /crm/login.php) responding correctly with HTTP 200

## System Architecture

### Core Technologies
- **Language**: PHP 8.3 (with Composer autoloading)
- **Web Server**: PHP built-in server (development)
- **Database**: PostgreSQL (with PDO connections)
- **Authentication**: PHP sessions with secure password hashing
- **UI Framework**: Tailwind CSS (via CDN)
- **Class Structure**: PSR-4 autoloading (`App\` namespace)

### File Structure
- `public/`: Web-accessible PHP pages (index, forms, admin)
- `src/`: PHP classes (Auth, Models, Services)
- `config/`: Database configuration
- `vendor/`: Composer dependencies

### Key Components & Design Patterns
- **Database Schema**: `users` (admin authentication), `shipment_orders` (main entity with common and regional-specific fields, status, timestamps), `clients` (customer management), `verification_codes`, `notifications`, `settings`, `shipment_tracking`.
- **API Routes**: Standard RESTful API for authentication (`/api/admin/login`, `/api/admin/logout`), order creation (`/api/orders`), order retrieval and filtering (`/api/orders`), and order status updates (`/api/orders/:id/status`). Includes a public tracking API (`/api/tracking.php`).
- **Frontend Pages**:
    - **Order Forms**: Dedicated forms for Astana (`/astana`) and Regional (`/regional`) orders.
    - **Admin Panel**: Protected interface (`/admin`) with modules for Dashboard, Orders, Notifications, Calendar, Bulk Operations, Reports, Analytics, Clients, and Settings.
    - **Tracking Page**: Public page (`/tracking.php`) for customers to track orders.
    - **Client Dashboard**: Personal cabinet for registered clients (`/client/dashboard.php`).
- **Repository Pattern**: Implemented in `server/storage.ts` for managing users and shipment order CRUD operations, including filtering.
- **UI/UX Decisions**:
    - Modern, minimalist design with a focus on clear visual hierarchy, ample white space, and consistent spacing.
    - Professional color scheme using blue gradients and gray backgrounds, completely emoji-free interface for corporate use.
    - Unified top navigation menu across all admin pages with active highlighting.
    - Responsive design applied consistently across all interfaces (desktop and mobile).
    - Enhanced statistics cards with gradients and subtle hover effects.
    - Clean typography and consistent element sizing.
    - Internal corporate focus as logistics department/customer rather than logistics service provider (e.g., "expense tracking" instead of "revenue", "costs we pay" instead of "earnings").
- **Feature Specifications**:
    - **Comprehensive CRM System**: Dashboard, Order Management (editing, status updates), Notifications (real-time, Telegram/Email integration), Delivery Calendar, Bulk Operations (mass updates, route assignment), Reports (CSV export, financial summaries focused on logistics expenses), Analytics (visual charts, KPI metrics).
    - **Client Management System**: Client registration with verification (6-digit codes via SMS/Email), client profiles, and order history linked to client accounts.
    - **Order Tracking**: Real-time tracking history, search by order ID or client phone number.
    - **Cost Calculator**: Interactive calculation based on PostgreSQL tariff database with detailed breakdown.
    - **Photo Uploads**: Functionality to attach photos to shipment orders.
    - **Specialized Cargo Categories**: Predefined company-specific cargo types.
    - **Phone Validation**: Robust validation for Kazakhstan phone formats.

## External Dependencies

- **Database**:
    - **Neon PostgreSQL**: Serverless PostgreSQL.
    - Requires `DATABASE_URL` environment variable for connection.
- **Authentication**:
    - PostgreSQL-based session store.
    - bcrypt for password hashing.
    - Requires `SESSION_SECRET` environment variable for secure session cookies.
- **UI Libraries**:
    - **Tailwind CSS**: Utility-first CSS framework (via CDN).
    - **Radix UI**: Accessible component primitives (for specific components like Shadcn/UI).
    - **Shadcn/UI**: Pre-built component library.
    - **Lucide React**: Icon library.
- **Development Tools**:
    - **Drizzle Kit**: Database migration and schema management.
    - **TypeScript**: For type safety (where applicable in initial Node.js versions, but project is now PHP).
    - **ESLint/Prettier**: Code formatting and linting.
    - **Vite**: Fast development server and build tool (used during hybrid phase, now primarily PHP built-in server).
- **Communication/Integration**:
    - **Telegram Bot API**: For order notifications and system alerts. (Requires configuration via admin settings).
    - **SMS API Integration**: Planned for client verification (e.g., Twilio, SMS.ru).