<?php
/**
 * Plugin Name: Static URL Docs
 * Plugin URI: https://example.com/static-url-docs
 * Description: Provides static URLs for documents that remain unchanged when files are updated, solving the problem of broken links when WordPress media files are replaced.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: static-url-docs
 */

if (!defined('ABSPATH')) {
    exit;
}

define('STATIC_URL_DOCS_VERSION', '1.0.0');
define('STATIC_URL_DOCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('STATIC_URL_DOCS_PLUGIN_URL', plugin_dir_url(__FILE__));

class StaticUrlDocs {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'static_url_docs';
        
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('template_redirect', array($this, 'handle_static_url_request'));
    }
    
    public function init() {
        add_rewrite_rule(
            '^docs/([^/]+)/?$',
            'index.php?static_doc_slug=$matches[1]',
            'top'
        );
        add_query_var('static_doc_slug');
    }
    
    public function activate() {
        $this->create_database_table();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_database_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            slug varchar(255) NOT NULL,
            attachment_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY attachment_id (attachment_id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_admin_menu() {
        add_management_page(
            __('Static URL Docs', 'static-url-docs'),
            __('Static URL Docs', 'static-url-docs'),
            'manage_options',
            'static-url-docs',
            array($this, 'admin_page')
        );
    }
    
    public function handle_static_url_request() {
        $slug = get_query_var('static_doc_slug');
        
        if (!$slug) {
            return;
        }
        
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT attachment_id FROM {$this->table_name} WHERE slug = %s",
            $slug
        ));
        
        if (!$result) {
            wp_die(__('Document not found.', 'static-url-docs'), 404);
        }
        
        $attachment_url = wp_get_attachment_url($result->attachment_id);
        
        if (!$attachment_url) {
            wp_die(__('Document file not found.', 'static-url-docs'), 404);
        }
        
        wp_redirect($attachment_url, 302);
        exit;
    }
    
    public function admin_page() {
        if (isset($_POST['action'])) {
            $this->handle_admin_actions();
        }
        
        $this->render_admin_page();
    }
    
    private function handle_admin_actions() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'static_url_docs_action')) {
            wp_die(__('Security check failed.', 'static-url-docs'));
        }
        
        global $wpdb;
        
        switch ($_POST['action']) {
            case 'add':
                $slug = sanitize_text_field($_POST['slug']);
                $attachment_id = intval($_POST['attachment_id']);
                $title = sanitize_text_field($_POST['title']);
                
                if (empty($slug) || empty($attachment_id) || empty($title)) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('All fields are required.', 'static-url-docs') . '</p></div>';
                    });
                    break;
                }
                
                $result = $wpdb->insert(
                    $this->table_name,
                    array(
                        'slug' => $slug,
                        'attachment_id' => $attachment_id,
                        'title' => $title
                    ),
                    array('%s', '%d', '%s')
                );
                
                if ($result === false) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Failed to create static URL. Slug may already exist.', 'static-url-docs') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Static URL created successfully.', 'static-url-docs') . '</p></div>';
                    });
                }
                break;
                
            case 'update':
                $id = intval($_POST['id']);
                $attachment_id = intval($_POST['attachment_id']);
                
                if (empty($id) || empty($attachment_id)) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Invalid data provided.', 'static-url-docs') . '</p></div>';
                    });
                    break;
                }
                
                $result = $wpdb->update(
                    $this->table_name,
                    array('attachment_id' => $attachment_id),
                    array('id' => $id),
                    array('%d'),
                    array('%d')
                );
                
                if ($result !== false) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Document updated successfully.', 'static-url-docs') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Failed to update document.', 'static-url-docs') . '</p></div>';
                    });
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                
                if (empty($id)) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Invalid ID provided.', 'static-url-docs') . '</p></div>';
                    });
                    break;
                }
                
                $result = $wpdb->delete(
                    $this->table_name,
                    array('id' => $id),
                    array('%d')
                );
                
                if ($result !== false) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Static URL deleted successfully.', 'static-url-docs') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Failed to delete static URL.', 'static-url-docs') . '</p></div>';
                    });
                }
                break;
        }
    }
    
    private function render_admin_page() {
        global $wpdb;
        
        $static_urls = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Static URL Docs', 'static-url-docs'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Add New Static URL', 'static-url-docs'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('static_url_docs_action'); ?>
                    <input type="hidden" name="action" value="add">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="title"><?php _e('Title', 'static-url-docs'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="title" name="title" class="regular-text" required>
                                <p class="description"><?php _e('Descriptive title for this static URL', 'static-url-docs'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="slug"><?php _e('URL Slug', 'static-url-docs'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="slug" name="slug" class="regular-text" required>
                                <p class="description"><?php _e('URL-friendly name (e.g., "sales-sheet" creates /docs/sales-sheet/)', 'static-url-docs'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="attachment_id"><?php _e('Attachment ID', 'static-url-docs'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="attachment_id" name="attachment_id" class="regular-text" required>
                                <p class="description"><?php _e('WordPress media library attachment ID', 'static-url-docs'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Create Static URL', 'static-url-docs')); ?>
                </form>
            </div>
            
            <h2><?php _e('Existing Static URLs', 'static-url-docs'); ?></h2>
            
            <?php if (empty($static_urls)): ?>
                <p><?php _e('No static URLs created yet.', 'static-url-docs'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Title', 'static-url-docs'); ?></th>
                            <th><?php _e('Static URL', 'static-url-docs'); ?></th>
                            <th><?php _e('Current File', 'static-url-docs'); ?></th>
                            <th><?php _e('Actions', 'static-url-docs'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($static_urls as $url): ?>
                            <tr>
                                <td><?php echo esc_html($url->title); ?></td>
                                <td>
                                    <a href="<?php echo home_url('/docs/' . $url->slug . '/'); ?>" target="_blank">
                                        <?php echo home_url('/docs/' . $url->slug . '/'); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php 
                                    $attachment = get_post($url->attachment_id);
                                    if ($attachment) {
                                        echo esc_html($attachment->post_title);
                                        echo ' <small>(ID: ' . $url->attachment_id . ')</small>';
                                    } else {
                                        echo '<span style="color: red;">' . __('File not found', 'static-url-docs') . '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('static_url_docs_action'); ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?php echo $url->id; ?>">
                                        <input type="number" name="attachment_id" value="<?php echo $url->attachment_id; ?>" style="width: 80px;">
                                        <input type="submit" value="<?php _e('Update', 'static-url-docs'); ?>" class="button button-small">
                                    </form>
                                    
                                    <form method="post" style="display: inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this static URL?', 'static-url-docs'); ?>');">
                                        <?php wp_nonce_field('static_url_docs_action'); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $url->id; ?>">
                                        <input type="submit" value="<?php _e('Delete', 'static-url-docs'); ?>" class="button button-small button-link-delete">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="card" style="margin-top: 20px;">
                <h3><?php _e('How to Use', 'static-url-docs'); ?></h3>
                <ol>
                    <li><?php _e('Upload your document to the WordPress Media Library', 'static-url-docs'); ?></li>
                    <li><?php _e('Note the Attachment ID from the media library', 'static-url-docs'); ?></li>
                    <li><?php _e('Create a static URL above using a memorable slug', 'static-url-docs'); ?></li>
                    <li><?php _e('Share the static URL (/docs/your-slug/) with clients', 'static-url-docs'); ?></li>
                    <li><?php _e('When you need to update the document, upload the new version and update the Attachment ID', 'static-url-docs'); ?></li>
                </ol>
                <p><strong><?php _e('Note:', 'static-url-docs'); ?></strong> <?php _e('The static URL will always redirect to the current version of your document, even when you update it.', 'static-url-docs'); ?></p>
            </div>
        </div>
        <?php
    }
}

new StaticUrlDocs();