# Copilot Instructions for copilot_poc

## Project Context
- This repository is a Laravel 12 PoC running in Docker Compose with PostgreSQL, Vite, PHPUnit, Vitest, and Playwright.
- Prefer changes that preserve the existing local developer workflow using the provided scripts and containers.
- Keep the implementation simple, explicit, and consistent with Laravel conventions.
- The internal Laravel coding standards (DB access, multi-tenancy, sessions, security rules, etc.) live in `docs/design-spec.md`. Do not inline its contents here — it is long and only needs to be loaded during spec-driven design (`/speckit.plan`), not for every chat turn.

## Working Principles
- Follow the Spec Kit workflow for feature work in order: constitution, specification, clarification, planning, task breakdown, implementation, and verification.
- For new or significant changes, start from the relevant Spec Kit artifact in .specify/templates/ and the project constitution in .specify/memory/constitution.md before writing code. The constitution's Coding Standards Compliance principle requires `/speckit.plan` to also load and comply with `docs/design-spec.md`.
- Prefer user value and business intent over implementation shortcuts.
- Do not skip ahead from specification to implementation when the task is user-facing, contract-changing, or otherwise significant; use the appropriate Spec Kit step first.

## Spec-Driven Development Flow
- When a new feature or behavior change is requested, first confirm the intent, then create or update the relevant specification before implementation.
- If the requirement is ambiguous, capture the ambiguity in the specification or ask clarifying questions before coding.
- After the specification is defined, create or update the implementation plan and task breakdown before making code changes.
- Keep implementation aligned with the specification; if the implementation would diverge from the agreed scope, explain the discrepancy and update the spec or plan.
- Finish with verification using the relevant PHPUnit, Vitest, or Playwright checks before claiming the work is complete.

## Required Practices
- Write or update tests before implementing behavior changes whenever practical.
- Use PHPUnit for Laravel backend changes, Vitest for frontend/unit-level JavaScript changes, and Playwright for user-flow or end-to-end scenarios when the change affects interaction flows.
- Keep changes scoped and explainable; avoid hidden coupling or unnecessary abstractions.
- Do not introduce secrets or environment-specific values into the repository.
- Use migrations for database changes rather than ad hoc schema edits.
- Keep documentation updated when behavior, setup, or developer workflow changes.

## Code Style Expectations
- Favor clear naming, small focused methods, and straightforward control flow.
- Preserve existing project conventions unless a change clearly improves consistency.
- Avoid introducing new frameworks or dependencies without justification.

## Response Expectations
- Respond in Japanese unless the user explicitly requests another language.
- Keep explanations clear, concise, and practical for a Japanese-speaking developer.
- When proposing changes, explain the intent, the impacted files, and the verification approach.
- When implementing a feature or bug fix, confirm the relevant tests or checks that should be run.
- When requirements are ambiguous, call out the ambiguity and suggest the most reasonable default.
