# Security Measures - API Data Protection

## Overview
This document outlines the security measures implemented to protect individual venue data from being scraped while allowing aggregate statistics to be publicly accessible.

## Implemented Protections

### 1. Rate Limiting (routes/api.php)

**Aggregate Statistics Endpoints:**
- Rate Limit: 60 requests per minute per IP
- Endpoints:
  - `/api/squash/country-stats`
  - `/api/squash/top-countries`
  - `/api/squash/top-countries-multi`
  - `/api/squash/court-distribution`
  - `/api/squash/court-types`
  - `/api/squash/timeline`
  - `/api/squash/venue-types`
  - `/api/squash/regional-breakdown`
  - `/api/squash/venues-by-state`

**Map Data Endpoint:**
- Rate Limit: 20 requests per minute per IP (stricter due to location data)
- Endpoint: `/api/squash/map`

### 2. Data Anonymization (app/Services/SquashDataAggregator.php)

**Map Points - Removed Sensitive Data:**
- ❌ Venue ID (prevents direct database lookups)
- ❌ Venue name (prevents identification)
- ❌ Physical address (prevents exact location scraping)
- ✅ Coordinates rounded to 2 decimal places (~1km precision)
- ✅ Only aggregate court counts exposed
- ✅ Country/region information retained for context
- ✅ Suburb information retained (general area only)

**What's Exposed:**
```json
{
  "type": "Feature",
  "geometry": {
    "type": "Point",
    "coordinates": [151.21, -33.87]  // Rounded to ~1km precision
  },
  "properties": {
    "courts": 4,
    "glass_courts": 2,
    "outdoor_courts": 0,
    "country": "Australia",
    "country_code": "AU",
    "suburb": "Sydney"
  }
}
```

**What's Protected:**
- Venue name
- Venue ID
- Exact street address
- Precise GPS coordinates
- Contact information
- Any other identifying details

### 3. Frontend Updates (resources/js/dashboard.js)

Map popups now display:
- Country name
- Suburb (general area)
- Court counts (aggregate data)

Map popups DO NOT display:
- Venue name
- Venue address
- Venue ID

## Why These Measures?

1. **Rate Limiting**: Prevents automated scraping tools from rapidly downloading all data
2. **Data Anonymization**: Even if someone bypasses rate limits, they cannot identify specific venues
3. **Coordinate Rounding**: ~1km precision is sufficient for visualization but prevents exact location pinpointing
4. **No IDs**: Prevents direct database queries or correlation with other data sources

## What Legitimate Users Can Still Do

✅ View aggregate statistics by country/region
✅ See general distribution of venues on a map
✅ Understand court type distributions
✅ Analyze trends over time
✅ Compare countries and regions

## What Scrapers Cannot Do

❌ Identify specific venue names
❌ Get exact venue addresses
❌ Correlate data with venue IDs
❌ Rapidly download all venue data
❌ Build a competing venue directory

## Production Considerations

When deploying to production, consider:

1. **Additional Rate Limiting**: May want to reduce limits further (e.g., 30/min for stats, 10/min for map)
2. **CORS Configuration**: Restrict API access to specific domains if needed
3. **API Authentication**: Consider requiring API keys for higher rate limits
4. **Monitoring**: Track API usage patterns to detect scraping attempts
5. **CDN/Caching**: Use Cloudflare or similar to add additional DDoS protection

## Testing Rate Limits

To test rate limits locally:
```bash
# Test aggregate endpoint (should allow 60/min)
for i in {1..65}; do curl https://spa.test/api/squash/country-stats; done

# Test map endpoint (should allow 20/min)
for i in {1..25}; do curl https://spa.test/api/squash/map; done
```

After exceeding limits, you should receive a 429 (Too Many Requests) response.

## Future Enhancements

Consider implementing:
- [ ] API key authentication for trusted partners
- [ ] Honeypot endpoints to detect scrapers
- [ ] IP blacklisting for repeat offenders
- [ ] Request signature validation
- [ ] Geographic rate limiting (stricter for known scraper regions)

