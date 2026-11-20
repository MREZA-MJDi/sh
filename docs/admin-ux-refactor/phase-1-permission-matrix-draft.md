# Phase 1 — Permission Matrix Draft

> Draft for review and inventory reconciliation. Not an authoritative matrix yet; do not use this file to grant or revoke permissions until every role, route, policy, and scope has been verified.

## Scope model
- all: records within the entire authorized tenant/system scope
- assigned: records explicitly assigned/linked to the acting user
- own: records owned by the acting user
- view: read-only capability
- manage: create/update/delete and related management actions, only when explicitly defined

## Resource inventory (based on navigation currently reviewed)
| Resource | Candidate capabilities | Scope variants to verify |
|---|---|---|
| Users / students | view, create, update, delete | all, assigned |
| Teachers | view, create, update, delete | all |
| Parents | view, create, update, delete | all |
| Courses | view, create, update, delete | all, assigned |
| Classes | view, create, update, delete, manage | all, assigned |
| Recorded videos / SpotPlayer | view, manage | all, assigned |
| Attendance | view, manage | all, assigned |
| Homework | view, manage | all, assigned |
| Exams | view, create, update, delete, manage | all, assigned (verify) |
| Online classes | view, manage | all, assigned |
| Quiz24 | integrations access | all (verify granular actions) |
| Messages | view, reply, manage | all, assigned |
| Consultations | view, manage | all, assigned (verify) |
| Parent meetings | view, manage | all, assigned |
| Announcements | view, create, update, delete | all (verify) |
| Enrollments | view, manage | all (verify) |
| Payments | view, manage | all (verify) |
| Resources | view, manage | all, assigned |
| SEO | view, manage | all |
| Articles | view, manage | all |
| Banners | view, manage | all |
| Testimonials | view, manage | all |

## Mandatory verification before authorization changes
- Inventory actual role names and permission assignments.
- Inventory routes and middleware and test direct route access.
- Inspect controllers, policies, gates, service-layer and query scopes.
- Confirm all vs assigned filtering at query/data level, not UI-only.
- Check mutations, exports, downloads, uploads and API endpoints.
- Check cross-user record access (IDOR).
- Check session role switching and guard boundaries.
- Reconcile naming conventions and remove duplicate/obsolete permission keys only after proving they are unused.

## Decision
This draft is an inventory aid, not an authorization implementation. Actual permission keys and role grants must be confirmed against the source before code changes.
