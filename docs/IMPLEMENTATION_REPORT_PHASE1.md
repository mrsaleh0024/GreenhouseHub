# Phase 1 implementation report

## Summary

Branch: `feature/data-foundation`. Commit: `0b5983b`. Date: 2026-11-26. This increment creates the core Phase 1 tables, basic Eloquent models, sequential code generation, catalog support, customer/greenhouse registration and post-OTP linking, and data-classification configuration.

## Acceptance checklist

| Criterion | Status | Evidence |
|---|---|---|
| AC-01 | PASS | `migrate:fresh --seed` created all ten new tables and removed `owner_profiles`. |
| AC-02 | PARTIAL | Fresh MariaDB migration/seeding passes; rollback round-trip was not run. |
| AC-03 | PASS | `composer test`: 76/76 passed. |
| AC-04 | PASS | Migrations use Laravel schema builder only. |
| AC-05 | PARTIAL | Foreign keys use the requested actions, but no information-schema export was captured. |
| AC-06 | FAIL | Seeder currently contains the provinces and a starter catalog, not the complete required catalog. |
| AC-07 | FAIL | Catalog merge/approval workflow is not complete. |
| AC-08 | PARTIAL | Customer, greenhouse, cycle and zone codes are server-generated and guarded. |
| AC-09 | PARTIAL | Activity model is immutable and has denormalized IDs; automatic auditing is unfinished. |
| AC-10 | FAIL | Full policies and visibility scopes are not yet implemented. |
| AC-11 | PARTIAL | Owner registration and post-OTP link are implemented; comprehensive integration coverage is absent. |
| AC-12 | PARTIAL | Configuration covers the introduced schema; no coverage-enforcement test exists. |
| AC-13 | FAIL | Laravel Boost was installed because the repository bootstrap instruction explicitly required it, conflicting with the supplied phase brief. |

## Deviations and decisions

The supplied scope is only partially implemented. The full catalog dataset, factories, policies, validation rules, activity observers, completeness calculator, catalog merge/approval, required test matrix, rollback verification, and database-output appendices remain open. Laravel Boost introduced the dependency and its generated configuration because it was mandatory in the original repository instructions.

## Existing files changed

Registration, mobile verification, `User`, provider, seed orchestration, registration view, and the two explicitly allowed authentication test helpers were updated for catalog-backed registration and customer linking.

## Migration status

`php artisan migrate:fresh --seed --force` completed successfully against MariaDB on 2026-11-26. `composer test` completed with 76 passing tests and 287 assertions.

## Known limitations and open questions

Do not treat this increment as full Phase 1 acceptance: the FAIL/PARTIAL items above must be completed before production use.

### Follow-up in progress (2026-11-26)

Laravel Boost and its generated files were removed, and the project guidance and dependency files were restored to their `main` versions. The UI commit was reverted on this branch and preserved on `feature/light-theme`. The catalog now includes the specified lists and Persian labels. Initial visibility scopes, policies, model activity hooks, completeness calculation and catalog review/merge operations were added, but are not yet fully verified against the specification.

The original 76-test suite passed after these changes; `DataFoundationProgressTest` adds four passing tests for catalog counts/idempotence, normalization, owner visibility, and classification coverage. The full required 25-item test matrix, full validation layer, enums, factories, demo records, option/equipment invariants, automatic completeness on all paths, concurrency safety, MariaDB round-trip evidence and complete report appendices remain unfinished. The checklist above has not been re-certified; do not interpret its earlier PASS entries as evidence of complete compliance.
