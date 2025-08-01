<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$plugin = new StaticUrlDocs();
$mappings = $plugin->get_all_mappings();
?>

<div class="wrap">
    <h1>Static URL Documents</h1>
    <p>Create permanent URLs for your documents that automatically redirect to the latest version. Perfect for sales sheets and other documents that get updated frequently.</p>
    
    <div class="sud-admin-container">
        <div class="sud-form-section">
            <h2>Add New Document URL</h2>
            <form id="sud-mapping-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="static_slug">Static URL Slug</label>
                        </th>
                        <td>
                            <input type="text" id="static_slug" name="static_slug" class="regular-text" placeholder="sales-sheet" required />
                            <p class="description">This will create a URL like: <code><?php echo home_url('/docs/sales-sheet'); ?></code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="document_title">Document Title</label>
                        </th>
                        <td>
                            <input type="text" id="document_title" name="document_title" class="regular-text" placeholder="Sales Sheet 2024" required />
                            <p class="description">A descriptive title for this document mapping.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="document_url">Current Document URL</label>
                        </th>
                        <td>
                            <input type="url" id="document_url" name="document_url" class="regular-text" placeholder="<?php echo home_url('/wp-content/uploads/2024/01/sales-sheet.pdf'); ?>" required />
                            <p class="description">The current URL of your document in the WordPress media library.</p>
                            <button type="button" id="browse-media" class="button">Browse Media Library</button>
                        </td>
                    </tr>
                </table>
                
                <input type="hidden" id="mapping_id" name="mapping_id" value="0" />
                
                <p class="submit">
                    <input type="submit" id="save-mapping" class="button-primary" value="Save Mapping" />
                    <button type="button" id="cancel-edit" class="button" style="display: none;">Cancel</button>
                </p>
            </form>
        </div>
        
        <div class="sud-list-section">
            <h2>Existing Document Mappings</h2>
            
            <?php if (empty($mappings)): ?>
                <p>No document mappings created yet. Create your first one using the form above.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Static URL</th>
                            <th>Document Title</th>
                            <th>Current Document URL</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappings as $mapping): ?>
                            <tr data-mapping-id="<?php echo $mapping->id; ?>">
                                <td>
                                    <code><?php echo home_url('/docs/' . esc_html($mapping->static_slug)); ?></code>
                                    <div class="row-actions">
                                        <span><a href="<?php echo home_url('/docs/' . esc_html($mapping->static_slug)); ?>" target="_blank">Test Link</a></span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($mapping->document_title); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($mapping->document_url); ?>" target="_blank">
                                        <?php echo esc_html(basename($mapping->document_url)); ?>
                                    </a>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($mapping->created_at)); ?></td>
                                <td>
                                    <button class="button edit-mapping" data-id="<?php echo $mapping->id; ?>">Edit</button>
                                    <button class="button delete-mapping" data-id="<?php echo $mapping->id; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="sud-help-section">
            <h3>How to Use</h3>
            <ol>
                <li><strong>Upload your document</strong> to the WordPress media library as usual.</li>
                <li><strong>Create a static URL</strong> using the form above with a memorable slug (e.g., "sales-sheet").</li>
                <li><strong>Share the static URL</strong> with your clients: <code><?php echo home_url('/docs/YOUR-SLUG'); ?></code></li>
                <li><strong>When you update the document</strong>, just edit the mapping and change the "Current Document URL" to point to the new file.</li>
                <li><strong>Your clients' links will automatically work</strong> with the updated document!</li>
            </ol>
            
            <h3>Example Use Case</h3>
            <p>You have a sales sheet that you send to clients. Instead of sending them the direct WordPress media URL (which changes every time you upload a new version), you create a static URL like <code><?php echo home_url('/docs/sales-sheet'); ?></code>. When you need to update the sales sheet, you simply upload the new version and update this mapping - all the links you've already sent out will automatically show the new document.</p>
        </div>
    </div>
</div> 