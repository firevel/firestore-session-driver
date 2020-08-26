# Firevel - Firestore session driver
Firestore session driver for [Laravel](https://www.laravel.com) and [Firevel](https://www.firevel.com) compatible with Google App Engine standard environment (PHP 7.3).

## Installation
1) Install package with `composer require firevel/firestore-session-driver`

2) Update your app.yaml with:
```
env_variables:
  SESSION_DRIVER: firestore
```

## Limitations
Check [Firestore Quotas and Limits](https://cloud.google.com/firestore/quotas).

## High availability applications
If you like to use this driver in high load applications, watch garbage collection that can be a bottleneck.

Modify `config.sessions.lottery` value to set how often garbage collection should happen, and app.yaml `SESSION_GC_BATCH_SIZE` to define garbage collection batch size.

If extremal cases do manual garbage collection from cron job, or shift to `cookie` driver.
