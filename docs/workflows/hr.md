---
status: verified
source_of_truth: source-code
last_verified: 2026-09-02
scope: plugins/webkul/recruitments, plugins/webkul/employees, plugins/webkul/time-off
confidence: high
---

# Human Resources Lifecycle & Employee Workflows

## 1. Scope

This document details the cross-cutting Human Resources workflows in Aureus ERP, spanning applicant hiring and employee onboarding, leave and time-off request approvals, tenure-based leave accruals, collision detection, and employee departure/offboarding.

The HR domain comprises three collaborating modules:
- **`recruitments`** (`plugins/webkul/recruitments`): Job positions (`JobPosition`), candidate pipelines (`Candidate`), applicant stages (`Applicant`, `Stage`), skill profiling, and employee creation upon hiring.
- **`employees`** (`plugins/webkul/employees`): Core employee master records (`Employee`), organizational hierarchy, departments (`Department`), work locations, partner synchronization (`Partner`), and departure recording.
- **`time-off`** (`plugins/webkul/time-off`): Leave requests (`Leave`), time-off types (`LeaveType`), allocation ledgers (`LeaveAllocation`), accrual plans (`LeaveAccrualPlan`, `LeaveAccrualLevel`), calendar scheduling, and approval workflows.

---

## 2. Entry Points

### Primary UI Entry Points (Filament Admin Panel)
- **Recruitment & Hiring (`Recruitments` Cluster)**:
  - `ApplicantResource`: Route `/admin/recruitments/applications/applicants` (`plugins/webkul/recruitments/src/Filament/Clusters/Applications/Resources/ApplicantResource.php`)
  - Sub-navigation: `ListApplicants`, `EditApplicant`, `ViewApplicant`, `ManageSkill`
- **Employee Directory (`Employees` Module)**:
  - `EmployeeResource`: Route `/admin/employees/employees` (`plugins/webkul/employees/src/Filament/Resources/EmployeeResource.php`)
  - Sub-navigation: `ListEmployees`, `CreateEmployee`, `EditEmployee`, `ViewEmployee`
- **Time Off Management (`Time Off` Clusters)**:
  - Management: `TimeOffResource` (`/admin/time-off/management/time-offs`) & `AllocationResource` (`/admin/time-off/management/allocations`)
  - Employee Self-Service: `MyTimeOffResource` (`/admin/time-off/my-time/my-time-offs`) & `MyAllocationResource` (`/admin/time-off/my-time/my-allocations`)
  - Configurations: `LeaveTypeResource`, `AccrualPlanResource`, `PublicHolidayResource`, `MandatoryDayResource`

### REST API v1 Entry Points
- `GET/POST /api/v1/employees/employees`: Full CRUD on employee records.
- `GET/POST /api/v1/recruitments/applicants`: Applicant management.
- `time-off`: Defines Eloquent API Resources in `src/Http/Resources/V1/` (`LeaveResource`, `LeaveAllocationResource`, `LeaveAccrualPlanResource`), but does not register public API routes in `routes/api.php`.

[VERIFIED]
Evidence: `plugins/webkul/recruitments/src/Filament/`, `plugins/webkul/employees/src/Filament/`, `plugins/webkul/time-off/src/Filament/`

---

## 3. Preconditions

1. **Company & Security User**: Active `Company` and `User` record for authentication.
2. **Job Positions & Departments**: Defined `JobPosition` and `Department` records in `employees`.
3. **Leave Types & Working Schedules**: Configured `LeaveType` policies in `time-off` and working hours calendars (`Calendar`) in `support`.
4. **Partner Master Records**: An active `Partner` record must exist for candidate conversion.

[VERIFIED]
Evidence: `plugins/webkul/employees/src/Models/Employee.php:251-278`, `plugins/webkul/time-off/src/Traits/TimeOffHelper.php`

---

## 4. Onboarding Flow: Applicant → Employee Conversion

```
[1] HR Recruiter opens Applicant on ApplicantResource (EditApplicant / ViewApplicant)
     │
     ▼
[2] Recruiter clicks "Create Employee" Header Action (Action::make('createEmployee'))
     │
     ├── Visibility Check: Hidden if application_status == HIRED or candidate->employee_id is present
     │
     ▼
[3] Invokes direct method: Applicant::createEmployee()
     │
     ├── Idempotency Check: Returns existing $this->candidate->employee if employee_id already set
     ├── Verifies partner presence ($this->candidate->partner_id)
     │
     ├── Creates Employee record in employees_employees:
     │     ├── name = candidate.name
     │     ├── user_id = candidate.user_id
     │     ├── job_id = applicant.job_id
     │     ├── department_id = applicant.department_id
     │     ├── partner_id = candidate.partner_id
     │     ├── work_email = candidate.email_from
     │     ├── mobile_phone = candidate.phone
     │     └── is_active = true
     │
     ├── Updates Candidate record in recruitments_candidates:
     │     └── employee_id = new Employee ID
     │
     ▼
[4] UI redirects to EmployeeResource (EditEmployee) for onboarding profile completion
     │
     └── [VERIFIED NOTE]: Direct method invocation. NO event is dispatched during conversion.
```

[VERIFIED]
Evidence: `plugins/webkul/recruitments/src/Filament/Clusters/Applications/Resources/ApplicantResource/Pages/EditApplicant.php:113-120`, `plugins/webkul/recruitments/src/Models/Applicant.php:212-240`

---

## 5. Time-Off Request & Supervisory Approval Lifecycle

### Approval Tier Governance (`LeaveType.leave_validation_type`)
- **`no_validation`**: Approved immediately upon submission (`state = State::VALIDATE_TWO`).
- **`hr`**: Requires single-tier validation by an HR Officer.
- **`manager`**: Requires single-tier validation by the Employee's direct supervisor.
- **`both`**: Requires two-tier validation: First approver moves request to `VALIDATE_ONE` ("To Validate"), second approver moves request to `VALIDATE_TWO` ("Approved").

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                               Time-Off Approval Flow                                   │
├───────────────────┬───────────────────────────────┬────────────────────────────────────┤
│ Step              │ UI Trigger / Action           │ Code Path & State Impact           │
├───────────────────┼───────────────────────────────┼────────────────────────────────────┤
│ 1. Submission     │ Employee files request on     │ `CreateTimeOff::handleRecord...()` │
│                   │ `MyTimeOffResource` or        │ • Collision check against overlaps │
│                   │ `TimeOffResource`             │ • Balance check against allocation │
│                   │                               │ • State = `confirm` (or `validate` │
│                   │                               │   if `no_validation`)              │
├───────────────────┼───────────────────────────────┼────────────────────────────────────┤
│ 2. First Tier     │ Approver clicks "Approve"     │ `TimeOffsTable::approve` action    │
│    Validation     │ on `TimeOffResource` table    │ • If `leave_validation_type == both`│
│                   │                               │   State = `validate1` (To Validate)│
│                   │                               │ • Otherwise:                       │
│                   │                               │   State = `validate` (Approved)    │
├───────────────────┼───────────────────────────────┼────────────────────────────────────┤
│ 3. Second Tier    │ Second Approver / HR clicks   │ `TimeOffsTable::approve` action    │
│    Validation     │ "Validate" on table           │ • State = `validate` (Approved)    │
│                   │                               │ • Creates `CalendarLeave` in       │
│                   │                               │   working hour calendar            │
├───────────────────┼───────────────────────────────┼────────────────────────────────────┤
│ 4. Refusal        │ Approver clicks "Refuse"      │ `TimeOffsTable::refuse` action     │
│                   │ on `TimeOffResource` table    │ • State = `refuse` (Rejected)      │
│                   │                               │ • Unblocks reserved allocation     │
└───────────────────┴───────────────────────────────┴────────────────────────────────────┘
```

[VERIFIED]
Evidence: `plugins/webkul/time-off/src/Filament/Clusters/Management/Resources/TimeOffResource/Tables/TimeOffsTable.php:77-114`, `plugins/webkul/time-off/src/Traits/TimeOffHelper.php:274-389`

---

## 6. Leave Accrual & Balance Allocation

### Source Code Reality vs Documentation Resolution

> **"Was leave accrual already resolved in time-off.md, or did this workflow need to resolve it from source?"**

1. **Resolution from Source Code**:
   - `docs/plugins/time-off.md` documented the schema tables (`time_off_leave_accrual_plans`, `time_off_leave_accrual_levels`) and UI forms, but left the automated execution mechanism unresolved.
   - **Investigation of Source Code**:
     - `LeaveAccrualPlan` and `LeaveAccrualLevel` encapsulate tenure thresholds (`start_count`, `start_type`), frequency rules (`daily`, `monthly`, `yearly`), and carryover caps (`maximum_leave`).
     - `LeaveAllocation` stores `allocation_type = 'accrual'`, `last_called`, and `next_call`.
     - **Finding**: There is **NO background scheduler, artisan command, or cron daemon** in the repository that automatically iterates employees and writes accrued balance increments. Accrual rules currently act as configuration master data, while allocation balances are updated via manual allocations or UI requests.

[VERIFIED]
Evidence: `plugins/webkul/time-off/src/Models/LeaveAccrualPlan.php`, `plugins/webkul/time-off/src/Models/LeaveAllocation.php`, `plugins/webkul/time-off/src/TimeOffServiceProvider.php`

---

## 7. Employee Departure & Offboarding

### Offboarding Execution Analysis

> **"What actually happens to access and related records during employee offboarding?"**

When an employee departs, the HR officer enters offboarding details in the Settings tab of `EmployeeForm` (`EmployeeResource`):
- `departure_reason_id`: Foreign key to `employees_departure_reasons`.
- `departure_date`: Effective date of exit.
- `departure_description`: Administrative exit notes.
- `is_active`: Toggled to `false`.

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                              Offboarding Impact Matrix                                 │
├─────────────────────────┬───────────────────┬──────────────────────────────────────────┤
│ Area                    │ System Behavior   │ Code Reality                             │
├─────────────────────────┼───────────────────┼──────────────────────────────────────────┤
│ 1. Employee Record      │ Updated           │ Sets departure reason, departure date,   │
│                         │                   │ notes, and `is_active = false`.          │
├─────────────────────────┼───────────────────┼──────────────────────────────────────────┤
│ 2. Security User &      │ NOT Modified      │ The linked `User` record (`user_id`) is  │
│    Login Access         │                   │ **NOT disabled or deleted**. Login       │
│                         │                   │ remains active unless manually revoked.  │
├─────────────────────────┼───────────────────┼──────────────────────────────────────────┤
│ 3. Ownership Scopes     │ Preserved         │ Assigned tasks, leads, sales orders, and │
│    & Assigned Records   │                   │ purchase orders are **NOT reassigned**.  │
├─────────────────────────┼───────────────────┼──────────────────────────────────────────┤
│ 4. Historical Records   │ Preserved         │ All Chatter logs, timesheets, partner    │
│                         │                   │ links, and audit trails remain intact.   │
└─────────────────────────┴───────────────────┴──────────────────────────────────────────┘
```

[VERIFIED]
Evidence: `plugins/webkul/employees/src/Filament/Resources/EmployeeResource/Schemas/EmployeeForm.php:671-689`, `plugins/webkul/employees/src/Models/Employee.php:250-305`

---

## 8. State Transitions

### `ApplicationStatus` Lifecycle (`recruitments_applicants`)
- **`ONGOING`**: Active candidate under evaluation in recruitment pipeline stages.
- **`HIRED`**: Candidate hired (`date_closed` filled, candidate converted to Employee).
- **`REFUSED`**: Candidate rejected with reason code (`refuse_reason_id`).
- **`ARCHIVED`**: Inactive or soft-deleted application.

### Time-Off Request State Lifecycle (`time_off_leaves.state`)
- **`confirm`**: Request submitted; awaiting supervisory review.
- **`validate1`**: First-stage approval granted in a two-tier policy (`leave_validation_type == both`).
- **`validate`**: Fully approved and scheduled on calendar.
- **`refuse`**: Rejected by supervisor/HR officer.

[VERIFIED]
Evidence: `plugins/webkul/recruitments/src/Models/Applicant.php:199-210`, `plugins/webkul/time-off/src/Enums/State.php`

---

## 9. Edge Cases & Error Handling

1. **Overlapping Leave Collision Detection**:
   - `TimeOffHelper::checkForOverlappingLeave()` queries existing leaves for the same employee where dates overlap and state is not `REFUSE`. Throws a validation exception if a collision is detected.
2. **Insufficient Allocation Balance**:
   - `TimeOffHelper::handleLeaveAllocation()` computes remaining balance (`total_allocated - total_taken`). If requested days exceed balance on a type where `allows_negative = false`, submission is blocked.
3. **Duplicate Applicant Conversion Idempotency**:
   - `Applicant::createEmployee()` checks if `$this->candidate->employee_id` is already populated; if so, it returns the existing `Employee` instance without creating a duplicate.
4. **Half-Day & Hourly Precision**:
   - When `request_unit_half = true`, `TimeOffHelper` calculates 0.5-day increments and splits morning/afternoon slots via `RequestDateFromPeriod`.

[VERIFIED]
Evidence: `plugins/webkul/time-off/src/Traits/TimeOffHelper.php:274-389`, `plugins/webkul/recruitments/src/Models/Applicant.php:218-220`

---

## 10. Authorization / Security

1. **Scoped Permissions**:
   - Enforced by `ApplicantPolicy`, `EmployeePolicy`, `LeavePolicy`, `LeaveAllocationPolicy`, and `LeaveAccrualPlanPolicy`.
   - Supports `GLOBAL`, `GROUP`, and `INDIVIDUAL` resource visibility.
2. **Self-Service vs Manager Boundaries**:
   - `MyTimeOffResource` and `MyAllocationResource` scope queries strictly to the authenticated user's linked employee record (`user->employee`).
   - Management resources (`TimeOffResource`, `AllocationResource`) allow authorized managers and HR officers to view and approve department/organization records.

[VERIFIED]
Evidence: `plugins/webkul/time-off/src/Policies/`, `plugins/webkul/employees/src/Policies/`

---

## 11. Models / Data Architecture

### Core Database Tables
- **`recruitments_applicants`**: Job applications with stage, salary, ratings, and refusal reasons.
- **`recruitments_candidates`**: Candidate contact profiles linked to partners and employees.
- **`employees_employees`**: Employee master records, work contacts, hierarchy, and departure fields.
- **`employees_departments`**: Organizational departments and managers.
- **`time_off_leaves`**: Time-off absence requests and approval states.
- **`time_off_leave_types`**: Leave policies, validation types, and allocation rules.
- **`time_off_leave_allocations`**: Granted leave credit ledgers.
- **`time_off_leave_accrual_plans`**: Master milestone accrual policies.
- **`time_off_leave_accrual_levels`**: Accrual rate tiers and frequency rules.

[VERIFIED]
Evidence: `plugins/webkul/recruitments/database/migrations/`, `plugins/webkul/employees/database/migrations/`, `plugins/webkul/time-off/database/migrations/`

---

## 12. Events / Listeners / Observers Catalog

| Event / Mechanism | Trigger Source | Timing | Handled By | Effect |
| :--- | :--- | :--- | :--- | :--- |
| `Applicant::createEmployee()` | Recruiter action | Synchronous | Direct method call | Creates `Employee` and links `Candidate`. No event dispatched. |
| `TimeOffsTable::approve` | Manager action | Synchronous | Direct state update | Sets `state = validate` and creates `CalendarLeave`. |
| `TimeOffsTable::refuse` | Manager action | Synchronous | Direct state update | Sets `state = refuse` and sends Filament UI notification. |
| `Employee::boot()` | Model saving | Synchronous | Internal closure | Automatically creates or updates linked `Partner` record. |

[VERIFIED]
Evidence: `plugins/webkul/recruitments/src/Models/Applicant.php`, `plugins/webkul/employees/src/Models/Employee.php:250-256`

---

## 13. Business Rules Observed

1. **Partner Synchronization**: Every employee must be linked to a partner record (`Partner` of sub-type `employee`), created automatically upon employee creation.
2. **Strict Absence Collision Gating**: An employee cannot have two active leave requests overlapping the same calendar timeframe.
3. **Two-Tier Supervisory Approval**: Leave types with `leave_validation_type = both` require two separate approvals (`validate1` followed by `validate`).
4. **Idempotent Conversion**: Re-triggering employee creation on a hired candidate returns the existing employee profile without duplication.
5. **Non-Destructive Offboarding**: Employee departure updates HR attributes but preserves user security accounts and assigned documents unless manually modified.

[VERIFIED]
Evidence: `plugins/webkul/employees/src/Models/Employee.php`, `plugins/webkul/time-off/src/Traits/TimeOffHelper.php`

---

## 14. Unknowns / Inferences

### [UNKNOWN]
1. **Automated Accrual Daemon**: The models and configuration schemas for `LeaveAccrualPlan` and `LeaveAccrualLevel` exist, but background cron automation for balance increment calculation is [UNKNOWN] / not implemented in the codebase.
2. **Automated User Account Deactivation**: Automatic security user revocation upon employee departure is [UNKNOWN] / not implemented.

### [INFERRED]
1. **Separation of HR and Security Roles**: Keeping user account deactivation manual reflects an intentional security architecture separation between HR employee records and system-level IAM accounts.

---

## 15. Evidence References

| Area | File Path | Key Symbols |
| :--- | :--- | :--- |
| **Applicant Model** | `plugins/webkul/recruitments/src/Models/Applicant.php` | `Applicant::createEmployee()`, `getApplicationStatusAttribute()` |
| **Applicant Edit Page** | `plugins/webkul/recruitments/src/Filament/Clusters/Applications/Resources/ApplicantResource/Pages/EditApplicant.php` | `createEmployee` header action |
| **Employee Model** | `plugins/webkul/employees/src/Models/Employee.php` | `Employee::handlePartnerCreation()`, `handlePartnerUpdation()` |
| **Employee Form Schema** | `plugins/webkul/employees/src/Filament/Resources/EmployeeResource/Schemas/EmployeeForm.php` | Departure reason fields (`departure_reason_id`, `departure_date`) |
| **Time Off Helper Trait** | `plugins/webkul/time-off/src/Traits/TimeOffHelper.php` | `checkForOverlappingLeave()`, `handleLeaveAllocation()` |
| **Time Off Table** | `plugins/webkul/time-off/src/Filament/Clusters/Management/Resources/TimeOffResource/Tables/TimeOffsTable.php` | `approve`, `refuse` actions |
| **Leave Accrual Plan Model** | `plugins/webkul/time-off/src/Models/LeaveAccrualPlan.php` | `LeaveAccrualPlan` definition |

---

## 16. Mermaid Flowchart

```mermaid
flowchart TD
    subgraph RecruitmentModule ["Recruitment & Onboarding"]
        CAND["Candidate Profile"]
        APP["Applicant (Job Application)"]
        HIRE_ACT["'Create Employee' Action (EditApplicant)"]
    end

    subgraph EmployeeMaster ["Employee Directory"]
        EMP["Employee Record (employees_employees)"]
        PARTNER["Partner Profile (partners_partners)"]
        DEPART["Departure Recording (departure_reason_id, is_active=false)"]
    end

    subgraph TimeOffModule ["Time Off Management"]
        REQ["Leave Request (time_off_leaves)"]
        COLL_CHK{"Collision & Balance Check"}
        APP_TIER{"Validation Type"}
        T1["First Approver (validate1)"]
        T2["Final Approver (validate)"]
        CAL_LEAVE["CalendarLeave (Working Schedule)"]
        REF["Refused Request (refuse)"]
    end

    %% Onboarding Flow
    APP -->|Candidate Hired| HIRE_ACT
    HIRE_ACT -->|Applicant::createEmployee()| EMP
    EMP -->|Employee::boot()| PARTNER
    CAND -.->|Linked employee_id| EMP

    %% Leave Request Flow
    EMP -->|Files Leave Request| REQ
    REQ --> COLL_CHK
    COLL_CHK -->|Overlap or Insufficient Balance| REQ
    COLL_CHK -->|Valid| APP_TIER

    %% Approval Routing
    APP_TIER -->|leave_validation_type = both| T1
    T1 -->|Approve| T2
    APP_TIER -->|leave_validation_type = hr/manager| T2
    APP_TIER -->|leave_validation_type = no_validation| T2
    APP_TIER -->|Refuse Action| REF
    T2 -->|Approved| CAL_LEAVE

    %% Offboarding Flow
    EMP -->|Employee Exits Organization| DEPART
    DEPART -.->|Preserves Historical Logs & User Record| EMP
```
