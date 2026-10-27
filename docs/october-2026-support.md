# October 2026 support changes

- Customer registration validates plain-text names and phone numbers on registration, booking and purchase entry points. Registration/login and booking/purchase requests are rate limited. The standalone signup form also includes a honeypot.
- New customer notifications go to info@dreamgym.pt. Booking confirmation and cancellation emails copy that address. A persisted `suppress_notifications` flag supports internal booking regularizations, including delayed lock email retries. Past bookings do not provision new door codes.
- Credit movements are recorded prospectively from 27 October: booking debits, cancellation refunds, pack purchases/expiry, membership recalculation and manual adjustments. Historical balances are not fabricated into a transaction history. The account and admin user relation show the ledger; customer queries are scoped to the authenticated user.
- Manual balance edits require a reason, run under a user lock and record the actor. Membership recalculation replays dated manual adjustments; pack reductions reconcile lots immediately. They send no email.
- Cancellation timestamps are visible on account desktop/mobile and the admin booking form/table. Refund cutoff is consistently 12 hours.

## Verification

The October feature suite covers rejected signup data and throttling, the 12–24 hour refund regression, late cancellations, customer isolation, email copying/suppression, manual adjustment history and reconciliation, and idempotent historical refund repair.

`support:audit-october` inspects suspected HTML names and affected balances. By default it does not modify data. `--repair-refunds` only changes a balance when it exactly matches the old 24-hour calculation and the corrected calculation is higher. It preserves the existing membership expiry and records the credit correction. `--test-mail` sends a clearly labelled test to the gym; transport acceptance is not proof of inbox delivery. `--output` saves a private report (0600).

Do not leave the one-time audit/repair or test mail in recurring deployment tasks after verification. Do not delete suspicious accounts as part of this workflow; preserve evidence for investigation.
