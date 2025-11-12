# Chart Gallery Thumbnail System

The chart gallery at `/charts` supports two methods for displaying chart previews:

## Option 1: Static Images (Recommended for Production)
- **Pros:** Fast loading, no performance impact, consistent appearance
- **Cons:** Requires manual screenshot generation or automation
- **Location:** `public/images/dashboards/` and `public/images/charts/`

## Option 2: Live Iframe Previews (Currently Active)
- **Pros:** Always up-to-date, no manual work needed
- **Cons:** Slower loading, higher server load, potential rendering issues
- **Implementation:** Scales down full dashboards/charts to 25% size

## How It Works

The gallery view checks for static images first, then falls back to live iframes:

```blade
@if(file_exists(public_path('images/charts/chart-id.png')))
    {{-- Show static image --}}
    <img src="/images/charts/chart-id.png">
@else
    {{-- Show live iframe preview --}}
    <iframe src="/render?charts=chart-id">
@endif
```

## Switching to Static Images (Option 1)

### Manual Method

1. **Take Screenshots:**
   - Navigate to each dashboard/chart
   - Take a screenshot (1200x800px recommended)
   - Save as PNG

2. **Save Images:**
   - Dashboards: `public/images/dashboards/{dashboard-id}.png`
     - `world.png`
     - `country.png`
     - `venue-types.png`
   
   - Charts: `public/images/charts/{chart-id}.png`
     - `summary-stats.png`
     - `venue-map.png`
     - `continental-breakdown.png`
     - `subcontinental-breakdown.png`
     - `timeline.png`
     - `top-venues.png`
     - `court-distribution.png`
     - `top-courts.png`
     - `venue-categories.png`
     - `website-stats.png`
     - `outdoor-courts.png`

3. **Done!** The gallery will automatically use static images when they exist.

### Automated Method (Future Enhancement)

Create an Artisan command that uses a headless browser (Puppeteer/Browsershot) to:
1. Navigate to each dashboard/chart
2. Wait for content to load
3. Take a screenshot
4. Save to the appropriate directory

```bash
php artisan charts:generate-thumbnails
```

## Performance Comparison

### Live Iframes (Option 2)
- **Initial Load:** ~3-5 seconds for all previews
- **Server Load:** High (renders 14 full dashboards/charts)
- **Bandwidth:** ~2-3 MB per page load
- **Caching:** Browser caches iframes

### Static Images (Option 1)
- **Initial Load:** <1 second for all previews
- **Server Load:** Minimal (serves static files)
- **Bandwidth:** ~500 KB per page load (optimized PNGs)
- **Caching:** Standard image caching

## Current Status

✅ **Option 2 (Live Iframes)** is currently active
- Gallery displays live previews
- Falls back to static images if they exist
- Easy to switch by adding images to directories

## Recommendations

1. **Development:** Use Option 2 (live iframes) for convenience
2. **Production:** Switch to Option 1 (static images) for performance
3. **Hybrid:** Use static images for dashboards, live iframes for individual charts

## Testing

Test the gallery at:
- Local: https://spa.test/charts
- Production: https://stats.squashplayers.app/charts

## Troubleshooting

### Iframes Not Loading
- Check that routes are accessible
- Verify CORS settings allow iframe embedding
- Check browser console for errors

### Images Not Showing
- Verify files exist in correct directories
- Check file permissions (755 for directories, 644 for files)
- Clear Laravel cache: `php artisan cache:clear`

### Slow Loading
- Switch to Option 1 (static images)
- Optimize image sizes (compress PNGs)
- Consider lazy loading (already implemented)

