# Lab users without Doctrine fixtures

Preferred path:

```bash
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate
```

If fixtures cannot run in your environment (dependency or environment constraints), insert equivalent rows directly:

1. Inspect the live schema—columns must match [`User` entity](../src/Entity/User.php) and current migrations.
2. Generate an Argon2 password hash inside the PHP container:

   ```bash
   docker compose exec php php bin/console security:hash-password
   ```

3. Insert users with SQL appropriate for your PostgreSQL version (UUID generation via `gen_random_uuid()` where available). Assign roles as JSON compatible with how Symfony reads `roles` on the entity.

Keep passwords and hashes out of version control; rotate lab credentials before any shared deployment.
