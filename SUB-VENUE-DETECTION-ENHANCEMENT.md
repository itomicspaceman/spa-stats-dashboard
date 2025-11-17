# Sub-Venue Detection Enhancement

## Problem Statement

The categorization system was correctly identifying venues like "Bulldogs Squash Club" as "Dedicated facility" based on their name, but this could be misleading in cases where:

1. **Sub-venue scenario**: The squash club has its own Google Place ID but is actually part of a larger leisure centre complex (e.g., "Bulldogs Sports Complex - Squash Club")
2. **Standalone scenario**: A dedicated squash facility that happens to be on the same campus as other buildings, but is still a standalone dedicated facility

This distinction is important because:
- A sub-venue should be categorized as the parent facility type (e.g., "Leisure centre")
- A standalone facility should remain as "Dedicated facility"

## Solution: VenueContextAnalyzer

We've implemented a new `VenueContextAnalyzer` service that uses multiple methods to detect sub-venue relationships:

### Method 1: Editorial Summary Analysis

Analyzes the Google Places `editorialSummary` field for indicators that the venue is part of a larger facility:

**High Confidence Indicators:**
- "part of", "located in", "within", "at the", "inside"
- Mentions of "sports complex", "recreation center", "leisure centre", "multi-sport"

**Medium Confidence Indicators:**
- "sports facility", "sports centre", "fitness center", "health club"

### Method 2: Co-Located Venue Detection

Checks our database for other venues at the same location:

1. **Exact Address Match**: Finds venues with identical address, suburb, and state
2. **Proximity Search**: Finds venues within 100 meters using coordinate-based search

If co-located venues are categorized as "Leisure centre" or "Gym", this suggests the venue may be a sub-venue.

### Method 3: Name Pattern Analysis

Analyzes venue names for patterns that suggest sub-venue status:

- **Pattern**: "Parent Facility - Squash Club" (e.g., "Sports Complex - Squash Club")
- **Pattern**: "Squash Club at Parent Facility" (e.g., "Squash Club at Sports Centre")
- **Multi-sport indicators**: Names containing both "squash" and multi-sport keywords (sports, recreation, leisure, fitness, multi, complex, centre/center)

## Category Adjustment Logic

When a sub-venue relationship is detected:

1. **If recommended category is "Dedicated facility" (ID 5)** and parent is "leisure_centre":
   - **Adjust to**: "Leisure centre" (ID 2)
   - **Confidence**: Based on context analysis confidence
   - **Reasoning**: Includes original reasoning plus context explanation

2. **If recommended category is "Dedicated facility" (ID 5)** and parent is "gym":
   - **Adjust to**: "Gym or health & fitness centre" (ID 4)
   - **Confidence**: Based on context analysis confidence
   - **Reasoning**: Includes original reasoning plus context explanation

3. **Other cases**: Keep original category but may lower confidence

## Integration

The `VenueContextAnalyzer` is integrated into the `VenueCategorizer` workflow:

1. **Step 1**: Fetch Google Places data
2. **Step 2**: Map Google Places types to category (existing logic)
3. **Step 2.5**: **NEW** - Analyze context for sub-venue relationships
4. **Step 2.6**: **NEW** - Adjust category if context indicates sub-venue
5. **Step 3**: AI fallback (if needed)

## Result Tracking

The categorization result now includes:
- `context_analyzed`: Boolean indicating if context analysis was performed
- `is_sub_venue`: Boolean indicating if venue appears to be a sub-venue
- `context_adjusted`: Boolean indicating if category was adjusted based on context

## Logging

Context adjustments are logged with:
- Original category ID
- Adjusted category ID
- Context reasoning

This helps track when and why adjustments are made for future refinement.

## Limitations

This is not an exact science. The system may:

1. **False Positives**: Incorrectly identify standalone facilities as sub-venues
   - Mitigation: Confidence levels help flag uncertain cases
   - Mitigation: Manual review of LOW confidence adjustments

2. **False Negatives**: Miss sub-venue relationships
   - Mitigation: Multiple detection methods increase coverage
   - Mitigation: AI fallback may catch some cases

3. **Database Dependency**: Co-location detection requires other venues to already be categorized
   - Mitigation: As more venues are categorized, detection improves
   - Mitigation: Editorial summary and name patterns work independently

## Future Enhancements

Potential improvements:

1. **Google Places Nearby Search**: Use Google's Nearby Search API to find parent facilities (adds API cost)
2. **Machine Learning**: Train a model on manually verified sub-venue cases
3. **User Feedback**: Allow users to flag incorrect categorizations for learning
4. **Parent Place ID**: If Google Places API adds parent place relationships, use them directly

## Testing

To test the enhancement:

```bash
# Run a dry-run batch to see context adjustments
php artisan venues:categorize --batch=10 --dry-run
```

Look for venues where:
- `context_adjusted: true` appears in the result
- Reasoning mentions "context indicates sub-venue"
- Category changed from "Dedicated facility" to "Leisure centre" or "Gym"

## Example

**Before Enhancement:**
- Venue: "Bulldogs Squash Club"
- Category: "Dedicated facility" (HIGH confidence)
- Reasoning: "Venue name contains 'squash club'"

**After Enhancement (if sub-venue detected):**
- Venue: "Bulldogs Squash Club"
- Category: "Leisure centre" (MEDIUM/HIGH confidence)
- Reasoning: "Originally 'Dedicated facility', but context indicates sub-venue of larger facility. Co-located with 'Bulldogs Sports Complex' (category: 2) at same address"
- Context Adjusted: true


