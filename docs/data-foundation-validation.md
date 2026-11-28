# Data Foundation Validation

## Validation results

- PHPUnit: 189 passed, 7480 assertions in Phase 10.
- Full suite executed on isolated in-memory SQLite.
- MariaDB 10.11.16 validation completed.
- Backup restoration verified on an isolated database.
- Both security migrations tested with rollback and reapplication.
- Direct activity UPDATE, DELETE and REPLACE rejected.
- Activity INSERT remains available.
- Audit parent deletion restrictions verified.
- Soft delete and restore regression tests passed.
- Main record counts and activity checksum preserved.

## Security improvements

- Centralized audit actor context.
- Role and ownership authorization.
- Fail-closed data classification.
- Structured audit payload redaction.
- Safe value validation.
- Transactional audit protection.
- Audited catalog reference merging.
- Activity immutability triggers.
- Restrictive MariaDB audit foreign keys.

## Remaining operational requirements

- Enforce least-privilege production database accounts.
- Deny application accounts audit UPDATE, DELETE and DDL privileges.
- Administrative database access remains a trusted boundary.
- Historical sensitive data requires approved retention and remediation.
- Phase 9 reported 1498 potentially affected historical activity records,
  predominantly internal catalog metadata, with zero invalid payloads.
- Existing historical records were not rewritten or deleted.
- Protect backups and isolated validation databases.
- AI export requires separate authorization, data minimization,
  anonymization and provenance validation.
- PostgreSQL has not been validated.
- Concurrency and load testing remain deployment requirements.

## Next milestone

Expert Visit Management and greenhouse visit history.
