# Why We Use iframes for WordPress Embedding

## The Problem with Native Integration

When we initially implemented the trivia page, we tried a **native WordPress integration** approach:
- Fetched data from the Laravel API via JavaScript
- Rendered HTML directly in WordPress using custom CSS/JS
- Loaded external libraries (Leaflet, Chart.js, WordCloud2.js) directly

### Issues with Native Integration

1. **CSS Conflicts**: WordPress themes have their own CSS that can override or conflict with our custom styles
2. **JavaScript Conflicts**: WordPress and plugins load jQuery and other libraries that can conflict
3. **Global Variable Pollution**: Our JavaScript variables can conflict with theme/plugin variables
4. **Unpredictable Rendering**: Different themes render content differently
5. **Maintenance Burden**: Need to maintain WordPress-specific CSS/JS in addition to Laravel app
6. **Version Conflicts**: Leaflet/Chart.js versions might conflict with other plugins

## The iframe Solution

We reverted to an **iframe-based approach** for both dashboard and trivia shortcodes:

```php
[squash_stats_dashboard]  // Uses iframe
[squash_trivia]           // Uses iframe
```

### Benefits of iframe Approach

1. ✅ **Complete Isolation**: No CSS conflicts - iframe has its own CSS scope
2. ✅ **Complete Isolation**: No JavaScript conflicts - iframe has its own JS scope
3. ✅ **No Global Variables**: iframe has its own window object
4. ✅ **Consistent Rendering**: Looks identical across all WordPress themes
5. ✅ **Single Codebase**: Maintain one Laravel app, not two versions
6. ✅ **Better Security**: iframe sandbox attribute provides additional security
7. ✅ **Easier Updates**: Update Laravel app, WordPress automatically shows changes
8. ✅ **Better Performance**: Browser can cache iframe content independently

### How It Works

1. **WordPress Plugin**: Renders a simple iframe pointing to the Laravel app
2. **Laravel App**: Serves the full HTML/CSS/JS for the dashboard or trivia page
3. **postMessage API**: Used for dynamic height adjustment (no scrollbars)
4. **Security**: Origin verification ensures only trusted messages are processed

### Example Implementation

```php
// WordPress plugin renders:
<iframe 
    src="https://stats.squashplayers.app/trivia"
    sandbox="allow-scripts allow-same-origin allow-popups"
    style="border: none; width: 100%;">
</iframe>

// JavaScript listens for height updates:
window.addEventListener("message", function(event) {
    if (event.origin === "https://stats.squashplayers.app") {
        iframe.style.height = event.data.height + "px";
    }
});
```

## WordPress Best Practices

This approach aligns with **WordPress.org recommendations**:

> "For embedding external content, especially interactive dashboards, iframes provide the best isolation and prevent conflicts with themes and plugins."

## Conclusion

While native integration might seem more "modern", **iframes are the right tool for this job**:
- They provide 100% reliability across all WordPress installations
- They prevent unpredictable conflicts
- They simplify maintenance
- They're the industry-standard approach for embedding external content

**Result**: A robust, maintainable solution that works consistently for all users.

