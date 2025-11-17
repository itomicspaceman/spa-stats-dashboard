# Venue Categorization System

An AI-powered system to automatically categorize squash venues using Google Places (New) API and OpenAI GPT-4.

## Overview

This system categorizes 4,470 venues currently marked as "Don't know" (and optionally 39 "Other" venues) by:
1. Fetching place details from Google Places (New) API
2. Mapping Google Places types to venue categories
3. Using AI (GPT-4) as fallback for ambiguous cases
4. Tracking potential new category suggestions

## Architecture

### Services

1. **GooglePlacesService** (`app/Services/GooglePlacesService.php`)
   - Fetches place details from Google Places (New) API
   - Returns types, primaryType, displayName, address, etc.

2. **GooglePlacesTypeMapper** (`app/Services/GooglePlacesTypeMapper.php`)
   - Maps Google Places types to venue category IDs
   - Assigns confidence levels (HIGH/MEDIUM/LOW)
   - 30+ type mappings covering all venue categories

3. **OpenAICategorizer** (`app/Services/OpenAICategorizer.php`)
   - AI-powered categorization for low-confidence cases
   - Uses GPT-4 with structured prompts
   - Can suggest new categories if none fit

4. **VenueCategorizer** (`app/Services/VenueCategorizer.php`)
   - Main orchestration service
   - Processes venues in batches
   - Coordinates Google Places → Mapper → AI flow

5. **VenueCategoryUpdater** (`app/Services/VenueCategoryUpdater.php`)
   - Handles database updates with audit logging
   - Batch update support
   - Statistics and reporting

6. **NewCategoryDetector** (`app/Services/NewCategoryDetector.php`)
   - Tracks unmapped Google Places types
   - Collects AI category suggestions
   - Generates reports for review

### Database

**Table: `venue_category_updates`**
- Audit log for all category changes
- Stores Google Places types, confidence, reasoning
- Tracks source (GOOGLE_MAPPING, OPENAI, MANUAL)

## Usage

### Basic Commands

```bash
# Dry run - preview recommendations without database changes
php artisan venues:categorize --batch=5 --dry-run

# Process 5 venues with database updates
php artisan venues:categorize --batch=5

# Process 50 venues per day (recommended production rate)
php artisan venues:categorize --batch=50

# Include "Other" category venues
php artisan venues:categorize --batch=10 --include-other

# Only update HIGH confidence recommendations
php artisan venues:categorize --batch=20 --min-confidence=HIGH

# Disable AI fallback (Google Places mapping only)
php artisan venues:categorize --batch=10 --no-ai
```

### Command Options

- `--batch=N` - Number of venues to process (default: 5)
- `--dry-run` - Preview without database changes
- `--include-other` - Also process "Other" category venues
- `--min-confidence=LEVEL` - Minimum confidence to auto-update (HIGH/MEDIUM/LOW, default: MEDIUM)
- `--no-ai` - Disable AI fallback for low confidence cases

## Configuration

### Environment Variables

Add to your `.env` file:

```env
# Google Places API (New)
GOOGLE_PLACES_API_KEY=your_api_key_here

# OpenAI API
OPENAI_API_KEY=your_api_key_here
```

### API Keys

1. **Google Places API**: Get from [Google Cloud Console](https://console.cloud.google.com/)
   - Enable "Places API (New)"
   - Cost: ~$0.017 per request

2. **OpenAI API**: Get from [OpenAI Platform](https://platform.openai.com/)
   - Uses GPT-4 model
   - Cost: ~$0.01 per categorization

## Cost Management

### Estimated Costs

**For 4,509 venues:**
- Google Places API: 4,509 × $0.017 = **~$76.65**
- OpenAI API (20% need AI): 900 × $0.01 = **~$9.00**
- **Total: ~$85.65**

### Rate Limiting

- 1-second delay between API calls (built-in)
- Process 50 venues per day = **~90 days** to complete
- Daily cost: ~$0.85 (Google) + ~$0.10 (OpenAI) = **~$0.95/day**

## Testing Results

### Dry Run Test (5 venues)

```
✅ All 5 venues successfully categorized
✅ 4 → "Dedicated facility" (sports_complex)
✅ 1 → "Hotel or resort" (resort_hotel)
✅ All HIGH or MEDIUM confidence
✅ No AI fallback needed
```

**Sample Output:**
```
Venue #1110: Centre Esportiu dels Serradells
Primary Type: sports_complex
Recommended Category: Dedicated facility (ID: 5)
Confidence: HIGH
Source: GOOGLE_MAPPING
Reasoning: Matched primary type: sports_complex
```

## Category Mappings

### Google Places → Venue Categories

**Important Note:** "Dedicated facility" means dedicated to **squash only**. General sports facilities map to "Leisure centre" instead.

| Google Places Type | Venue Category | Confidence | Notes |
|-------------------|----------------|------------|-------|
| `gym`, `fitness_center`, `health_club` | Gym or health & fitness centre | HIGH | |
| `sports_complex`, `sports_club` | Leisure centre | MEDIUM | Multi-sport facilities |
| `recreation_center` | Leisure centre | HIGH | |
| `stadium`, `athletic_field` | Leisure centre | LOW | General sports venues |
| `hotel`, `resort_hotel` | Hotel or resort | HIGH | |
| `school`, `primary_school`, `secondary_school` | School | HIGH | |
| `university`, `college` | College or university | HIGH | |
| `community_center` | Community hall | HIGH | |
| `private_club` | Private club | HIGH | |
| `country_club` | Country club | HIGH | |
| `military_base` | Military | HIGH | |
| `shopping_mall` | Shopping centre | HIGH | |
| `office_building` | Business complex | MEDIUM | |

**Note:** "Dedicated facility" (ID 5) is intentionally not mapped to any Google Places types, as Google doesn't have a "squash-specific" type. These venues will require AI evaluation to determine if they're squash-only facilities.

See `GooglePlacesTypeMapper.php` for complete mapping table.

## New Category Detection

The system automatically tracks:
1. **Unmapped Google Places types** - Types that don't map to existing categories
2. **AI suggestions** - New categories suggested by GPT-4

Reports saved to: `storage/logs/suggested-new-categories.json`

### Example Report

```json
{
  "generated_at": "2025-11-14T10:15:00Z",
  "unmapped_types": [
    {
      "primary_type": "wellness_center",
      "venue_count": 15,
      "related_types": ["spa", "wellness_center", "health"],
      "sample_venues": [...]
    }
  ],
  "ai_suggested_categories": [
    {
      "suggested_category_name": "Wellness & Spa Center",
      "venue_count": 12,
      "google_types": ["wellness_center", "spa"],
      "sample_venues": [...]
    }
  ]
}
```

## Database Permissions

⚠️ **Important:** Database write permissions are required for production use.

See `DATABASE-PERMISSIONS-SETUP.md` for detailed setup instructions.

Current status:
- ✅ Dry-run mode works (no permissions needed)
- ❌ Database updates require CREATE, UPDATE, INSERT permissions
- 📝 Migration pending: `venue_category_updates` table

## Workflow

### Phase 1: Testing (Days 1-3)
```bash
# Day 1: Test with 5 venues
php artisan venues:categorize --batch=5 --dry-run

# Day 2-3: Small batches with updates
php artisan venues:categorize --batch=5
```

### Phase 2: Ramp Up (Days 4-14)
```bash
# Increase to 10 venues per day
php artisan venues:categorize --batch=10
```

### Phase 3: Production (Days 15+)
```bash
# Process 50 venues per day
php artisan venues:categorize --batch=50
```

### Phase 4: Review
- Check `storage/logs/suggested-new-categories.json`
- Review low-confidence assignments
- Add new categories if needed
- Re-process "Other" category venues

## Monitoring

### View Statistics

```bash
# Check how many venues need categorization
php artisan venues:categorize --batch=0 --dry-run
```

### Audit Log Queries

```sql
-- View recent updates
SELECT * FROM venue_category_updates 
ORDER BY created_at DESC 
LIMIT 20;

-- Count by confidence level
SELECT confidence_level, COUNT(*) 
FROM venue_category_updates 
GROUP BY confidence_level;

-- Count by source
SELECT source, COUNT(*) 
FROM venue_category_updates 
GROUP BY source;
```

## Troubleshooting

### Google Places API Errors

```bash
# Test API connection
php artisan tinker
>>> app(App\Services\GooglePlacesService::class)->testConnection()
```

### OpenAI API Errors

```bash
# Check if API key is configured
php artisan tinker
>>> config('services.openai.api_key')
```

### Database Permission Errors

See `DATABASE-PERMISSIONS-SETUP.md` for permission setup.

## Files Created

### Services
- `app/Services/GooglePlacesService.php`
- `app/Services/GooglePlacesTypeMapper.php`
- `app/Services/OpenAICategorizer.php`
- `app/Services/VenueCategorizer.php`
- `app/Services/VenueCategoryUpdater.php`
- `app/Services/NewCategoryDetector.php`

### Commands
- `app/Console/Commands/CategorizeVenues.php`

### Migrations
- `database/migrations/2025_11_14_101514_create_venue_category_updates_table.php`

### Configuration
- `config/services.php` - Added Google Places and OpenAI config

### Documentation
- `VENUE-CATEGORIZATION-SYSTEM.md` (this file)
- `DATABASE-PERMISSIONS-SETUP.md`

## Next Steps

1. ✅ Set up database write permissions (see `DATABASE-PERMISSIONS-SETUP.md`)
2. ✅ Run migration to create audit log table
3. ✅ Test with 5 venues (actual database updates)
4. ✅ Review results and adjust confidence thresholds if needed
5. ✅ Scale up to 50 venues per day
6. ✅ Monitor for new category suggestions
7. ✅ Complete all 4,509 venues over ~90 days

## Support

For questions or issues:
- Check logs: `storage/logs/laravel.log`
- Review audit trail: `venue_category_updates` table
- Check new category suggestions: `storage/logs/suggested-new-categories.json`

## License

Proprietary - © 2025 Itomic Apps

