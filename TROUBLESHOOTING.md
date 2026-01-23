# Quote Builder Troubleshooting Guide

## Issue: Wrong Builder Showing on Page

### Step 1: Check the Debug Log

After loading the page with the shortcode, check your WordPress debug.log file. Look for entries starting with `TEQB Render:` and `TEQB Lookup:`.

You should see:
- `TEQB Render: Shortcode called with attributes:` - Shows what attributes were passed
- `TEQB Render: Builder attribute provided:` - Shows the builder slug from shortcode
- `TEQB Lookup: Searching for builder with slug:` - Shows what slug is being searched
- `TEQB Lookup: Found builder via...` - Shows if builder was found and its ID
- `TEQB Render: Successfully loaded builder data for ID:` - Shows which builder ID was loaded

### Step 2: Verify the Shortcode

Make sure your shortcode is correct:
```
[toast_quote_builder builder="your-builder-slug"]
```

The builder slug should match the **post slug** of your quote builder post in WordPress admin.

### Step 3: Check Builder Post Status

1. Go to WordPress Admin → Quote Builder → Builders
2. Find your builder post
3. Check:
   - **Status**: Must be "Published" (not Draft or Private)
   - **Post Slug**: This is what you use in the shortcode `builder` attribute
   - **Post ID**: You can also use the numeric ID: `builder="12345"`

### Step 4: Clear Caching

If you're using caching plugins or server-side caching:

1. **Browser Cache**: Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)
2. **WordPress Cache**: Clear your caching plugin cache
3. **Server Cache**: Clear server-side cache if applicable
4. **CDN Cache**: Clear CDN cache if using one

### Step 5: Verify Builder Data

Check the debug log for:
- `TEQB Render: Services in data:` - Should list the services
- `TEQB Render: Builder title:` - Should show the builder title

If these are wrong, the builder data might be corrupted or loading from the wrong source.

### Step 6: Check for Multiple Shortcodes

If you have multiple shortcodes on the same page, only the **last one** will set `window.quoteBuilderData`. Make sure you only have one shortcode per page.

### Step 7: Check JavaScript Console

Open browser developer tools (F12) and check:
1. **Console tab**: Look for errors
2. **Network tab**: Check if `quote-builder.js` is loading
3. **Console**: Type `window.quoteBuilderData` and check what builder ID it shows

### Common Issues:

1. **Builder slug mismatch**: The slug in shortcode doesn't match the post slug
   - Solution: Check the post slug in admin and update shortcode

2. **Builder not published**: Builder post is in Draft status
   - Solution: Publish the builder post

3. **Caching**: Old builder data is cached
   - Solution: Clear all caches

4. **Multiple builders**: Wrong builder ID being loaded
   - Solution: Check debug log to see which ID is actually loaded

5. **Location fallback**: If no builder is found, it falls back to location-based lookup
   - Solution: Make sure builder slug is correct and builder exists

## Quick Debug Commands

To see all available builders and their slugs, you can add this to your theme's functions.php temporarily:

```php
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        $builders = get_posts([
            'post_type' => 'teqb_builder',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);
        echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 2px solid red;">';
        echo '<h3>Available Builders:</h3>';
        foreach ($builders as $builder) {
            echo '<p>ID: ' . $builder->ID . ' | Slug: ' . $builder->post_name . ' | Title: ' . $builder->post_title . '</p>';
        }
        echo '</div>';
    }
});
```

This will show all available builders at the bottom of the page (for admins only).
