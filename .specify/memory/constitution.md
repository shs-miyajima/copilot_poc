<!-- Sync Impact Report
Version change: 1.0.0 -> 1.1.0
Modified principles: none
Added principles: VI. Coding Standards Compliance
Added sections: none
Removed sections: none
Follow-up TODOs: none
-->

# copilot_poc Constitution

## Core Principles

### I. Test-First Quality
Every user-visible or business-rule change MUST be covered by a failing test before implementation. PHPUnit, Vitest, and Playwright are the required validation layers for the relevant scope, and the change is not complete until the relevant suite passes.

### II. Environment Parity
Development, testing, and runtime MUST use the same containerized stack described by Docker Compose. Local changes MUST NOT depend on undocumented host-only setup, and new services, ports, or environment variables MUST be declared in the repository configuration.

### III. Simple, Explicit Design
Features MUST be implemented with the smallest clear abstraction that satisfies the requirement. Prefer Laravel conventions, explicit names, and straightforward data flow over clever shortcuts or hidden coupling.

### IV. Secure by Default
Secrets, credentials, and environment-specific values MUST stay outside the repository. Input validation, authorization checks, and database changes through migrations are mandatory; direct manual data mutations in production-like environments are prohibited.

### V. Observable and Documented Delivery
Behavioral changes MUST be explainable through code, tests, and repository documentation. Logging, error handling, and setup steps MUST be sufficient for another contributor to understand and verify the change without tribal knowledge.

### VI. Coding Standards Compliance
Any design or implementation plan MUST comply with the internal coding rules in `docs/design-spec.md` (Model-only DB access, soft-delete/multi-tenant scoping, optimistic locking via `version`, session and file-upload handling, and the security rules for SQL injection, XSS, CSRF, and path traversal). Planning work (`/speckit.plan`) MUST read `docs/design-spec.md` alongside this constitution, record compliance in the Constitution Check gate, and flag any deviation as a violation requiring justification in the plan's Complexity Tracking table. This gate is re-evaluated after Phase 1 design as a self-review pass before the plan is considered complete.

## Additional Constraints
This project uses Laravel 12, Docker Compose, PostgreSQL, Vite, and modern PHP tooling. New dependencies MUST be justified by the requirement, documented in the repository, and compatible with the existing local container workflow. Feature work MUST preserve the ability to run the app locally with the provided scripts and containers.

## Development Workflow
Work MUST follow the repository's Spec Kit flow: capture intent in a specification before implementation, refine ambiguity when needed, plan changes before coding, and verify with the relevant test suite before completion. Changes that alter contracts, data shape, or user flows MUST include the corresponding tests and documentation updates.

## Governance
This constitution supersedes ad hoc practices for this repository. Amendments require a documented rationale, review of impact on testing and deployment, and explicit version updates. All pull requests and local changes MUST demonstrate compliance with these principles through tests, clear intent, and any required documentation updates.

**Version**: 1.1.0 | **Ratified**: 2026-08-04 | **Last Amended**: 2026-08-04
