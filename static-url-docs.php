<?php
/**
 * Plugin Name: Static URL Documents
 * Plugin URI: https://mattruetz.com/
 * Description: Create permanent URLs for documents that automatically redirect to the latest version, solving the WordPress media library URL change issue.
 * Version: 1.1.0
 * Author: Matt Ruetz
 * License: GPL v2 or later
 * Text Domain: static-url-docs
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SUD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SUD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SUD_VERSION', '1.0.0');

class StaticUrlDocs {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_sud_save_mapping', array($this, 'ajax_save_mapping'));
        add_action('wp_ajax_sud_delete_mapping', array($this, 'ajax_delete_mapping'));
        
        // Hook into multiple points to catch our URLs
        add_action('parse_request', array($this, 'early_redirect_check'));
        add_action('template_redirect', array($this, 'handle_redirect'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Add rewrite rules for our static URLs
        add_rewrite_rule('^docs/([^/]+)/?$', 'index.php?sud_doc=$matches[1]', 'top');
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Hook into parse_request to handle our custom URLs before WordPress tries to find pages
        add_action('parse_request', array($this, 'parse_request'));
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'sud_doc';
        return $vars;
    }
    
    public function parse_request($wp) {
        // Check if the request is for our docs URL
        if (preg_match('/^docs\/([^\/]+)\/?$/', $wp->request, $matches)) {
            // Set our query var
            $wp->query_vars['sud_doc'] = $matches[1];
            
            // Handle the redirect immediately
            $this->handle_redirect();
        }
    }
    
    public function early_redirect_check() {
        // Check REQUEST_URI directly for our docs pattern
        if (isset($_SERVER['REQUEST_URI'])) {
            $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            if (preg_match('/\/docs\/([^\/]+)\/?$/', $request_uri, $matches)) {
                $doc_slug = $matches[1];
                
                global $wpdb;
                $table_name = $wpdb->prefix . 'static_url_docs';
                
                $mapping = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE static_slug = %s",
                    $doc_slug
                ));
                
                if ($mapping) {
                    // Redirect immediately before WordPress processes the request further
                    wp_redirect($mapping->document_url, 301);
                    exit;
                } else {
                    // Document not found - return 404
                    status_header(404);
                    wp_die('Document not found. The requested document "' . esc_html($doc_slug) . '" does not exist.', 'Document Not Found', array('response' => 404));
                }
            }
        }
    }
    
    public function activate() {
        // Create database table
        $this->create_table();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'static_url_docs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            static_slug varchar(255) NOT NULL,
            document_url text NOT NULL,
            document_title varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY static_slug (static_slug)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Static URL Documents',
            'Static URLs',
            'manage_options',
            'static-url-docs',
            array($this, 'admin_page'),
            'dashicons-admin-links',
            30
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_static-url-docs') {
            return;
        }
        
        // Enqueue WordPress media scripts for the media library browser
        wp_enqueue_media();
        
        wp_enqueue_script('sud-admin', SUD_PLUGIN_URL . 'assets/admin.js', array('jquery'), SUD_VERSION, true);
        wp_enqueue_style('sud-admin', SUD_PLUGIN_URL . 'assets/admin.css', array(), SUD_VERSION);
        
        wp_localize_script('sud-admin', 'sud_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sud_nonce')
        ));
    }
    
    public function admin_page() {
        include SUD_PLUGIN_PATH . 'templates/admin-page.php';
    }
    
    public function ajax_save_mapping() {
        check_ajax_referer('sud_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $static_slug = sanitize_text_field($_POST['static_slug']);
        $document_url = esc_url_raw($_POST['document_url']);
        $document_title = sanitize_text_field($_POST['document_title']);
        $mapping_id = intval($_POST['mapping_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'static_url_docs';
        
        if ($mapping_id > 0) {
            // Update existing mapping
            $result = $wpdb->update(
                $table_name,
                array(
                    'static_slug' => $static_slug,
                    'document_url' => $document_url,
                    'document_title' => $document_title
                ),
                array('id' => $mapping_id),
                array('%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Create new mapping
            $result = $wpdb->insert(
                $table_name,
                array(
                    'static_slug' => $static_slug,
                    'document_url' => $document_url,
                    'document_title' => $document_title
                ),
                array('%s', '%s', '%s')
            );
        }
        
        if ($result !== false) {
            wp_send_json_success('Mapping saved successfully');
        } else {
            wp_send_json_error('Failed to save mapping');
        }
    }
    
    public function ajax_delete_mapping() {
        check_ajax_referer('sud_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $mapping_id = intval($_POST['mapping_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'static_url_docs';
        
        $result = $wpdb->delete($table_name, array('id' => $mapping_id), array('%d'));
        
        if ($result !== false) {
            wp_send_json_success('Mapping deleted successfully');
        } else {
            wp_send_json_error('Failed to delete mapping');
        }
    }
    
    public function handle_redirect() {
        // Try to get the doc slug from query vars or parse it from the request
        $doc_slug = get_query_var('sud_doc');
        
        // If not found in query vars, try to parse from REQUEST_URI
        if (empty($doc_slug) && isset($_SERVER['REQUEST_URI'])) {
            $request_uri = $_SERVER['REQUEST_URI'];
            if (preg_match('/\/docs\/([^\/\?]+)/', $request_uri, $matches)) {
                $doc_slug = $matches[1];
            }
        }
        
        if (!empty($doc_slug)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'static_url_docs';
            
            $mapping = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE static_slug = %s",
                $doc_slug
            ));
            
            if ($mapping) {
                // Use 301 redirect for better SEO and caching
                wp_redirect($mapping->document_url, 301);
                exit;
            } else {
                // Document not found - return 404
                status_header(404);
                wp_die('Document not found. The requested document "' . esc_html($doc_slug) . '" does not exist.', 'Document Not Found', array('response' => 404));
            }
        }
    }
    
    public function get_all_mappings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'static_url_docs';
        
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    }
    
    // Debug function to test if a mapping exists (can be called from browser)
    public function debug_mapping($slug) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'static_url_docs';
        
        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE static_slug = %s",
            $slug
        ));
        
        return $mapping;
    }
}

// Initialize the plugin
new StaticUrlDocs(); 