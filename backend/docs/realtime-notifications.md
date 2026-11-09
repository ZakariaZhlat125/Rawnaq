# Pusher realtime notifications

Only backend credentials are needed. Set these in Laravel's `.env` (never the frontend `.env`):

```dotenv
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
```

Use the exact cluster shown in your **Pusher Channels** app. `REALTIME_NOTIFICATIONS_ENABLED` defaults to `true`; empty credentials keep the feature inactive. The notification publisher explicitly uses the Pusher connection; no additional frontend key configuration or broadcast-driver switch is required. If configuration is cached, run `php artisan config:clear` after entering credentials (or rebuild the configuration cache through your usual deployment).

Dependencies and compiled assets are installed/built in this workspace. On deployment install the updated Composer lock (`composer install`) and publish both Laravel's `public/js/realtime-notifications.js`, `public/js/vendor/pusher.min.js` and the rebuilt frontend `dist`. The frontend build syncs the two shared dashboard assets automatically. No migration or additional realtime queue worker is required; if existing application notifications themselves use an asynchronous queue, keep that existing worker running as usual.

Every persisted `Illuminate\Notifications\DatabaseNotification` targeting a user publishes `notification.created` on `private-notifications.user.{id}` **after the enclosing transaction commits**. The payload contains only the notification ID; clients fetch their existing authorized inbox. Delivery errors are logged without interrupting notification persistence, booking or payment. Rolled-back notifications never publish.

The authenticated config endpoints are `/api/notifications/realtime-config` (Sanctum) and `/app/notifications/realtime-config` (dashboard session). Private channel authorization uses `/api/broadcasting/auth` with a Bearer token or `/broadcasting/auth` with a session and CSRF token. Both validate that the requested channel belongs to the authenticated user. Responses expose the app key and cluster, never the app secret.

The dashboard refreshes its unread badge and the open dropdown on arrival. Desktop and mobile use the existing shared customer inbox; the mobile iframe delegates to its parent so it does not create a second socket or play a second chime. Connections are destroyed on logout/account change or page teardown. Disconnected clients fall back to polling, and reconnecting clients refresh missed notifications.

The local Web Audio chime has three soft notes, an event-ID deduplicator and a short rate limit. It works after the first pointer/keyboard interaction; browser autoplay policy can otherwise block audio. The dashboard and inbox have a persistent mute toggle. Existing notifications on first load do not ring. This is in-page audio while the page is running; closed-page delivery remains the existing Web Push feature.

Validation: PHP tests cover private configuration/authentication, post-commit delivery, rollback, missing credentials and Pusher failure without external calls. JS tests mock the Pusher client to cover authorization, deduplication, teardown, polling and user-gesture audio. A live Pusher connection must be checked after credentials are entered; no test message has been sent to real users.

Pusher's official authorization reference: https://pusher.com/docs/channels/server_api/authorizing-users/
