# Quiet hours rewards

- Dashboard: `/app/quiet-hours`, also linked from Loyalty and the sidebar. Reading uses `view_loyalty`; saving uses `store_loyalty`.
- Configure each branch independently: enabled, extra points per booking, and hourly selections for each weekday (Sunday is 0). Times use `Asia/Riyadh`. An hour is inclusive at the start, exclusive at the end; midnight belongs to the new calendar day. Working hours and availability stay authoritative.
- Public API: `GET /api/quiet-hours`; enabled settings only. Both frontend layouts share `public/quiet-hours.js` for matching appointment times. A failed preview request does not change server eligibility.
- A server-owned snapshot (`bookings.quiet_hours_points`) is saved on creation. Changing the appointment time, branch, customer, or home-service location recalculates it before completion. Ordinary edits and later settings changes preserve the promised reward. Home-service appointments are excluded from branch campaigns.
- Completion adds the snapshot to `loyalty_points` and records `source=quiet_hours`, `source_id=booking_id`. The booking and balance are locked, and the credit timestamp and ledger entry commit together. Repeated saves or completion notifications cannot award twice. Existing bookings default to zero; they are not retroactively rewarded unless rescheduled before completion.
- A booking here means a `bookings` database record. Checkout can create one record per selected service. The frontend separately totals the quiet-hours rewards of those records.
- Write booking changes through Eloquent model saves (as existing booking controllers do); query-builder bulk updates bypass model observers.

## Deployment

Configure the backend database, then run:

```sh
php artisan migrate --path=database/migrations/2026_10_22_000002_add_quiet_hours_rewards.php
```

Publish the frontend `dist` folder from `npm run build`. Rebuild/configure Laravel caches according to the deployment's normal process.

## Checks

```sh
php vendor/bin/phpunit --filter QuietHoursTest
php tests/Unit/quiet-hours.test.php
```

Frontend: `node --test tests/quiet-hours.test.cjs`. Integration tests use only in-memory SQLite and assert the database target before creating tables.
