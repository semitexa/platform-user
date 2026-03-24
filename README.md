# semitexa/platform-user

User management module with CRUD, role assignment, profile management, and activity tracking.

## Purpose

Provides the complete user management backend for the platform. Handles user creation, authentication flows, RBAC role assignment, dynamic profiles, avatar storage, and audit trails.

## Role in Semitexa

Depends on `semitexa/core`, `semitexa/api`, `semitexa/auth`, `semitexa/orm`, `semitexa/ssr`, `semitexa/testing`, `semitexa/storage`, and `semitexa/platform-wm`. Integrates most of the framework stack and serves as the reference implementation for building full-featured platform modules.

## Key Features

- User CRUD with validation
- Login/logout flows integrated with Auth
- RBAC role assignment via Authorization
- Dynamic user profiles
- Avatar storage via Storage
- Activity tracking and audit
- WM desktop app integration
- API endpoints for M2M user operations

## Notes

This is a platform-level module that integrates most of the framework stack. It serves as the reference implementation for building full-featured platform modules.
