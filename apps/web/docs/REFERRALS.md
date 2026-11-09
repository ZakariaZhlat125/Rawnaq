# Customer referral loyalty

Dashboard: Loyalty management -> Loyalty settings (/app/loyalty). referral_points is an integer from 0 to 1,000,000; default 0 disables awards. Uses existing view_loyalty/store_loyalty permissions.

Deploy the backend, then run from the Laravel directory:
    php artisan migrate --force
Deploy the rebuilt frontend dist as well. The migration adds unique referral codes to all existing users. New users receive a random SC-prefixed code from the User creating event. Referral fields are deliberately not mass assignable.

Registration /register accepts optional referral_code, normalizes case and whitespace, rejects unknown/inactive/banned owners, and stores it with the registration OTP. Verification creates the user and credits only the inviter inside one DB transaction. An account-creation lock serializes submissions for the same normalized mobile; a database lock serializes rewards to one inviter. referral_rewarded_at prevents repeat rewards, including when the configured amount was zero. Ledger source is referral and source_id is the newly created member ID. Affiliate referral attribution remains separate.

Registration now sends a random four-digit OTP instead of the previous fixed 1111 value, through the existing Taqnyat service. Configure SMS before live signup testing. Login OTP behavior is outside this change.

/profile, registration and login responses expose referral_code. Desktop and both mobile entry points offer the optional registration field and show a selectable referral code on My Account.

Validation: PHP lint, isolated ReferralServiceSmoke.php (model/transaction doubles; not a real database concurrency test), referral.browser.test.cjs (mock APIs), production build. Full Laravel migration/registration/SMS integration needs the nested vendor dependencies and database, unavailable in this workspace.
