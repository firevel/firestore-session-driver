# Firevel – Firestore Session Driver

A Firestore-backed session driver for [Laravel](https://www.laravel.com), designed to run smoothly on **Google App Engine (Standard environment)**.

## Installation

```bash
composer require firevel/firestore-session-driver
```

## Configuration

### 1) Set the session driver

**App Engine (app.yaml):**
```yaml
env_variables:
  SESSION_DRIVER: firestore
  # Optional: tune GC batch size (see "Garbage collection & scale")
  # SESSION_GC_BATCH_SIZE: 500
```

**Local development (.env):**
```env
SESSION_DRIVER=firestore
# SESSION_GC_BATCH_SIZE=500
```

> Firestore credentials on App Engine Standard are picked up via Application Default Credentials. For local development, set `GOOGLE_APPLICATION_CREDENTIALS` if needed.

### 2) (Optional) Session lifetime

Configure the session lifetime as usual in `config/session.php` or via `.env`:

```env
SESSION_LIFETIME=120
```

## Garbage collection & scale

Firestore session cleanup happens via Laravel’s session **lottery**. On very high-traffic apps this can be a bottleneck. You can tune or offload it:

- **Adjust the lottery** in `config/session.php` (note the correct path/key):
  ```php
  'lottery' => [2, 100], // e.g., 2% chance per request
  ```
- **Batch size**: control how many expired sessions are removed per GC pass:
  ```env
  SESSION_GC_BATCH_SIZE=500
  ```
- **Heavy load / HA setups**: consider moving GC out of request flow. Run cleanup on a schedule (cron/Scheduler) and set a very low lottery, or temporarily switch to the `cookie` driver if GC becomes a hotspot.

> In extreme cases, run garbage collection from a scheduled job/cron and reduce the in-request lottery to near zero.

## Limitations

- Review Firestore’s quotas and limits before deploying high-throughput workloads: <https://cloud.google.com/firestore/quotas>
