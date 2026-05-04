# Gratitude System Documentation

## Overview

The Gratitude module is a loyalty and rewards subsystem for tracking member accounts, earned journey points, bonus points, redemptions, cancellations, levels, benefits, and reserve data.

Accounts are identified by `gratitudeNumber`. The `gratitudes` table does not store a `user_id`; point records may optionally store `user_id`, but the account-level lookup is always by `gratitudeNumber`.

The system has three access layers:

- Authenticated web pages under `/gratitude`.
- Authenticated internal APIs under `/internal-api/gratitude`.
- External bearer-token APIs under `/api/v1/gratitude`.

## Main Files

- Web routes: `routes/gratitude/web.php`
- Internal API routes: `routes/gratitude/internal-api.php`
- External API routes: `routes/gratitude/external-api.php`
- External API controller: `app/Http/Controllers/Api/Gratitude/GratitudeController.php`
- Internal API controller: `app/Http/Controllers/InternalApi/Gratitude/GratitudeController.php`
- Core service: `app/Services/Gratitude/GratitudeService.php`
- Tier service: `app/Services/Gratitude/TierService.php`
- Expiry service: `app/Services/Gratitude/PointExpiryService.php`
- External bearer middleware: `app/Http/Middleware/ValidateBearerToken.php`

## Authentication

### Web and Internal API

The web app and internal API are registered inside the authenticated and verified web route group in `routes/web.php`.

Internal API base path:

```text
/internal-api/gratitude
```

### External API

External APIs are registered under:

```text
/api/v1/gratitude
```

External requests use `ValidateBearerToken`. The token must be sent as:

```http
Authorization: Bearer {id}|{plain_text_secret}
```

The middleware:

1. Splits the bearer token into `{id}` and `{secret}`.
2. Looks up `personal_access_tokens.id` on the `auth_db` connection.
3. Hashes the plain text secret with SHA-256.
4. Compares the hash with the stored token using `hash_equals`.

External API routes are CSRF-exempt so third-party clients can POST with bearer-token auth.

## Core Domain Concepts

### Gratitude Account

Stored in `gratitudes`.

Important fields:

- `gratitudeNumber`: unique member account number.
- `level`: current level, for example `Explorer`, `Globetrotter`, or `Jetsetter`.
- `levelHistory`: JSON history of level changes.
- `level_obtained_at`: start date for the current membership interval.
- `systemLevelUpdate`: when true, tiers can be recalculated automatically.
- `status`, `is_active`: account state.
- Balance columns: `totalPoints`, `totalEarnedPoints`, `totalBonusPoints`, `totalExpiredPoints`, `totalCancelledPoints`, `totalRedeemedPoints`, `totalRemainingPoints`, `useablePoints`, `nonUseablePoints`.

### Gratitude Number Creation

`GratitudeService::generateGratitudeNumber()` creates numbers in the current `G####` style.

Example:

```text
G0879 -> G0880
```

If an external client provides `gratitudeNumber` or `gratitude_number`, the provided value is used after uniqueness validation. If no number is provided, the service generates the next `G####` number.

### Levels

Configured in `gratitude_levels` and seeded by `GratitudeLevelSeeder`.

Default levels:

- `Explorer`: minimum 0 points.
- `Globetrotter`: minimum 15001 points.
- `Jetsetter`: minimum 30001 points plus qualifying journey requirements.

Level configuration includes:

- Point thresholds.
- Redemption points-per-dollar rates.
- Partner redemption points-per-dollar rates.
- Earned and bonus expiry days.
- Membership interval years.
- Jetsetter journey-count and journey-duration requirements.
- Terms and level-specific rules.

### Earned Points

Stored in `earned_points`.

Earned points represent journey-related points and are the only points used for automatic tier qualification.

Important fields:

- `gratitudeNumber`
- `journey_id`
- `points`
- `redeemed_points`
- `cancelled_points`
- `remaining_points` generated column
- `amount`
- `date`
- `usable_date`
- `expires_at`
- `status`: typically `pending`, `active`, or `expired`
- `points_breakdown`
- `redemption_history`

### Bonus Points

Stored in `bonus_points`.

Bonus points contribute to account balance and redemption availability, but do not drive automatic level changes.

Important fields:

- `gratitudeNumber`
- `points`
- `redeemed_points`
- `cancelled_points`
- `remaining_points` generated column
- `date`
- `usable_date`
- `expires_at`
- `status`
- `description`
- `redemption_history`

### Cancellations

Stored in `cancellations`.

Cancellations reduce remaining available points. A cancellation can target a specific earned or bonus point record, or it can allocate across available point batches using the same practical ordering as redemption-style point consumption.

Important fields:

- `gratitudeNumber`
- `points`
- `date`
- `description`
- `points_breakdown`: source allocations
- `status`

### Redemptions

Stored in `redeem_points` and `redeem_points_details`.

`redeem_points` is the master redemption record. `redeem_points_details` records which earned or bonus point batches were consumed.

The redemption queue uses active, usable, unexpired point batches ordered by:

1. Soonest expiry first.
2. Earliest effective date.
3. Earned points before bonus points when otherwise tied.
4. Lowest record ID as final tie-breaker.

Redemption types:

- `journey`
- `partner`
- `other`

Journey and other redemptions use `redemption_points_per_dollar`. Partner redemptions use `partner_points_per_dollar`, falling back to `redemption_points_per_dollar`.

## Database Model Summary

| Table | Purpose |
| --- | --- |
| `gratitudes` | Member account, current balances, level, status, level history. |
| `earned_points` | Journey-earned point batches, including expiry, redemption, cancellation, and tier eligibility data. |
| `bonus_points` | Bonus point batches, including expiry, redemption, and cancellation data. |
| `cancellations` | Cancellation or manual expiration records and allocation history. |
| `redeem_points` | Redemption master records. |
| `redeem_points_details` | Polymorphic allocations from redemptions to earned or bonus point batches. |
| `gratitude_levels` | Level thresholds, expiry rules, redemption rates, and terms. |
| `gratitude_benefits` | Benefits available in the program. |
| `benefit_gratitude_level` | Pivot table configuring benefits per level. |
| `gratitude_reserves` | Reserve records for journey or reserve-related accounting. |

## Services

### `GratitudeService`

Primary account and balance service.

Key methods:

- `createAccount(array $data = [])`: creates a gratitude account with zero balances and an initial level history.
- `generateGratitudeNumber(string $prefix = 'G')`: generates the next `G####` number.
- `import(array $data, array $journeysMap = [])`: imports legacy account and point data.
- `allGratitudes()`: returns all accounts.
- `redeemPoints($gratitudeNumber, $data, $points)`: redeems points using FIFO-style soonest-expiring allocation.
- `syncAccountBalance($gratitudeNumber)`: recalculates account totals and triggers tier recalculation when allowed.
- `gratitudeDataByNumber(string $gratitudeNumber)`: returns account data plus point and redemption history.

### `EarnedPointService`

Creates, updates, and deletes earned journey points. It calculates expiry using the account level and syncs balances after writes.

### `BonusPointService`

Creates, updates, and deletes bonus points. It calculates expiry using the account level and syncs balances after writes.

### `CancellationService`

Creates and deletes cancellations. It supports:

- Cancelling one specific earned point record.
- Cancelling one specific bonus point record.
- Cancelling across the available point queue when no source record is specified.

### `PointExpiryService`

Resolves level expiry settings and calculates point expiry dates.

Default expiry:

```text
730 days
```

### `TierService`

Recalculates tiers based on earned journey points in the current membership interval.

Rules:

- Only earned journey points count toward automatic level changes.
- Bonus points and redemptions do not count toward tier qualification.
- Cancellations reduce tier-qualifying earned points.
- Members can upgrade before the interval ends.
- Downgrades happen after the interval expires.
- If `systemLevelUpdate` is false, automatic recalculation is skipped.
- Jetsetter also requires qualifying journey count and duration.

### `GratitudeBenefitsService`

Manages levels, benefits, and level-benefit pivot configuration. It also checks whether a level has an active benefit by `benefit_key`.

### `PointService`

Used by scheduled commands and tests for pending point activation, direct bonus creation, legacy-style redemption, and point expiry.

## Balance Calculation

`GratitudeService::syncAccountBalance()` recalculates balances from source tables.

The main calculations are:

- `totalEarnedPoints`: sum of `earned_points.points`.
- `totalBonusPoints`: sum of `bonus_points.points`.
- `totalPoints`: earned plus bonus.
- `totalCancelledPoints`: sum of `cancellations.points`.
- `totalRedeemedPoints`: sum of `redeem_points.points`.
- `totalExpiredPoints`: remaining points from expired earned and bonus batches.
- `useablePoints`: active, usable, unexpired remaining points.
- `totalRemainingPoints`: total points minus redeemed, cancelled, and expired.
- `nonUseablePoints`: total points minus usable points.

After syncing, the account `last_activity_at` is updated. If `systemLevelUpdate` is true, `TierService::recalculateTier()` runs.

## Main Workflows

### 1. Create Account

Entry point:

```text
POST /api/v1/gratitude
```

Flow:

1. Validate optional account data.
2. Ensure `gratitudeNumber` and `gratitude_number` match if both are supplied.
3. Generate a `G####` number if none is supplied.
4. Create the account with zero balances.
5. Set the default active level, usually `Explorer`.
6. Add the initial level history entry.

### 2. Add Earned Points

Entry points:

```text
POST /api/v1/gratitude/{gratitudeNumber}/earned
POST /internal-api/gratitude/{gratitudeNumber}/earned
```

Required payload:

```json
{
  "date": "2026-04-30",
  "category": "journey",
  "points": 1500,
  "amount": 1000,
  "description": "Journey points",
  "journey_id": 123
}
```

Flow:

1. Load the account by `gratitudeNumber`.
2. Create an active earned point batch.
3. Set `usable_date` from `date`.
4. Calculate `expires_at` from the current level's earned expiry days.
5. Sync account balance.
6. Recalculate tier when automatic tier updates are enabled.

### 3. Add Bonus Points

Entry points:

```text
POST /api/v1/gratitude/{gratitudeNumber}/bonus
POST /internal-api/gratitude/{gratitudeNumber}/bonus
```

Required payload:

```json
{
  "date": "2026-04-30",
  "description": "Promotional bonus",
  "points": 500
}
```

Flow:

1. Load the account by `gratitudeNumber`.
2. Create an active bonus point batch.
3. Calculate `expires_at` from the current level's bonus expiry days.
4. Sync account balance.

Bonus points do not drive automatic tier upgrades.

### 4. Redeem Points

Entry points:

```text
POST /api/v1/gratitude/{gratitudeNumber}/redeem
POST /internal-api/gratitude/{gratitudeNumber}/redeem
```

Payload:

```json
{
  "points": 1000,
  "amount": 33.33,
  "reason": "Partner redemption",
  "redemption_type": "partner",
  "journey_id": null
}
```

Flow:

1. Lock the account for update.
2. Validate benefit access if `benefit_key` is supplied.
3. Resolve the redemption rate from the current level.
4. Build a queue of active, usable, unexpired point batches.
5. Check available points.
6. Create `redeem_points`.
7. Deduct from source batches and update `redeemed_points`.
8. Create `redeem_points_details` records.
9. Append redemption history to source batches.
10. Sync account balance.

### 5. Cancel Points

Entry points:

```text
POST /api/v1/gratitude/{gratitudeNumber}/cancel
POST /internal-api/gratitude/{gratitudeNumber}/cancel
```

Payload:

```json
{
  "date": "2026-04-30",
  "cancellation_reason": "Journey adjustment",
  "cancellation_points": 250,
  "earned_point_id": 10,
  "bonus_point_id": null
}
```

Flow:

1. Create a cancellation record.
2. If a source point ID is supplied, cancel from that source only.
3. If no source is supplied, allocate cancellation across available point batches.
4. Store the allocation in `points_breakdown`.
5. Sync account balance.

### 6. Delete Redemptions and Cancellations

Deleting a redemption restores redeemed points to the original source batches, removes detail records, and syncs balances.

Deleting a cancellation reduces `cancelled_points` on affected source batches, clears `cancel_id` where relevant, and syncs balances.

### 7. Import Legacy Data

Entry points:

```text
GET /internal-api/gratitude/migrate-data
POST /gratitude/import
```

The import process:

- Upserts accounts by `old_id`.
- Imports cancellations before point batches so cancellation references can be resolved.
- Imports earned, bonus, and redemption records.
- Converts negative non-expiry point rows into cancellation records.
- Skips legacy negative expiration rows and allows expiry calculations to handle them.
- Syncs account balances after import.

## API Reference

### External API

Base path:

```text
/api/v1/gratitude
```

All external routes require bearer-token auth.

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/all` | List all gratitude accounts. |
| `POST` | `/` | Create a gratitude account. |
| `GET` | `/{gratitudeNumber}` | Show account data. |
| `POST` | `/{gratitudeNumber}/earned` | Add earned points. |
| `PUT` | `/{gratitudeNumber}/earned/{id}` | Update earned points. |
| `DELETE` | `/{gratitudeNumber}/earned/{id}` | Delete earned points. |
| `POST` | `/{gratitudeNumber}/bonus` | Add bonus points. |
| `PUT` | `/{gratitudeNumber}/bonus/{id}` | Update bonus points. |
| `DELETE` | `/{gratitudeNumber}/bonus/{id}` | Delete bonus points. |
| `POST` | `/{gratitudeNumber}/cancel` | Cancel points. |
| `DELETE` | `/{gratitudeNumber}/cancel/{id}` | Delete cancellation. |
| `POST` | `/{gratitudeNumber}/redeem` | Redeem points. |
| `PUT` | `/{gratitudeNumber}/redeem/{id}` | Update redemption metadata. |
| `DELETE` | `/{gratitudeNumber}/redeem/{id}` | Delete redemption and restore points. |

### Create Account Example

Auto-generate the next account number:

```http
POST /api/v1/gratitude
Authorization: Bearer 1|plain-text-secret
Content-Type: application/json
```

```json
{}
```

Create with an explicit account number:

```json
{
  "gratitude_number": "G0880",
  "level": "Explorer",
  "status": "active"
}
```

Successful response:

```json
{
  "message": "Gratitude account created",
  "gratitude": {
    "gratitudeNumber": "G0880",
    "level": "Explorer",
    "totalPoints": 0,
    "useablePoints": 0
  }
}
```

### Internal API

Base path:

```text
/internal-api/gratitude
```

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/` | List account summaries. |
| `GET` | `/overview` | Program totals and dashboard metrics. |
| `GET` | `/reserve` | Reserve records. |
| `GET` | `/history` | Current user's point history. |
| `GET` | `/account/show/{gratitudeNumber}` | Full account details for the UI. |
| `POST` | `/{gratitudeNumber}/earned` | Add earned points. |
| `PUT` | `/{gratitudeNumber}/earned/{id}` | Update earned points. |
| `DELETE` | `/{gratitudeNumber}/earned/{id}` | Delete earned points. |
| `POST` | `/{gratitudeNumber}/bonus` | Add bonus points. |
| `PUT` | `/{gratitudeNumber}/bonus/{id}` | Update bonus points. |
| `DELETE` | `/{gratitudeNumber}/bonus/{id}` | Delete bonus points. |
| `POST` | `/{gratitudeNumber}/cancel` | Cancel points. |
| `DELETE` | `/{gratitudeNumber}/cancel/{id}` | Delete cancellation. |
| `POST` | `/{gratitudeNumber}/expire` | Manually expire points through cancellation flow. |
| `POST` | `/{gratitudeNumber}/sync-balance` | Recalculate account balances. |
| `POST` | `/{gratitudeNumber}/redeem` | Redeem points. |
| `GET` | `/{gratitudeNumber}/redeem/{id}` | Show a redemption with source details. |
| `PUT` | `/{gratitudeNumber}/redeem/{id}` | Update redemption metadata. |
| `DELETE` | `/{gratitudeNumber}/redeem/{id}` | Delete redemption and restore points. |
| `GET` | `/levels` | List levels. |
| `POST` | `/levels` | Create level. |
| `PUT` | `/levels/{level}` | Update level. |
| `DELETE` | `/levels/{level}` | Delete level. |
| `GET` | `/benefits` | List benefits. |
| `POST` | `/benefits` | Create benefit. |
| `PUT` | `/benefits/{benefit}` | Update benefit. |
| `DELETE` | `/benefits/{benefit}` | Delete benefit. |
| `GET` | `/program-benefits` | Show level-benefit grid. |
| `PUT` | `/program-benefits/{benefit}` | Update level-benefit pivot configuration. |

## Scheduled Jobs

Scheduled in `routes/console.php`.

| Command | Schedule | Purpose |
| --- | --- | --- |
| `gratitude:activate-points` | Daily | Activates pending earned point batches whose `usable_date` has arrived. |
| `gratitude:expire-points` | Daily | Marks expired earned and bonus point batches. |
| `gratitude:check-inactivity` | Daily | Flags accounts as inactive when they have no recent journey activity and no bonus balance. |

## Web Pages

Authenticated users can access:

| Route | Purpose |
| --- | --- |
| `/gratitude` | Overview page. |
| `/gratitude/accounts` | Account list. |
| `/gratitude/reserve` | Reserve view. |
| `/gratitude/history` | History view. |
| `/gratitude/levels` | Level management. |
| `/gratitude/benefits` | Benefit management. |
| `/gratitude/program-level-benefits` | Benefit grid by level. |
| `/gratitude/account/show/{gratitudeNumber}` | Account detail page. |

## Activity Logging

Most Gratitude models use `Spatie\Activitylog\Traits\LogsActivity` with `LogOptions::defaults()->logAll()`. This means model changes are tracked by Spatie activity logs.

## Operational Notes

- Always call `GratitudeService::syncAccountBalance($gratitudeNumber)` after any change that affects points, cancellations, redemptions, or expiry state.
- Use `systemLevelUpdate = false` for manual level overrides that must not be overwritten by automatic recalculation.
- `gratitudeNumber` is the account key used across the module.
- External clients should prefer `POST /api/v1/gratitude` with no body when they want the system to allocate the next `G####` number.
- Redemptions and cancellations should run inside transactions because they update multiple records.
- Expiry is based on the level configuration at the time the point record is created or updated.

## Testing

The focused Gratitude test suite is:

```bash
php artisan test tests/Feature/GratitudeServiceTest.php
```

The suite covers:

- Account creation and gratitude number generation.
- External account creation endpoint.
- Pending point activation.
- FIFO-style redemption allocation.
- Tier upgrades and downgrade timing.
- Partial cancellation behavior.
- Partner redemption rates.
- Legacy import handling for negative point rows.
