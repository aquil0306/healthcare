# Healthcare Referral Management System

A comprehensive Laravel-based backend system for managing healthcare referrals with AI-assisted triage, real-time notifications, audit logging, and role-based access control.

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Quick Start](#quick-start)
- [Setup Instructions](#setup-instructions)
- [Architecture Decisions](#architecture-decisions)
- [Trade-offs Made](#trade-offs-made)
- [Assumptions](#assumptions)
- [Future Improvements](#future-improvements)
- [Optional Features Implemented](#optional-features-implemented)
- [API Documentation](#api-documentation)
- [Testing](#testing)

## Features

- **Referral Submission**: Hospitals can submit referrals via API using API key authentication
- **AI Triage**: Automatic urgency assessment and department routing with retry logic
- **Staff Notifications**: Role and department-based notifications with queuing for unavailable staff
- **Emergency Escalation**: Automatic escalation if emergency referrals aren't acknowledged within 2 minutes
- **Audit Logging**: Complete audit trail for all referral changes
- **Reporting**: Aggregated statistics for admins
- **Modern UI**: Vue 3 + Vuetify 3 frontend with responsive design
- **API Versioning**: RESTful API with versioning support
- **Security**: PII encryption at rest, token-based authentication, role-based access control

## Tech Stack

- **Backend**: Laravel 12 (latest)
- **Frontend**: Vue 3 + Vuetify 3
- **Database**: MySQL 8.0
- **Queue**: Redis
- **API Documentation**: Swagger/OpenAPI (L5-Swagger)
- **Authentication**: Laravel Sanctum (API tokens) + API Keys

## Quick Start

### Option 1: Docker Compose (Recommended - Single Command)

```bash
# Clone the repository
git clone <repository-url>
cd Assessment

# Copy environment file
cp .env.example .env

# Start all services (database, Redis, queue, scheduler, app, nginx)
docker-compose up -d

# Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations and seeders
docker-compose exec app php artisan migrate --seed

# Build frontend assets
docker-compose exec app npm run build
```

**Access the application:**
- Frontend: `http://localhost:8080`
- API: `http://localhost:8080/api/v1/...`
- Swagger UI: `http://localhost:8080/api/documentation`

### Option 2: Local Development

See [Setup Instructions](#setup-instructions) below for detailed local setup.

## Setup Instructions

### Prerequisites

- PHP 8.2+ (8.3 recommended)
- Composer 2.x
- Node.js 18+ & NPM
- MySQL 8.0+
- Redis (optional, for queues - can use database queue as fallback)

### Step-by-Step Local Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Assessment
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Update `.env` with your configuration:
   ```env
   # Database
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=healthcare_referral
   DB_USERNAME=root
   DB_PASSWORD=your_password
   
   # AI Triage Configuration
   # Choose one: 'openai' or 'gemini'
   AI_PROVIDER=openai
   
   # OpenAI Configuration (if using OpenAI)
   OPENAI_API_KEY=your_openai_api_key_here
   
   # Google Gemini Configuration (if using Gemini)
   GEMINI_API_KEY=your_gemini_api_key_here
   
   # Queue Configuration
   QUEUE_CONNECTION=redis  # or 'database' for local development
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

5. **Run database migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed database (optional - creates test data)**
   ```bash
   php artisan db:seed
   ```

   This creates:
   - Test hospitals with API keys
   - Sample patients
   - Staff members (admin, doctors, coordinators)
   - Departments and ICD-10 code mappings
   - Sample referrals

7. **Start the development servers** (requires 2 terminals)

   **Terminal 1 - Laravel Backend:**
   ```bash
   php artisan serve
   ```

   **Terminal 2 - Queue Worker (required for async jobs):**
   ```bash
   php artisan queue:work
   ```

   **Terminal 3 - Vue.js Frontend (Vite) - Optional for development:**
   ```bash
   npm run dev
   ```

   **Or build for production:**
   ```bash
   npm run build
   ```

8. **Access the application**
   - Frontend: `http://localhost:8000`
   - API: `http://localhost:8000/api/v1/...`
   - Swagger UI: `http://localhost:8000/api/documentation`

### Default Credentials (After Seeding)

- **Admin**: `admin@healthcare.com` / `password`
- **Doctor**: `doctor.cardiology@healthcare.com` / `password`
- **Coordinator**: `coordinator.cardiology@healthcare.com` / `password`

### Generate API Documentation

```bash
# Recommended: Use the custom script that handles validation warnings
php artisan-swagger.php

# Or use the bash script
./generate-swagger.sh

# Standard generation (will show a validation warning - harmless)
php artisan l5-swagger:generate
```

**Note**: The validation warning is harmless and doesn't affect API functionality. All endpoints are properly documented.

## Architecture Decisions

### Repository Pattern

**Decision**: Implemented repository pattern for data access abstraction.

**Why**: 
- Enables easier testing by allowing mock repositories
- Centralizes query logic and reduces duplication
- Makes future database changes easier (e.g., switching ORMs)
- Provides a clean interface between controllers and data layer

**Implementation**: 
- Base repository (`BaseRepository`) with common CRUD operations
- Specialized repositories (`PatientRepository`, `ReferralRepository`, etc.) for complex queries
- Dependency injection in controllers for testability

### Service Layer

**Decision**: Separated business logic into dedicated service classes.

**Why**:
- Promotes single responsibility principle
- Makes complex operations testable in isolation
- Reusable across different entry points (API, CLI, jobs)
- Clear separation between HTTP layer and business logic

**Services Implemented**:
- `AiTriageService`: Handles AI triage with retry logic
- `NotificationService`: Manages multi-channel notifications
- `AuditService`: Centralized audit logging
- `EscalationService`: Emergency referral escalation
- `DepartmentSuggestionService`: ICD-10 to department mapping

### Policies & Gates

**Decision**: Used Laravel Policies for authorization logic.

**Why**:
- Centralizes authorization rules in one place
- Makes permissions testable
- Clear, declarative permission checks
- Integrates seamlessly with Laravel's authorization system

**Implementation**: 
- Policy for each resource (Referral, Patient, Hospital, etc.)
- Role-based access control using Spatie Laravel Permission
- Middleware enforcement at route level

### Form Requests

**Decision**: Validation logic separated into Form Request classes.

**Why**:
- Keeps controllers thin and focused
- Reusable validation rules
- Better error handling and response formatting
- Clear validation requirements for each endpoint

### API Versioning

**Decision**: Routes organized by version prefix (`/api/v1/`).

**Why**:
- Allows future API evolution without breaking changes
- Clear separation between hospital, admin, and staff endpoints
- Enables gradual migration path for clients
- Industry best practice for API design

**Structure**:
- `/api/v1/hospital/*` - Hospital endpoints (API key auth)
- `/api/v1/admin/*` - Admin endpoints (Sanctum + role check)
- `/api/v1/staff/*` - Staff endpoints (Sanctum + role check)
- `/api/v1/auth/*` - Authentication endpoints

### Encryption Strategy

**Decision**: Patient PII encrypted at rest using Laravel's Crypt.

**Why**:
- Protects sensitive data (names, national IDs, insurance numbers)
- Meets healthcare data protection requirements
- Uses Laravel's built-in encryption (AES-256-CBC)
- Automatic encryption/decryption via model accessors/mutators

**Trade-off**: 
- Encrypted fields cannot be efficiently searched/indexed
- Full table scans required for searches
- For production, consider searchable encryption or hash indexes

### Event-Driven Architecture

**Decision**: Used Laravel Events and Jobs for async processing.

**Why**:
- Decouples components (referral triage triggers notifications)
- Improves response times (heavy operations in background)
- Scalable (queue workers can be scaled independently)
- Better error handling and retry logic

**Events**:
- `ReferralTriaged`: Fired after successful AI triage
- Triggers notification dispatch to appropriate staff

**Jobs**:
- `RetryAiTriage`: Retries failed AI triage calls
- `SendEmailNotification`: Async email sending
- `SendSmsNotification`: Async SMS sending
- `QueueNotificationForUnavailableStaff`: Queues notifications when staff unavailable
- `CheckEmergencyEscalations`: Scheduled job for escalation checks

### Database Design

**Decision**: Normalized database with proper relationships and indexes.

**Why**:
- Ensures data integrity
- Optimizes query performance with strategic indexes
- Clear relationships between entities
- Supports complex queries efficiently

**Key Indexes**:
- `(hospital_id, external_referral_id)` - Duplicate detection
- `status`, `urgency`, `department` - Common filters
- Foreign key indexes for joins

## Trade-offs Made

Given the time constraints of this assessment, the following trade-offs were made:

### 1. Encryption vs. Search Performance

**Trade-off**: Patient PII is encrypted, but searching by encrypted fields requires full table scans.

**Impact**: 
- Patient lookups by national ID are slower (must decrypt each record)
- Cannot use database indexes on encrypted fields
- Search functionality limited

**Production Solution**:
- Implement hash indexes for searchable fields (national_id hash)
- Use searchable encryption solutions
- Maintain separate search index (Elasticsearch) with encrypted data
- Consider field-level encryption for non-searchable fields only

### 2. AI Triage Integration

**Trade-off**: Integrated with Laravel AI package supporting OpenAI and Gemini, but uses synchronous processing with retry queue.

**Impact**:
- Initial triage blocks referral creation slightly
- Retry logic adds complexity but ensures reliability
- AI failures fall back to default values (routine urgency, general department)

**Production Solution**:
- Move AI triage to async job for faster response
- Implement circuit breaker pattern for AI service failures
- Add confidence score thresholds for auto-acceptance
- Consider multiple AI providers with fallback

### 3. Notification Channels

**Trade-off**: Email and SMS notification jobs are implemented but use mock/logging in development.

**Impact**:
- Notifications are queued and logged but not actually sent
- Requires integration with actual providers (SendGrid, Twilio, etc.)

**Production Solution**:
- Integrate with SendGrid/Mailgun for emails
- Integrate with Twilio for SMS
- Add webhook support for in-app notifications
- Implement notification preferences per staff member

### 4. Queue Processing

**Trade-off**: Supports both database and Redis queues, defaults to database for easier local setup.

**Impact**:
- Database queue is slower and less scalable
- Redis queue requires additional infrastructure

**Production Solution**:
- Use Redis for production (faster, more scalable)
- Implement queue monitoring and alerting
- Add dead letter queue for failed jobs
- Scale queue workers based on load

### 5. Frontend Completeness

**Trade-off**: Basic Vue components created with Vuetify, but full UI/UX polish would require more time.

**Impact**:
- Functional but not production-ready UI
- Missing some advanced features (bulk operations, advanced filters)
- Limited error handling and loading states

**Production Solution**:
- Complete all CRUD operations in UI
- Add comprehensive error handling
- Implement loading states and skeletons
- Add advanced filtering and search
- Mobile-responsive optimizations

### 6. Test Coverage

**Trade-off**: Core functionality tested (referral submission, AI triage, notifications), but not comprehensive.

**Impact**:
- Some edge cases may not be covered
- Integration tests limited
- No E2E tests

**Production Solution**:
- Increase coverage to 90%+
- Add E2E tests with Playwright/Cypress
- Performance and load testing
- Security testing (OWASP)

### 7. API Rate Limiting

**Trade-off**: Not implemented due to time constraints.

**Impact**:
- No protection against API abuse
- Potential for DDoS attacks

**Production Solution**:
- Implement Laravel rate limiting middleware
- Per-API-key rate limits
- IP-based rate limiting
- Monitor and alert on unusual patterns

## Assumptions

The following assumptions were made about the requirements:

### 1. ICD-10 Code Format

**Assumption**: ICD-10 codes are stored as strings and format validation happens at the hospital level.

**Rationale**: 
- ICD-10 codes have complex format rules (e.g., A00.0, Z99.9)
- Validation at hospital level reduces API complexity
- System accepts any string format

**If Different**: Would add regex validation or ICD-10 code lookup service.

### 2. Department Mapping

**Assumption**: Department-to-role mapping is configurable via database (ICD-10 code to department mappings).

**Rationale**:
- Allows flexibility without code changes
- Can be managed by admins via UI
- Supports multiple departments per ICD-10 code with priorities

**Implementation**: 
- `icd10_code_department` pivot table with `priority` and `is_primary` flags
- `DepartmentSuggestionService` calculates best department matches

### 3. Escalation Time Window

**Assumption**: Fixed 2-minute window for emergency referral acknowledgment.

**Rationale**:
- Simple, predictable behavior
- Meets emergency response requirements
- Can be made configurable later

**If Different**: Would add `escalation_timeout_minutes` per department or referral type.

### 4. AI Confidence Thresholds

**Assumption**: AI confidence scores are stored but not used for automatic decisions.

**Rationale**:
- Allows human review of low-confidence triage
- Provides transparency for audit purposes
- Can be used for reporting and analytics

**If Different**: Would add confidence thresholds (e.g., auto-accept if >0.9, flag for review if <0.7).

### 5. Duplicate Detection

**Assumption**: Duplicate referrals detected by `external_referral_id` + `hospital_id` combination.

**Rationale**:
- Hospitals control their own external IDs
- Prevents accidental duplicate submissions
- Simple and effective

**If Different**: Could add fuzzy matching on patient + date, or allow duplicates with different statuses.

### 6. Patient Deduplication

**Assumption**: Patients are deduplicated by `national_id` (encrypted field).

**Rationale**:
- National ID is unique identifier
- Prevents duplicate patient records
- Uses existing patient if found

**If Different**: Could use composite keys (name + DOB) or fuzzy matching.

### 7. Staff Availability

**Assumption**: Staff availability is a simple boolean flag (`is_available`).

**Rationale**:
- Simple to implement and understand
- Covers most use cases (on/off duty)
- Can be extended later with schedules

**If Different**: Would add availability schedules, time-based availability, or capacity limits.

### 8. Notification Channels

**Assumption**: All staff receive notifications via all configured channels (email, SMS, in-app).

**Rationale**:
- Ensures critical referrals are not missed
- Simple to implement
- Can be made configurable per staff member later

**If Different**: Would add notification preferences per staff member.

### 9. Audit Logging Granularity

**Assumption**: All referral changes are logged with before/after values.

**Rationale**:
- Meets audit requirements
- Provides complete change history
- Supports compliance and debugging

**Implementation**: 
- `AuditService` logs all changes
- Stores user, action, field, old value, new value, metadata
- Immutable log (no updates/deletes)

### 10. API Authentication

**Assumption**: 
- Hospitals use API keys (simple, stateless)
- Staff/Admins use Sanctum tokens (session-based, more secure)

**Rationale**:
- API keys suitable for server-to-server communication
- Tokens better for user sessions with logout capability
- Different security models for different use cases

## Future Improvements

With more time, I would implement the following:

### 1. Enhanced Security

- **Searchable Encryption**: Implement hash indexes or searchable encryption for PII fields
- **Rate Limiting**: Per-API-key and IP-based rate limiting
- **API Key Rotation**: Automatic key rotation with grace periods
- **Request Signing**: HMAC request signing for additional security
- **2FA**: Two-factor authentication for admin/staff accounts
- **Audit Log Retention**: Configurable retention policies with archival

### 2. Performance Optimizations

- **Caching Layer**: Redis caching for frequently accessed data (departments, staff, etc.)
- **Database Optimization**: Query optimization, read replicas, connection pooling
- **Elasticsearch**: Full-text search for referrals and patients
- **CDN**: Static asset delivery via CDN
- **API Response Caching**: Cache frequently requested endpoints
- **Database Indexing**: Additional indexes based on query patterns

### 3. Advanced Features

- **Real-time Notifications**: WebSocket support for instant in-app notifications
- **Advanced Reporting**: Charts, dashboards, export functionality (PDF, Excel)
- **Bulk Operations**: Bulk referral assignment, status updates
- **FHIR Compatibility**: FHIR R4-compatible referral payload format
- **Event Sourcing**: Full event sourcing for referral state changes (beyond audit logs)
- **Workflow Engine**: Configurable referral workflows per department
- **Document Management**: Attach files/images to referrals
- **Communication Log**: Track all communications about a referral

### 4. Testing & Quality

- **Test Coverage**: Increase to 90%+ coverage
- **E2E Tests**: Playwright/Cypress tests for critical user flows
- **Performance Testing**: Load testing, stress testing, benchmarking
- **Security Testing**: OWASP Top 10, penetration testing
- **Contract Testing**: API contract testing with consumers

### 5. Documentation & Developer Experience

- **API Examples**: Postman collection, cURL examples
- **Architecture Diagrams**: System architecture, data flow diagrams
- **Deployment Guides**: Step-by-step production deployment
- **Developer Onboarding**: Quick start guide, development setup
- **API Versioning Guide**: How to handle breaking changes

### 6. DevOps & Infrastructure

- **Kubernetes Deployment**: Helm charts, K8s manifests
- **CI/CD Enhancements**: Automated deployments, staging environments
- **Monitoring**: Application performance monitoring (APM), error tracking (Sentry)
- **Logging**: Centralized logging (ELK stack, CloudWatch)
- **Automated Backups**: Database backups with point-in-time recovery
- **Health Checks**: Comprehensive health check endpoints
- **Metrics**: Prometheus metrics, Grafana dashboards

### 7. User Experience

- **Advanced Filtering**: Multi-criteria filtering with saved filters
- **Keyboard Shortcuts**: Power user features
- **Dark Mode**: UI theme support
- **Mobile App**: React Native or Flutter mobile app
- **Offline Support**: Service workers for offline functionality

## Optional Features Implemented

The following optional features were implemented:

### ✅ Docker Compose Setup

**Status**: Fully implemented

**Implementation**:
- `docker-compose.yml` with all services (app, nginx, MySQL, Redis, queue worker, scheduler)
- Single command setup: `docker-compose up -d`
- Production-ready configuration
- Health checks and dependencies configured

**Usage**:
```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan migrate --seed
```

### ✅ OpenAPI / Swagger Documentation

**Status**: Fully implemented

**Implementation**:
- L5-Swagger package integrated
- All API endpoints fully annotated with OpenAPI 3.0
- Request/response schemas documented
- Authentication requirements specified
- Interactive Swagger UI at `/api/documentation`

**Features**:
- Complete endpoint documentation
- Try-it-out functionality
- Authentication testing (API keys, Bearer tokens)
- Example requests and responses

**Generate Documentation**:
```bash
php artisan-swagger.php
# Visit http://localhost:8000/api/documentation
```

### ✅ CI Pipeline

**Status**: Fully implemented

**Implementation**:
- GitHub Actions workflow (`.github/workflows/ci.yml`)
- Runs on every push and pull request
- Tests on multiple PHP versions (8.3)
- MySQL service for integration tests
- Code style checking with Laravel Pint

**Pipeline Steps**:
1. Checkout code
2. Setup PHP 8.3 with required extensions
3. Install Composer dependencies
4. Generate application key
5. Run database migrations
6. Execute PHPUnit tests (unit + feature)
7. Run code style checks (Pint)

**Status Badge**: Add to README:
```markdown
![CI](https://github.com/yourusername/Assessment/workflows/CI/badge.svg)
```

### ⚠️ FHIR-Compatible Format

**Status**: Not implemented (mentioned in future improvements)

**Note**: While not implemented, the current API structure could be extended to support FHIR R4 format. The referral payload structure is similar to FHIR ServiceRequest resource, but would need:
- FHIR resource types and structure
- FHIR coding systems (ICD-10, SNOMED CT)
- FHIR extensions and profiles
- FHIR validation

**Future Implementation**: Would add `/api/v1/fhir/` endpoints with FHIR-compatible request/response formats.

### ⚠️ Event Sourcing

**Status**: Partially implemented (audit logging, not full event sourcing)

**Current Implementation**:
- Comprehensive audit logging with `AuditService`
- Tracks all referral changes (what, who, when, before/after values)
- Immutable audit log table
- Event-driven architecture with Laravel Events

**What's Missing for Full Event Sourcing**:
- Event store (separate from audit log)
- Event replay capability
- State reconstruction from events
- Event versioning and migration
- CQRS pattern (Command Query Responsibility Segregation)

**Current Approach**: 
- Audit logs provide complete change history
- Can reconstruct state from audit logs
- Not optimized for event sourcing patterns (no event store, no replay)

**Future Implementation**: Would implement full event sourcing with:
- Event store table
- Event sourcing package (e.g., Spatie Event Sourcing)
- Separate read/write models (CQRS)
- Event replay and projection capabilities

## API Documentation

Swagger/OpenAPI documentation is available at `/api/documentation` after generating.

### Generate Documentation

```bash
# Recommended: Use the custom script
php artisan-swagger.php

# Or use the bash script
./generate-swagger.sh

# Standard generation (will show a validation warning - harmless)
php artisan l5-swagger:generate
```

### API Endpoints Summary

#### Hospital Endpoints
- `POST /api/v1/hospital/referrals` - Submit a new referral
  - Authentication: API Key (X-API-Key header)

#### Admin Endpoints
- `GET /api/v1/admin/referrals` - List all referrals (filterable, paginated)
- `GET /api/v1/admin/referrals/{id}` - View referral details
- `POST /api/v1/admin/referrals/{id}/assign` - Assign referral to staff
- `POST /api/v1/admin/referrals/{id}/cancel` - Cancel referral
- `GET /api/v1/admin/reports/statistics` - Get reporting statistics
- Full CRUD for hospitals, patients, staff, departments, ICD-10 codes
  - Authentication: Bearer token (Sanctum) + Admin role

#### Staff Endpoints
- `GET /api/v1/staff/referrals` - List assigned referrals
- `GET /api/v1/staff/referrals/{id}` - View referral details
- `POST /api/v1/staff/referrals/{id}/acknowledge` - Acknowledge referral
- `POST /api/v1/staff/referrals/{id}/complete` - Mark referral as complete
- `POST /api/v1/staff/referrals/{id}/update-status` - Update referral status
  - Authentication: Bearer token (Sanctum) + Doctor/Coordinator role

#### Common Endpoints
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/logout` - Logout
- `GET /api/v1/auth/user` - Get current user
- `GET /api/v1/notifications` - List notifications
- `POST /api/v1/notifications/{id}/acknowledge` - Acknowledge notification

## Testing

### Run Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

### Test Coverage

Current test coverage includes:
- ✅ Referral submission (API key auth, duplicate detection, patient deduplication)
- ✅ AI triage service (success, retry logic, fallback)
- ✅ Notification service (multi-channel, queuing)
- ✅ Audit logging (all referral changes)
- ✅ Authorization (role-based access control)
- ✅ Referral lifecycle (status transitions)

### Test Structure

```
tests/
├── Feature/
│   ├── ReferralSubmissionTest.php
│   ├── ReferralLifecycleTest.php
│   ├── NotificationTest.php
│   └── ...
└── Unit/
    ├── AiTriageServiceTest.php
    └── ...
```

## Scheduled Tasks

The system includes scheduled tasks for:
- Emergency escalation checks (every minute)
- Queue processing (via queue worker)

### Setup Cron (Production)

Add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Docker Scheduler

Docker Compose includes a scheduler service that runs automatically.

## License

This project is for assessment purposes.

## Author

Built as a take-home assessment for Senior Laravel Developer position.
