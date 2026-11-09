# Restricted employee access

An account with `employee`, without `admin` or `manager`, is restricted to a read-only employee portal:

- `/app/employee/bookings`: its assigned service/package lines, appointment, customer name, status and its own line value.
- `/app/employee/reports`: its booking counts, completed line value and recorded commissions within the selected period (up to 366 days).
- Sanctum equivalents: `/api/employee/bookings` and `/api/employee/reports`.

The employee ID is always taken from the authenticated account. Request parameters cannot select another employee. Shared bookings expose only the current employee's lines, not the other employees' names, services, prices or total booking value. Package ownership follows `booking_packages.employee_id`. Commission totals use only the authenticated employee's ledger entries and their creation dates. These are recorded commissions, not a promise of a paid-out balance.

`EmployeeOnlyAccess` is an allowlist on both web and API middleware groups. Management pages, general reports, settings, other employees' detail endpoints, booking mutations and exports are blocked by default, even when old direct/additional-role permissions exist. Authentication, the employee portal, own notification channels/inbox, Web Push subscription and logout remain available. Old `/app/bookings` and `/admin` entry points redirect to the restricted portal. Public data remains public to anonymous visitors as before.

The Gate restriction is registered before permission package boot callbacks, preventing an old direct permission from short-circuiting the restriction. The employee role is reduced to `view_own_employee_bookings`, `view_own_employee_reports`, `view_notification`. The normal permission seeder preserves this restriction. Existing admin/manager access is retained, including accounts also assigned the employee role.

The portal uses an independent layout without management menus, branch selectors, employee filters or customer directories. It retains the employee's own recent notifications and realtime chime. The customer cart view composer does not run shopping/cart processing for restricted employees.

Apply on deployment:

```sh
php artisan migrate --path=database/migrations/2026_10_24_000003_restrict_employee_permissions.php
```

The migration does not restore broad permissions on rollback. Any deliberate future expansion requires updating the explicit policy/allowlist, not assigning a second unrestricted role to an employee. There are no frontend bundle changes for this task.

Tests: `php -d opcache.enable_cli=0 vendor/bin/phpunit tests/Feature/EmployeeOnlyAccessTest.php`. The suite uses in-memory SQLite and covers management/direct-permission denial, shared bookings, spoofed employee IDs, personal totals, rendered portal, migration and admin/manager exemptions.
