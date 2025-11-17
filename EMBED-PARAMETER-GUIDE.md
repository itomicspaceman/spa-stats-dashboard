# Embed Parameter Guide

## Overview

When embedding stats.squashplayers.app pages in iframes (e.g., via WordPress shortcodes), the `?embed=1` parameter automatically hides the navigation and hero sections to provide a cleaner embedding experience.

## How It Works

### Laravel Application

The `dashboard-layout.blade.php` component checks for the `embed` parameter:

```php
@php
    // Check if page is embedded (via iframe)
    $isEmbedded = request()->has('embed') || request()->has('embedded');
@endphp

@unless($isEmbedded)
    <!-- Navigation and Hero sections only shown when NOT embedded -->
@endunless
```

### WordPress Plugin

The WordPress plugin automatically adds `?embed=1` to all iframe URLs:

```php
// Dashboard shortcode
$query_params['embed'] = '1';
$url .= '?' . http_build_query($query_params);

// Trivia shortcode
$query_params['embed'] = '1';
$url .= '?' . http_build_query($query_params);
```

## What Gets Hidden

When `?embed=1` is present:

✅ **Navigation Bar** - The top menu with Stats/Trivia/Chart Gallery links  
✅ **Hero Section** - The large banner with page title and subtitle  
✅ **Geographic Search** - The search bar (if present)

What **remains visible**:
- Main content area
- Charts and visualizations
- Interactive maps
- Tables and statistics
- Footer (if enabled)

## URL Examples

### Without Embed Parameter (Standalone)
```
https://stats.squashplayers.app/
https://stats.squashplayers.app/trivia
https://stats.squashplayers.app/render?dashboard=world
```
Shows: Full navigation + hero + content

### With Embed Parameter (Embedded)
```
https://stats.squashplayers.app/?embed=1
https://stats.squashplayers.app/trivia?embed=1
https://stats.squashplayers.app/render?dashboard=world&embed=1
```
Shows: Content only (no navigation/hero)

## Benefits

1. **No Duplicate Navigation**: Host site's navigation takes precedence
2. **Cleaner Integration**: Embedded content blends seamlessly
3. **Reduced Clutter**: Removes competing visual elements
4. **Better UX**: Users aren't confused by two sets of navigation
5. **Flexible**: Can still access full site by removing `?embed=1`

## WordPress Shortcode Usage

The WordPress plugin handles this automatically - no manual configuration needed:

```php
[squash_court_stats]           // Automatically adds ?embed=1
[squash_trivia]                    // Automatically adds ?embed=1
[squash_trivia section="graveyard"] // Adds ?section=graveyard&embed=1
```

## Manual iframe Usage

If you're manually creating iframes (not using the WordPress plugin), remember to add `?embed=1`:

```html
<iframe 
    src="https://stats.squashplayers.app/trivia?embed=1"
    width="100%" 
    height="2000px"
    frameborder="0">
</iframe>
```

## Testing

To test the difference:

1. **Standalone**: Visit `https://stats.squashplayers.app/trivia`
   - You'll see navigation + hero + content

2. **Embedded**: Visit `https://stats.squashplayers.app/trivia?embed=1`
   - You'll see content only

## Implementation Date

- **Laravel Backend**: Implemented 2025-11-17
- **WordPress Plugin**: v1.5.0 (2025-11-17)
- **Commit**: `f204c84`

