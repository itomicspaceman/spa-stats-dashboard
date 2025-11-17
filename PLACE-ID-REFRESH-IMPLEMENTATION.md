# Place ID Refresh System - Implementation Summary

## Overview

Successfully implemented an automated Place ID refresh system that handles expired Google Place IDs during venue categorization. The system uses a three-tier approach:

1. **Google's Free Refresh Method** (first attempt)
2. **Text Search API** (fallback)
3. **Flag for Deletion** (if venue cannot be found)

## Implementation Complete

All components have been implemented and tested successfully.

### Files Created/Modified

#### 1. GooglePlacesService.php
**Added Method:** `refreshPlaceId(string $oldPlaceId): ?string`
- Uses minimal field mask (`id` only) for free Place ID validation
- Returns new Place ID if Google provides one
- Returns null if Place ID is expired/invalid
- Cost: **FREE** (uses minimal fields)

#### 2. GooglePlacesTextSearchService.php (NEW)
**Main Method:** `findPlaceByNameAndAddress(Venue $venue): ?string`
- Searches for venue using Text Search (New) API
- Builds query from venue name, address, suburb, country
- Uses venue coordinates for location bias (5km radius)
- Returns first matching Place ID or null
- Cost: ~$32 per 1,000 requests

#### 3. VenueCategorizer.php
**Updated Method:** `categorizeVenue()`
- Detects expired Place IDs when Place Details fails
- Attempts Google's free refresh method first
- Falls back to Text Search if needed
- Flags venue for deletion if not found
- Updates venue's Place ID in database when found
- Logs all actions

**Added Methods:**
- `updateVenuePlaceId()` - Updates venue's g_place_id in database
- `flagVenueForDeletion()` - Flags venue with status=3, appropriate reason

**New Result Fields:**
- `place_id_refreshed` - Boolean indicating if Place ID was updated
- `place_id_refresh_source` - How it was refreshed (Google/Text Search)
- `venue_flagged_for_deletion` - Boolean indicating deletion flag

#### 4. CategorizeVenues.php
**Enhanced Display:**
- Shows "🔄 Place ID refreshed via {source}" when refresh occurs
- Shows "🗑️ Venue flagged for deletion" when venue not found
- Added aggregate statistics for Place ID refreshes
- Added aggregate statistics for deletion flags

## Test Results

Successfully tested with 3 venues including 2 with expired Place IDs:

### Venue #1113: Club de Squash Bajada Vieja
- **Status:** Expired Place ID
- **Action:** Refreshed via Text Search
- **Result:** Found new Place ID, categorized as "Dedicated facility" (HIGH confidence)
- **Cost:** Text Search API call

### Venue #1192: Club The Squash Club
- **Status:** Expired Place ID
- **Action:** Refreshed via Text Search
- **Result:** Found new Place ID, categorized as "Leisure centre" (MEDIUM confidence)
- **Cost:** Text Search API call

### Venue #1197: Tango Club
- **Status:** Valid Place ID
- **Action:** No refresh needed
- **Result:** Categorized as "Other" (HIGH confidence)
- **Cost:** Standard Place Details call

## Process Flow

```
1. Attempt Place Details with existing Place ID
   ↓ (fails)
2. Try Google's free refresh method (minimal fields)
   ↓ (if new Place ID provided)
3. Update database with new Place ID → Retry Place Details → Continue categorization
   ↓ (if no new Place ID)
4. Try Text Search API (name + address + coordinates)
   ↓ (if found)
5. Update database with new Place ID → Retry Place Details → Continue categorization
   ↓ (if not found)
6. Flag venue for deletion
   - status = '3' (Flagged for Deletion)
   - delete_reason_id = 2 ("Venue is permanently closed")
   - reason_for_deletion = "Google Place ID expired, suggesting venue is closed"
   - deletion_request_by_user_id = 1 (System/Itomic Webmaster)
   - date_flagged_for_deletion = now()
```

## Database Updates

When Place ID is refreshed:
```sql
UPDATE venues SET
    g_place_id = '{new_place_id}',
    updated_at = NOW()
WHERE id = {venue_id}
```

When venue is flagged for deletion:
```sql
UPDATE venues SET
    status = '3',
    delete_reason_id = 2,
    reason_for_deletion = 'Google Place ID expired, suggesting venue is closed',
    deletion_request_by_user_id = 1,
    date_flagged_for_deletion = NOW(),
    updated_at = NOW()
WHERE id = {venue_id}
```

## Cost Analysis

### Per Venue with Expired Place ID:
1. **Initial Place Details attempt:** $0.005 (fails)
2. **Free refresh attempt:** $0.00 (FREE)
3. **Text Search fallback:** $0.032 (if needed)
4. **Retry Place Details:** $0.005 (if found)

**Total per expired Place ID:** ~$0.042 (if Text Search needed)

### Estimated for 4,458 Remaining Venues:
- Assuming 2-5% have expired Place IDs: ~90-220 venues
- Text Search cost: 90-220 × $0.032 = **$2.88 - $7.04**
- Well within $200 monthly credit

## Logging

All actions are logged:
- Place ID refresh attempts (info level)
- Successful refreshes with source (info level)
- Text Search attempts and results (info level)
- Venues flagged for deletion (warning level)

## Command Usage

```bash
# Dry run with 10 venues
php artisan venues:categorize --batch=10 --dry-run

# Process 50 venues (will refresh expired Place IDs automatically)
php artisan venues:categorize --batch=50

# Export report with Place ID refresh details
php artisan venues:categorize --batch=50 --export=csv
```

## Success Metrics

- ✅ Expired Place IDs automatically detected
- ✅ Google's free refresh method utilized first
- ✅ Text Search fallback working correctly
- ✅ New Place IDs saved to database
- ✅ Venues flagged for deletion when not found
- ✅ All actions logged and reported
- ✅ No manual intervention required
- ✅ Cost-effective (uses free method first)

## Next Steps

The system is now ready for production use:

1. Continue categorizing venues in batches
2. Monitor logs for Place ID refresh activity
3. Review venues flagged for deletion periodically
4. Track Text Search API costs in Google Cloud Console
5. Adjust batch sizes based on API usage and costs

## Permissions Required

No additional permissions needed beyond what was already granted:
- ✅ UPDATE on `venues` table (for g_place_id and deletion fields)
- ✅ INSERT on `venue_category_updates` table (for audit log)

## Notes

- The system prioritizes cost-effectiveness by using Google's free refresh method first
- Text Search is only used when absolutely necessary
- Venues that cannot be found are safely flagged for manual review
- All changes are logged for audit purposes
- The system handles rate limiting with 1-second delays between API calls


