# Databases

This foundation application is configured to use a single MySQL database by default.
However, integrating additional databases is straightforward.
Follow the instructions below to define multiple databases in the `config/core/databases.yml` file.

## Configuring Additional Databases

Here's an example configuration for adding another database:

```yaml
# Databases
databases:
  # Existing configurations...
  second:
    driver: mysql
    host: 10.0.11.2
    port: 3306
    database: DATABASE_NAME
    username: root
    password: ~ # Use the configured MySQL root password
```

## Using Additional Databases in Code

Once configured, you can access the additional database in your code like this:

```php
/** @var \App\Shared\CharcoalApp $app */
$app->databases->getDb("second");
```

## Optional: Enhancing Type Safety

For stricter typing, you can modify the [`App\Shared\Core\Db\Database`](../../../src/shared/Core/Db/Database.php) enum
and
the [`App\Shared\Core\Db\Databases`](../../../src/shared/Core/Db/Databases.php) class to include the newly configured
databases.
This approach reduces reliance on string-based identifiers and improves code maintainability.
