# Admin UX & Authorization Audit — Phase 0

Repository: MREZA-MJDi/sh
Branch: refactor/admin-ux-permission-matrix
Status: Preliminary audit (not an exhaustive runtime audit)

## Reviewed areas
- Admin layout and permission-to-navigation mapping
- Navigation groups and sidebar behavior
- Topbar page-local search, notifications, and profile dropdown
- Admin CSS/JS entrypoint organization
- Courses index card density and actions
- Classes index card and modal workflows

## Findings

### UX
1. Sidebar groups are all expanded by default, making navigation long and noisy.
2. Navigation is grouped by feature buckets, but lacks an operations-first view of pending work.
3. Search is explicitly page-local, not global navigation or resource search.
4. Course cards carry too much information and too many actions; improve hierarchy and move secondary actions.
5. Class management workflows are split across card/modal actions; a class workspace with tabs for roster, meetings, attendance and assignments would reduce context switching.
6. Topbar notifications/search/profile affordances need clearer task-oriented behavior and consistent interaction states.

### Authorization / architecture risks (not confirmed vulnerabilities)
1. Layout-level permission mapping may drift from route/controller/policy enforcement.
2. Sidebar visibility alone cannot guarantee authorization; direct URL, API, mutations, downloads and assigned-record scopes must be checked independently.
3. Permission naming and scopes should be inventoried and normalized before refactoring.

## Suggested phase sequence
1. Audit current roles, permissions, routes, policies, controller checks and scope constraints.
2. Define a resource/action/scope matrix and align authorization enforcement.
3. Rebuild navigation from a single permission-aware configuration and responsive sidebar.
4. Refactor dashboard into an operational center using real data.
5. Improve admin pages/workspaces and form/table UX.
6. Regression, authorization isolation, responsive, build and performance checks.

## Limitations
This is a preliminary source review based on files and snippets previously inspected. Runtime verification, exhaustive route/controller review, automated test execution and visual browser testing have not been completed.
