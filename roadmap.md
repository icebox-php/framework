# Icebox PHP Framework Roadmap

This document outlines the features and components of a complete PHP web framework, with checkboxes indicating what has been implemented in Icebox. This enhanced roadmap merges practical implementation status with modern framework concepts.

## Overview

Icebox is a PHP framework following MVC architecture with ActiveRecord ORM, CLI tooling, and comprehensive logging. This roadmap tracks implementation status and identifies areas for future development, incorporating modern web framework best practices.

## 1. Core Foundation

### HTTP Foundation
- [ ] HTTP server implementation (currently uses PHP built-in server)
- [x] Request/Response abstractions (Request.php, Response.php)
- [x] Router with pattern matching (Routing.php)
- [ ] Middleware pipeline
- [ ] Context object for request lifecycle
- [x] Error handling and recovery (custom exceptions, error conversion)
- [x] Logging and debugging utilities (Log.php with Monolog integration)

### Application Bootstrap & Configuration
- [x] Application (Icebox\App) class with base path management
- [x] Environment configuration (.env support via phpdotenv)
- [x] URL generation helpers (root_url, url)
- [x] Base path management for file system operations
- [x] HTTPS detection and URL prefix handling
- [ ] Service container / Dependency injection
- [ ] Configuration file loading system
- [ ] Environment-specific configuration
- [ ] Application lifecycle events

## 2. Routing System

### Basic Routing
- [x] Static route matching
- [x] Dynamic route parameters with `:param` syntax
- [ ] Query parameter parsing
- [ ] Route groups/prefixes
- [ ] Named routes
- [ ] Route constraints/validation
- [x] HTTP method routing (GET, POST, PUT, DELETE, PATCH, etc.)
- [ ] Wildcard/catch-all routes
- [x] Resourceful routing (`resource()` method for RESTful controllers)
- [x] Request method detection with `_method` override

### Advanced Routing Features
- [ ] Subdomain routing
- [ ] Localized routes
- [ ] API resource routes (without new/edit views)
- [ ] Nested resources
- [ ] Route model binding
- [ ] Rate limiting middleware
- [ ] Route caching for performance

## 3. HTTP Layer

### Request Handling
- [x] Request parameter management (Request::params())
- [x] HTTP method detection (Request::method())
- [x] POST parameter filtering (`filter_post_params()`)
- [ ] Request body parsing (JSON, form data, multipart)
- [ ] File upload handling
- [ ] Cookie parsing and management
- [ ] Session management
- [ ] Request validation
- [ ] Content negotiation
- [ ] Header manipulation
- [ ] IP address detection
- [ ] User agent parsing

### Response Handling
- [x] Response class with status and headers (Response.php)
- [x] HTML response rendering
- [x] Redirect responses with proper HTTP status codes
- [ ] JSON response formatting
- [ ] File downloads
- [ ] Streaming responses
- [ ] Response compression
- [x] Status code helpers
- [x] Redirect helpers
- [ ] Custom response types
- [ ] Response caching headers
- [ ] Content negotiation

### Middleware System
- [ ] CORS handling
- [ ] Authentication middleware
- [ ] Rate limiting
- [x] Request logging (via Log.php)
- [ ] Body parsing middleware
- [ ] Static file serving
- [ ] Security headers
- [ ] CSRF protection
- [ ] Compression middleware
- [ ] Middleware pipeline architecture

## 4. Controller Layer

### Base Controller
- [x] Controller base class with render method (Controller.php)
- [x] Layout support with `application` default
- [x] View rendering with variable passing
- [x] Automatic view path resolution
- [x] Content blocks (`yield_html`, `start_content`, `end_content`)
- [x] Flash messages with session integration
- [ ] Before/after action filters
- [ ] Controller concerns / modules
- [ ] Strong parameters / mass assignment protection
- [ ] Response formatting (JSON, XML, etc.)

## 5. View / Template System

### Basic Templating
- [x] PHP-based templates (.html.php files)
- [x] Layout system with content yielding
- [x] View variable passing
- [x] Automatic view path resolution
- [ ] Template inheritance
- [ ] Partial templates
- [ ] View helpers
- [x] Form builders (in CRUD generator)
- [ ] Asset helpers (CSS, JS, image tags)

### Advanced Features
- [ ] Blade/Laravel-style template engine
- [ ] Template caching
- [ ] Internationalization support
- [ ] Localization helpers
- [ ] CSRF token generation in forms
- [ ] Pagination helpers
- [ ] Breadcrumb generation

## 6. Model / ORM Layer

### ActiveRecord Implementation
- [x] ActiveRecord base model (ActiveRecord/Model.php)
- [x] Database configuration with URL parsing
- [x] PDO connection management
- [x] Basic CRUD operations (create, read, update, delete)
- [x] Rails-style query builder
- [x] Model attribute accessors
- [x] Timestamps (created_at, updated_at)
- [ ] Model relationships (hasMany, belongsTo, etc.)
- [ ] Model validation
- [ ] Model callbacks (before_save, after_create, etc.)
- [ ] Model scopes
- [ ] Soft deletes
- [ ] Polymorphic associations

### Query Builder
- [x] `where()` with multiple syntaxes
- [x] Comparison operators (>, <, >=, <=, LIKE, etc.)
- [x] NULL checks (whereNull, whereNotNull)
- [x] IN clauses (whereIn, whereNotIn)
- [x] BETWEEN clauses
- [x] OR conditions
- [x] Raw SQL support
- [x] Ordering, limiting, offset
- [x] Aggregates (count, exists, first)
- [ ] JOIN support
- [ ] Subqueries
- [ ] Eager loading
- [ ] Query logging

## 7. Database Integration

### Database Tools
- [x] Database creator
- [x] Query builder
- [x] Migration system
- [ ] Database connection pooling
- [ ] Multiple database support
- [ ] Database seeding
- [ ] Database factory pattern
- [ ] Query performance analysis
- [ ] Database backup/restore

### Database Migrations
- [x] Migration generation CLI command
- [x] Migration runner
- [x] Table blueprint with column definitions
- [x] SQL generation for schema changes
- [x] Migration status tracking
- [x] Rollback support
- [x] Database reset
- [ ] Schema dump/load
- [ ] Seed data support
- [ ] Migration testing helpers
- [x] Transaction support

## 8. CLI Tools & Console

### Command Line Interface
- [x] Base command structure (Cli/BaseCommand.php)
- [x] Command registry (Cli/CommandRegistry.php)
- [x] Interactive console (PsySH REPL)
- [x] Development server command
- [x] Test runner command
- [x] Database commands (create, migrate, reset, rollback, status)
- [x] CRUD generator command
- [x] Migration generator command
- [ ] Artisan-style command syntax
- [ ] Command scheduling
- [ ] Queue worker commands
- [ ] Maintenance mode commands

### Code Generators
- [x] CRUD generator (controller, model, views)
- [x] Migration generator
- [ ] Model generator
- [ ] Controller generator
- [ ] Resource generator
- [ ] Test generator
- [ ] Seeder generator
- [ ] Policy generator

### Developer Experience
- [ ] Hot reload/auto-restart
- [x] CLI tool for scaffolding (CRUD, migrations)
- [x] Code generation utilities
- [ ] Comprehensive documentation
- [ ] Error messages with helpful hints

## 9. Security Features

### Input Security
- [ ] CSRF protection
- [ ] XSS prevention
- [x] SQL injection prevention (via PDO prepared statements)
- [ ] Input validation
- [ ] Output escaping
- [ ] File upload security
- [ ] Secure headers (CSP, HSTS, etc.)
- [ ] Input sanitization
- [ ] Secure cookie handling

### Authentication & Authorization
- [ ] User authentication
- [ ] Password hashing utilities
- [ ] Remember me functionality
- [ ] Role-based access control
- [ ] Permission system
- [ ] OAuth integration
- [ ] API token authentication

## 10. Testing Infrastructure

### Testing Framework
- [x] PHPUnit integration
- [x] Test command runner
- [x] Test case base class (TestCase.php)
- [x] Test helpers (TestHelper.php)
- [x] Database testing utilities
- [ ] HTTP testing helpers
- [ ] Browser testing (Selenium/Playwright)
- [ ] Mocking helpers
- [ ] Factory pattern for test data
- [ ] Fixture loading
- [ ] Mock request/response objects
- [ ] Integration testing helpers
- [ ] Performance benchmarking tools

### Test Types
- [x] Unit tests
- [x] Integration tests
- [ ] Feature tests
- [ ] Browser tests
- [ ] API tests
- [ ] Performance tests
- [ ] Security tests

## 11. Performance & Caching

### Optimization Features
- [ ] Query caching
- [ ] Page caching
- [ ] Fragment caching
- [ ] Opcode caching integration
- [ ] CDN integration support
- [ ] Response caching
- [ ] Static asset optimization
- [ ] Connection pooling
- [ ] Lazy loading

## 12. WebSocket Support

### Real-time Features
- [ ] WebSocket server integration
- [ ] Real-time event handling
- [ ] Broadcasting mechanisms
- [ ] Connection management

## 13. API Features

### REST API Development
- [ ] API resource controllers
- [ ] API authentication (JWT, OAuth)
- [ ] API versioning
- [ ] API documentation generation (OpenAPI/Swagger)
- [ ] Rate limiting per endpoint
- [ ] API key authentication
- [ ] RESTful API conventions
- [ ] CORS support

## 14. Deployment

### Production Readiness
- [ ] Production server configuration
- [x] Environment configuration (.env support)
- [ ] Graceful shutdown
- [ ] Process management integration
- [ ] Docker support
- [ ] Health check endpoints

## 15. Monitoring & Observability

### Application Insights
- [ ] Application metrics
- [ ] Performance monitoring hooks
- [ ] Distributed tracing support
- [ ] Health checks
- [ ] Custom metric collection

## 16. Extensibility

### Framework Extensibility
- [ ] Plugin system
- [ ] Custom middleware creation
- [ ] Event system/hooks
- [ ] Service container/dependency injection
- [ ] Provider/service registration

## 17. Internationalization

### Multi-language Support
- [ ] Translation files
- [ ] Locale detection
- [ ] Date/time formatting
- [ ] Number/currency formatting
- [ ] Pluralization rules

## 18. Additional Features

### Extended Capabilities
- [ ] Background job processing
- [ ] Email sending utilities
- [ ] File storage abstraction
- [ ] Cron job scheduling
- [ ] WebHook handling
- [ ] OAuth integration
- [ ] Payment gateway integration helpers

## Implementation Notes

### Currently Implemented (Based on Code Analysis)

**Core Framework:**
- App class handles base paths, URLs, and file system operations
- Configuration via environment variables
- Error handling with custom exceptions
- Request/Response abstractions with parameter management

**Routing:**
- Complete routing system with parameter extraction
- Resourceful routing for RESTful controllers
- Request method detection with override support

**Controllers:**
- Full controller base with rendering, layouts, and redirects
- Flash message system with session integration
- Content blocks for flexible template rendering

**Models/ORM:**
- ActiveRecord implementation with Rails-style query builder
- Comprehensive query methods (where, orWhere, whereIn, etc.)
- Database migrations with CLI commands

**CLI Tools:**
- Interactive console (PsySH REPL)
- CRUD and migration generators
- Database management commands
- Test runner

**Logging:**
- Multi-handler logging system
- Request logging with unique IDs
- PSR-3 compliance with Monolog backend

### Critical Missing Features

1. **Middleware Pipeline** - Essential for modern framework architecture
2. **Comprehensive Security** - CSRF, XSS protection, authentication
3. **API Development** - REST API features, OpenAPI documentation
4. **WebSocket Support** - Real-time capabilities
5. **Deployment Tools** - Docker, production configuration
6. **Performance Optimizations** - Caching at multiple levels
7. **Extensibility System** - Plugin architecture, DI container

## Version Planning

### v1.0 (Current)
- Stable MVC core with ActiveRecord
- CLI tooling and generators
- Basic logging and testing
- Request/Response abstractions

### v1.1 (Next Priority)
- Security features (CSRF, validation, authentication)
- Middleware pipeline architecture
- Enhanced testing framework
- API development support

### v1.2
- Performance optimizations and caching
- Internationalization support
- Advanced ORM features (relationships, scopes)
- WebSocket support

### v1.3
- Deployment tools (Docker, production config)
- Monitoring and observability
- Extensibility system (plugins, DI)
- Background job processing

### v2.0
- Complete middleware ecosystem
- Advanced API features (versioning, rate limiting)
- Enterprise-ready security
- Comprehensive monitoring

## Contributing

This roadmap serves as a guide for framework development. Checkboxes will be updated as features are implemented. New feature suggestions and pull requests are welcome.

---
*Last Updated: 26 December 2025*   
*Enhanced with modern web framework concepts*

**Note:** This roadmap merges practical implementation status from Icebox code analysis with comprehensive modern framework features.
