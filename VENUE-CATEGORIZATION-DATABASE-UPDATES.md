# Venue Categorization: Database Updates Summary

## Overview

When the venue categorization system updates a venue's category, it performs the following database operations:

## Tables Updated

### 1. ✅ `venues` Table
**Columns Updated:**
- `category_id` - Changed from old category (usually 6 = "Don't know") to new category
- `updated_at` - Timestamp automatically updated to current time

**Purpose:** Primary venue data update

---

### 2. ✅ `venue_category_updates` Table (Custom Audit Log)
**New Record Inserted:**
```sql
INSERT INTO venue_category_updates (
    venue_id,
    old_category_id,
    new_category_id,
    google_place_types,      -- JSON: primary_type, types[], matched_type
    confidence_level,        -- HIGH, MEDIUM, or LOW
    reasoning,               -- Why this category was chosen
    source,                  -- GOOGLE_MAPPING or OPENAI
    created_at,
    created_by               -- 'Automated: AI Categorization System'
)
```

**Purpose:** Detailed audit trail specific to categorization with AI metadata

---

## Note: Laravel Nova Action Events

We **do not** update the `action_events` table (used by Laravel Nova) to avoid any potential interference with Nova's internal audit logging system. Our custom `venue_category_updates` table provides all the audit trail we need.

---

## Required Database Permissions

To perform these updates, the database user needs:

1. **UPDATE** on `venues` table (for `category_id` and `updated_at`)
2. **CREATE** permission (one-time, to create `venue_category_updates` table)
3. **INSERT** on `venue_category_updates` table

See `DATABASE-PERMISSIONS-SETUP.md` for full SQL commands.

---

## Transaction Safety

Both operations are wrapped in a **database transaction**:

```php
DB::connection('squash_remote')->beginTransaction();

try {
    // 1. Insert to venue_category_updates
    // 2. Update venues table
    
    DB::connection('squash_remote')->commit();
} catch (\Exception $e) {
    DB::connection('squash_remote')->rollBack();
    // All changes are reverted
}
```

**This ensures:**
- ✅ Both updates succeed together, or none at all
- ✅ No partial updates if something fails
- ✅ Database consistency is maintained

---

## Viewing Audit Logs

### Custom Categorization Log
```sql
-- View all categorization updates for a specific venue
SELECT * FROM venue_category_updates 
WHERE venue_id = 1109 
ORDER BY created_at DESC;

-- View all automated categorization updates
SELECT 
    v.name as venue_name,
    vcu.*
FROM venue_category_updates vcu
JOIN venues v ON v.id = vcu.venue_id
WHERE vcu.created_by = 'Automated: AI Categorization System'
ORDER BY vcu.created_at DESC;

-- Summary by confidence level
SELECT 
    confidence_level,
    COUNT(*) as count,
    source
FROM venue_category_updates
WHERE created_by = 'Automated: AI Categorization System'
GROUP BY confidence_level, source
ORDER BY confidence_level DESC;
```

---

## Example Update Flow

**Before:**
```
venues.category_id = 6 ("Don't know")
venues.updated_at = 2024-01-15 10:30:00
```

**After Categorization:**
```
venues.category_id = 2 ("Leisure centre")
venues.updated_at = 2025-11-14 15:45:23

+ venue_category_updates record created
+ action_events record created (user_id = 1)
```

---

## Summary

✅ **2 tables updated** per venue categorization  
✅ **Full audit trail** with AI reasoning and confidence  
✅ **Transaction-safe** - all or nothing  
✅ **Timestamps** automatically maintained  
✅ **No interference** with Laravel Nova's action_events  

No other tables or columns need to be updated.

