# Static URL Documents - WordPress Plugin

A WordPress plugin that solves the problem of changing document URLs in the WordPress media library. Create permanent, static URLs for your documents that automatically redirect to the latest version.

## Problem Solved

When you upload documents to WordPress media library and share the URLs with clients, the URLs include upload dates and change every time you update the document. This plugin creates permanent URLs that always point to the latest version of your documents.

**Before:** `yoursite.com/wp-content/uploads/2024/01/sales-sheet.pdf` → URL changes when updated
**After:** `yoursite.com/docs/sales-sheet` → URL never changes, always shows latest version

## Features

- ✅ Create permanent URLs for any document type (PDF, DOC, images, etc.)
- ✅ Easy-to-use admin interface for managing document mappings
- ✅ Integration with WordPress media library browser
- ✅ Automatic URL sanitization and validation
- ✅ Edit and delete existing mappings
- ✅ Test links directly from admin interface
- ✅ Responsive design that works on mobile devices
- ✅ Secure with proper nonce verification and user capability checks

## Installation

### Method 1: Upload Plugin Files

1. Download or clone this repository
2. Upload the entire `static-url-docs` folder to your `wp-content/plugins/` directory
3. Go to your WordPress admin → Plugins
4. Find "Static URL Documents" and click "Activate"

### Method 2: ZIP Upload

1. Create a ZIP file of the plugin folder
2. Go to WordPress admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate

## Usage

### Creating Your First Static URL

1. Go to **WordPress Admin → Static URLs**
2. Fill out the form:
   - **Static URL Slug**: Enter a memorable slug (e.g., "sales-sheet")
   - **Document Title**: A descriptive title for your reference
   - **Current Document URL**: The current WordPress media library URL
3. Click "Browse Media Library" to easily select from uploaded files
4. Click "Save Mapping"

Your static URL will be: `yoursite.com/docs/your-slug`

### Updating a Document

1. Upload the new version of your document to WordPress media library
2. Go to **WordPress Admin → Static URLs**
3. Click "Edit" next to the mapping you want to update
4. Change the "Current Document URL" to point to the new file
5. Click "Update Mapping"

All existing links will now redirect to the new document!

### Example Workflow

**Initial Setup:**

1. Upload `sales-sheet-v1.pdf` to media library
2. Create static URL: `yoursite.com/docs/sales-sheet`
3. Share this URL with clients

**When You Need to Update:**

1. Upload `sales-sheet-v2.pdf` to media library
2. Edit the mapping to point to the new file
3. All client links automatically work with the new document!

## URL Structure

Static URLs follow this pattern:

```
yoursite.com/docs/[your-slug]
```

Examples:

- `yoursite.com/docs/sales-sheet`
- `yoursite.com/docs/product-catalog`
- `yoursite.com/docs/price-list-2024`

## Technical Details

### Database

The plugin creates a table `wp_static_url_docs` with the following structure:

- `id` - Unique identifier
- `static_slug` - The URL slug (e.g., "sales-sheet")
- `document_url` - Current document URL
- `document_title` - Descriptive title
- `created_at` - Creation timestamp
- `updated_at` - Last modification timestamp

### URL Rewriting

The plugin uses WordPress rewrite rules to handle the `/docs/` URLs and redirects them to the appropriate document URLs.

### Security

- All AJAX requests are protected with WordPress nonces
- User capability checks ensure only administrators can manage mappings
- Input sanitization prevents XSS and injection attacks

## Requirements

- WordPress 5.0+
- PHP 7.0+
- Administrator user role to manage mappings

## Troubleshooting

### Static URLs Not Working

1. Go to **WordPress Admin → Settings → Permalinks**
2. Click "Save Changes" to flush rewrite rules
3. Test your static URLs again

### Media Library Browser Not Opening

Make sure you're using a modern browser with JavaScript enabled. The media library integration requires WordPress core media scripts.

### Links Return 404 Error

This usually means the rewrite rules need to be flushed:

1. Deactivate the plugin
2. Reactivate the plugin
3. Go to Settings → Permalinks and click "Save Changes"

## Support

For support or feature requests, please contact the plugin author or create an issue in the repository.

## License

This plugin is licensed under GPL v2 or later.

## Changelog

### Version 1.0.0

- Initial release
- Core functionality for creating and managing static document URLs
- Admin interface with media library integration
- Security features and input validation
