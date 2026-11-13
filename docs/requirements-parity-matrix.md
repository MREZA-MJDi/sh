# Employer Requirements Parity Matrix

Branch: `feat/clean-backend-parity-from-sheykhan-20261113`
Baseline: fast-forwarded from `main` after the local tone/style commit.
Scope: backend behavior first. Preserve existing Blade layouts and navigation; later visual work is limited to color consistency, component/widget polish, and real-data widgets.

Status meanings:
- **Implemented**: model/routes/workflow exist in the current codebase; still subject to runtime regression tests.
- **Partial**: a useful foundation exists, but one or more required workflow steps are missing.
- **Missing**: no complete implementation has been identified.
- **Provider-pending**: local workflow/preview can be delivered now; live provider calls require credentials/API contract.

## Role workflow matrix

| Employer requirement | Current evidence in sh | Status | Remaining work |
|---|---|---|---|
| Admin/secretary: register students, guardians and teachers | role/permission system, UserController, UserManagementService, guardian-child relations | Implemented | Audit create/edit/link flows and role-specific authorization |
| Admin: courses and classrooms | Course/Classroom models, controllers, services, enrollment service | Implemented | Regression coverage for lifecycle and roster synchronization |
| Admin/teacher: upload/manage homework and review submissions | Homework, HomeworkSubmission, SubmissionFile models; admin and student controllers; review workflow tests | Implemented | Verify all allowed file types, storage privacy, feedback visibility |
| Student: submit homework as text/files and view results | Student submission routes/controllers and submission-file model | Implemented | Regression tests for PDF/image/Word/text limits and guardian visibility |
| Admin/teacher: learning resources | LearningResource model/service/controller and scoped download routes | Implemented | Audit course/classroom scope, replacement/deletion and teacher boundaries |
| Student/guardian: access enrolled resources | PortalLearningResourceController and access feature tests | Implemented | Add negative tests for expired/revoked enrollment and cross-child access |
| Admin/teacher: internal exams and results | Exam, Question, ExamAttempt, ExamAnswer, ExamResult; admin/student exam workflows | Implemented | Verify scheduling, expiration, grading, guardian visibility and reports |
| External Quiz24 exams/results/launch | Quiz24 service, mapping models, import workflow and waiting-contract operation records; admin page now includes an explicitly marked sample exam/result preview | Provider-pending (preview delivered) | Replace preview data with provider adapter responses and activate live sync after API/SSO credentials |
| Admin: attendance entry | Attendance model/service/controller and classroom scope | Implemented | Move recording to session-aware workflow and preserve legacy records |
| Attendance duration and lateness | Attendance now links to a class session, records optional joined_at/left_at, computes measured minutes and lateness, and leaves historical rows without both timestamps unmeasured | Partial | Add Skyroom event ingestion and attendance percentage/report aggregation; do not infer duration for missing timestamps |
| Skyroom automatic attendance | Existing class schedule/link workflow, session-aware manual attendance and an explicitly marked sample attendee report preview | Provider-pending (preview delivered) | Implement provider event/webhook adapter and reconcile real join/leave events idempotently |
| Parent/teacher meetings | Dedicated ParentMeeting model, migrations, admin/guardian controllers, service, requests, routes and dedicated `meetings.*` permissions and role-scoped pages; class-specific scheduling, encrypted HTTPS online link, guardian accept/decline, reschedule re-confirmation, dashboard widgets | Implemented (source + feature tests added; runtime unverified) | Add notifications, verify migration and workflow tests in local environment, review meeting history and reschedule/cancel edge cases |
| Guardian consultation requests | Consultation, ConsultationReply, guardian routes/controller, admin status handling | Implemented | Keep distinct from scheduled parent meetings; improve teacher scheduling only if in scope |
| Guardian/student ↔ teacher/admin messages | Message, MessageReply and portal routes exist; teacher inbox scope now also includes the message's related student enrollment; dedicated regression test added | Partial | Verify full reply/read/close notifications and role combinations; execute regression tests |
| Recorded course/class videos | SpotPlayerVideo staging and management models/services/controllers | Partial | Complete demo playback state, publish lifecycle, access preview and replace raw-file 409 placeholder only through safe demo/player mode |
| SpotPlayer protected playback/licenses | SpotPlayer contract/service, local license queue and waiting-contract operation; admin page now includes an explicitly marked protected-player/license preview | Provider-pending (preview delivered) | Replace sample state with real player/license adapter responses when official API credentials arrive |
| Announcements/news | Announcement model/controller and audience filtering | Implemented | Verify audience and publish scheduling against all role dashboards |
| Admin dashboard operational overview | DashboardService provides permission-scoped KPIs, recent messages, consultations, classes and attendance trend | Implemented | Validate metric correctness and add only useful real-data widgets after workflow completion |
| Student dashboard progress | StudentDashboardService, CourseProgressService and progress models/routes | Implemented | Verify edge cases and display consistency |
| Guardian dashboard child progress | GuardianDashboardService scopes classes, homework, attendance, exams, resources, progress and announcements | Implemented | Verify all child-specific scopes and empty/multi-child states |
| Teacher dashboard/workspace | Teacher shares admin workspace with permission-scoped queries | Partial | Verify each teacher operation is constrained to assigned classes/courses; avoid separate duplicate UI |
| Counseling appointment scheduling by teacher | Consultation request/reply/status foundation; no confirmed teacher proposal workflow | Partial | Treat as later phase; don't claim complete |

## Integration delivery policy

Each integration has a read-only local Preview with explicitly marked sample data. Preview values are not persisted and must never be represented as provider responses. Once the provider contract arrives, implement the real adapter behind the integration service and replace preview values with live responses without changing the existing role-facing pages.

| Provider | Demo deliverable | Production activation requirement |
|---|---|---|
| SpotPlayer | Course/video mapping preview, protected-player preview, sample license states and student eligibility preview | Official API contract, credentials, provider account/course IDs |
| Quiz24 | Exam catalog preview, student mapping, launch preview, sample results and unmatched participant resolution | Official API/SSO contract and credentials |
| Skyroom | Session creation/schedule preview, join-link preview, attendee/attendance sample report | Official API/webhook contract and credentials |

## Delivery sequence

1. Complete requirement-to-code audit and preserve the pushed admin tone stylesheet.
2. Implement parent/guardian meeting workflow as a distinct domain from consultations.
3. Complete message authorization and notification workflow.
4. Complete session-aware attendance calculations without fabricating historical durations.
5. Add Demo/Preview gateways and visible integration states for SpotPlayer, Quiz24 and Skyroom.
6. Run automated tests, build and route/security regression checks.
7. Only then apply small frontend polish: existing layout/navigation remain; adjust colors, component tones and add useful real-data widgets. No frontend redesign.

## Verification notes

This matrix is based on repository source inspection. It is not a claim that PHPUnit, Vite build, browser flows, or live provider APIs have been executed. Each item must be marked runtime-verified only after those checks are actually run.
