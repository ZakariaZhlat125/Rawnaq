# Customer notifications

The authenticated inbox covers booking creation, status, schedule and service changes; booking payments; store order creation, delivery and payment status; wallet credits/debits and wheel rewards; and loyalty credits, deductions and expiry. Zero-point wheel audit records do not create notifications.

Entries use the existing `notifications` table and are written on the activity's database connection. If an enclosing transaction rolls back, its notification rolls back too. No schema migration is required. Bulk booking/payment updates now load models to trigger the same observers. Future bulk SQL writes must likewise use model events or explicitly record customer activity.

`GET /api/notification-list` returns paginated `notification_data`, `all_unread_count`, `current_page` and `last_page`. Viewing the list does not mark it read. `POST /api/notifications/{id}/read` and `POST /api/notifications/read-all` are scoped to the signed-in user. The legacy `type=mark_as_read` query remains compatible.

`GET /api/notifications/stream` uses authenticated fetch with Server-Sent Events. The server checks committed inbox entries every second, closes after 20 seconds, and the client reconnects automatically. Hidden pages disconnect. Failed streams fall back to list refreshes with increasing retry delays. Bearer tokens are never placed in URLs. The mobile iframe uses same-origin messages to share the Vue shell's connection; standalone mobile pages open their own connection.

Deploy Laravel changes and the generated frontend `dist` together. Clear cached routes if the deployment caches routes. The production PHP server needs concurrent workers; a single-worker development server can block other requests during the stream. Disable reverse-proxy response buffering for the stream endpoint (`X-Accel-Buffering: no` is already sent). Size PHP workers for concurrent live viewers.

This is live delivery while the website is open. Existing template-based mail/FCM delivery is preserved, with duplicate customer database entries removed. Browser push while the website is closed requires a separate Web Push subscription/service-worker integration; this change does not provision push credentials. Anonymous cafe orders are not assigned to customer accounts and therefore are not included in this authenticated inbox.

Checks:

```text
cd backend
php vendor/bin/phpunit tests/Feature/CustomerNotificationsTest.php
cd ../apps/web
node --test tests/customer-notifications.test.cjs
npm run build
```
