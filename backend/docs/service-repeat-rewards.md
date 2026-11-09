# Repeat service rewards

Dashboard: `/app/service-repeat` under Loyalty permissions (`view_loyalty`, `store_loyalty`). A site-wide switch defaults to **off**. Enable it and configure each service's switch and required completed paid visits (1–1000).

Counting starts at the later of first site-wide activation and first activation for that service. Older bookings are excluded. Disabled campaigns do not earn or accept new redemptions; previously reserved bookings remain valid. Enabling again retains progress and issued rewards. Changing the threshold affects unconsumed progress on the next qualifying completion, without changing issued rewards.

Each completed, paid standalone booking counts once per service. Packages, fully discounted services, complimentary redemptions, cancellations before completion, and unpaid bookings do not count. Products and home visit fees never count as paid service value. There is no automatic retroactive recount on editing a completed booking. Completion should follow payment recording, through Eloquent saves.

After each set of qualifying visits, an independent reward is issued for the same service. It works at any available branch offering that service and has no expiry. Users explicitly select it on the confirmation screen. The server validates ownership, matching service, active campaign, and availability under locks. The reward is reserved in the cart transaction, and the service's persisted price becomes zero while the home visit fee remains payable. The existing checkout calculates tax on payable items and supports a zero-total card checkout without contacting a gateway.

Unused reservations are released on Eloquent cancellation/deletion and the normal cart expiration bulk-delete path. Completion marks the reward used. A used reward is not restored by later cancellation. A cancelled complimentary booking cannot be reactivated after its reward was returned. Bulk status changes outside these paths bypass observers and must call the service explicitly.

Authenticated `GET /api/service-repeat-rewards` returns only the caller's progress and rewards, with private/no-store caching. Mobile and desktop show progress in the account and booking confirmation; available rewards are selectable only for matching services. Send optional `services.*.subServices.*.repeat_reward_id` to `POST /api/mobile/cart/bookings`. Other client price/reward marker fields are ignored.

Deployment, from the Laravel folder:

```sh
php artisan migrate --path=database/migrations/2026_10_24_000001_create_service_repeat_rewards.php
php artisan migrate --path=database/migrations/2026_10_24_000002_add_repeat_reward_evaluation_marker.php
php -d opcache.enable_cli=0 vendor/bin/phpunit tests/Feature/ServiceRepeatRewardsTest.php
```

Publish the rebuilt frontend `dist` as usual. Tests use in-memory SQLite, assert that target before schema writes, and never migrate the configured application database. No notification messages are sent by this feature.
