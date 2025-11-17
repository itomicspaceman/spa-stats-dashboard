# Database Permissions Setup for Venue Categorization

## Current Status

The current database user `squahliv_cursor` has **READ-ONLY** access to the `squahliv_db` database on `atlas.itomic.com`.

## Required Permissions

To enable the venue categorization system, we need:

1. **CREATE TABLE** permission to create the `venue_category_updates` audit log table
2. **ALTER TABLE** permission to add indexes to the `venue_category_updates` table
3. **UPDATE** permission on the `venues` table to update:
   - `category_id` field
   - `updated_at` timestamp
4. **INSERT** permission on the `venue_category_updates` table to log all changes

## Option 1: Create New User with Write Access (Recommended)

Create a new database user specifically for the categorization system:

```sql
-- On atlas.itomic.com MySQL server
CREATE USER 'squahliv_categorizer'@'%' IDENTIFIED BY 'SECURE_PASSWORD_HERE';

-- Grant specific permissions needed
GRANT SELECT, UPDATE ON squahliv_db.venues TO 'squahliv_categorizer'@'%';
GRANT SELECT ON squahliv_db.venue_categories TO 'squahliv_categorizer'@'%';
GRANT SELECT ON squahliv_db.countries TO 'squahliv_categorizer'@'%';
GRANT CREATE, ALTER, SELECT, INSERT ON squahliv_db.venue_category_updates TO 'squahliv_categorizer'@'%';

FLUSH PRIVILEGES;
```

Then update your `.env` file:

```env
# Add new connection for categorization
SQUASH_CATEGORIZER_DB_HOST=atlas.itomic.com
SQUASH_CATEGORIZER_DB_PORT=3306
SQUASH_CATEGORIZER_DB_DATABASE=squahliv_db
SQUASH_CATEGORIZER_DB_USERNAME=squahliv_categorizer
SQUASH_CATEGORIZER_DB_PASSWORD=SECURE_PASSWORD_HERE
```

And update `config/database.php` to add the new connection:

```php
'squash_categorizer' => [
    'driver' => 'mysql',
    'host' => env('SQUASH_CATEGORIZER_DB_HOST', 'atlas.itomic.com'),
    'port' => env('SQUASH_CATEGORIZER_DB_PORT', '3306'),
    'database' => env('SQUASH_CATEGORIZER_DB_DATABASE', 'squahliv_db'),
    'username' => env('SQUASH_CATEGORIZER_DB_USERNAME', 'squahliv_categorizer'),
    'password' => env('SQUASH_CATEGORIZER_DB_PASSWORD', ''),
    'unix_socket' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => false,
    'engine' => null,
    'options' => [],
],
```

## Option 2: Grant Write Permissions to Existing User

Alternatively, grant write permissions to the existing `squahliv_cursor` user:

```sql
-- On atlas.itomic.com MySQL server
GRANT UPDATE ON squahliv_db.venues TO 'squahliv_cursor'@'%';
GRANT CREATE, ALTER, INSERT ON squahliv_db.venue_category_updates TO 'squahliv_cursor'@'%';

FLUSH PRIVILEGES;
```

**Note:** This is less secure as it gives the read-only user write access.

## Running the Migration

Once permissions are granted, run the migration:

```bash
php artisan migrate --path=database/migrations/2025_11_14_101514_create_venue_category_updates_table.php
```

## Testing the System

After permissions are set up:

1. **Test with dry-run** (no database writes needed):
   ```bash
   php artisan venues:categorize --batch=5 --dry-run
   ```

2. **Test with actual updates** (requires write permissions):
   ```bash
   php artisan venues:categorize --batch=5
   ```

3. **Process larger batches**:
   ```bash
   php artisan venues:categorize --batch=50
   ```

## Current Test Results

The dry-run test successfully categorized 5 venues:
- 4 venues → "Dedicated facility" (sports_complex type)
- 1 venue → "Hotel or resort" (resort_hotel type)
- All with HIGH or MEDIUM confidence
- No AI fallback needed (Google Places mapping was sufficient)

## Security Considerations

- Use strong passwords for any new database users
- Limit permissions to only what's needed (principle of least privilege)
- Consider using separate users for read vs. write operations
- Log all database changes via the `venue_category_updates` audit table
- Regularly review audit logs for unexpected changes

## Contact

For database access on `atlas.itomic.com`, contact the database administrator.

