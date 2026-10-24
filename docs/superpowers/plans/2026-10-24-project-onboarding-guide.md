# Project Onboarding Guide Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a Thai developer guide that maps this Laravel project's architecture, feature flows, data model, and safe starting points for future development.

**Architecture:** Documentation only. The guide follows request flow from routes and middleware through controllers, services/models/import-export code, persistence, and Blade views, then records domain-specific flows and maintenance risks.

**Tech Stack:** Laravel 13, PHP 8.3, Eloquent, Blade, PHPUnit, Maatwebsite Excel, Vite.

**Spec:** User request in chat: read and understand the whole project in detail so continued development is easier.

## Global Constraints

- Do not modify application behavior or source code; deliver documentation only.
- Do not expose secrets from `.env` or runtime data.
- Base statements on repository evidence and mark operational details that depend on environment.

## Review Focus

- Framework/config versions must match `composer.json` and the repository's database configuration.
- Feature entry points must match actual routes, controllers, service/import/export classes, and views.
- Security and maintenance caveats must be phrased as verification guidance, not claims that a vulnerability exists.
- Guide must disclose that a line-by-line runtime audit and tests were not performed.
- No application tests are required for documentation-only output.

---

## File Structure

- Create `docs/PROJECT_GUIDE.md`: durable onboarding map for architecture, feature ownership, data flows, development setup, and risk areas.
- Create `docs/superpowers/plans/2026-10-24-project-onboarding-guide.md`: this task breakdown and review checklist.

### Task 1: Map repository structure and runtime entry points

**Files:**
- Read: `composer.json`, `.env.example`, `routes/web.php`, `app/Http/Kernel.php`, `app/Providers/AppServiceProvider.php`
- Read: `app/Http/Controllers/`, `app/Services/`, `app/Support/`, `app/Imports/`, `app/Exports/`, `app/Models/`
- Read: `database/migrations/`, `resources/views/`, `tests/`, `package.json`, `phpunit.xml`

**Interfaces:**
- Consumes: user request and repository source as of 2026-10-24.
- Produces: file/module map and traceable feature entry points for Task 2.

- [x] Inventory project roots, dependencies, routes, middleware, controllers, model/service/import/export families, migrations, views, and tests.
- [x] Trace representative Awareness, Kidney DHB, salt, sodium, HT/HI, auth, and admin management entry points.
- [x] Record material caveats, including DB compatibility, geography scoping, fiscal-year data, file storage, caching, and oversized controller/view files.

### Task 2: Write and self-review the Thai developer guide

**Files:**
- Create: `docs/PROJECT_GUIDE.md`
- Create: `docs/superpowers/plans/2026-10-24-project-onboarding-guide.md`

**Interfaces:**
- Consumes: repository map from Task 1.
- Produces: a guide that a new maintainer can use to locate feature code and follow request/data flows.

- [x] Write the guide with architecture map, file ownership, module map, setup, tests, data model, flows, and safe-change checklist.
- [x] Check that every named class/file is grounded in repository inventory and no `.env` values are copied.
- [x] State scope limits and avoid claiming runtime verification or test success.
- [x] Review for unexpanded placeholders and ensure plan links to the guide deliverable.
