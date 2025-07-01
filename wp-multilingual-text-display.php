<?php
/*
Plugin Name: Multilingual Text Display
Version: 1.0.5
Description: Display multilingual text via shortcode using WPML.
Author: Angelo Maiuri
Text Domain: wp-multilingual-text-display
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('WMTD_VERSION', '1.0.5');
define('WMTD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WMTD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'wmtd_activate');
function wmtd_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wmtd_texts';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        entry_id bigint(20) NOT NULL,
        language_code varchar(10) NOT NULL,
        text_content text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY entry_lang (entry_id, language_code)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wmtd_deactivate');
function wmtd_deactivate() {
    // No actions needed on deactivation
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'wmtd_uninstall');
function wmtd_uninstall() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wmtd_texts';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// Check for WPML
add_action('admin_init', 'wmtd_check_wpml');
function wmtd_check_wpml() {
    if (!function_exists('icl_get_languages')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . __('Multilingual Text Display requires WPML to be installed and activated.', 'wp-multilingual-text-display') . '</p></div>';
        });
    }
}

// Admin menu
add_action('admin_menu', 'wmtd_admin_menu');
function wmtd_admin_menu() {
    add_options_page(
        __('Multilingual Text Display', 'wp-multilingual-text-display'),
        __('Multilingual Text', 'wp-multilingual-text-display'),
        'manage_options',
        'wmtd-settings',
        'wmtd_settings_page'
    );
}

// Enqueue admin styles
add_action('admin_enqueue_scripts', 'wmtd_enqueue_admin_styles');
function wmtd_enqueue_admin_styles($hook) {
    if ($hook !== 'settings_page_wmtd-settings') {
        return;
    }
    wp_enqueue_style('wmtd-admin-css', WMTD_PLUGIN_URL . 'admin/css/wmtd-admin.css', [], WMTD_VERSION);
    wp_enqueue_script('wmtd-admin-js', WMTD_PLUGIN_URL . 'admin/js/wmtd-admin.js', ['jquery'], WMTD_VERSION, true);
    wp_localize_script('wmtd-admin-js', 'wmtdAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wmtd_nonce')
    ]);
}

// Settings page
function wmtd_settings_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wmtd_texts';

    // Handle form submission
    if (isset($_POST['wmtd_submit']) && check_admin_referer('wmtd_save_text', 'wmtd_nonce')) {
        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
        $texts = isset($_POST['wmtd_text']) ? (array) $_POST['wmtd_text'] : [];
        $languages = icl_get_languages();

        foreach ($texts as $lang_code => $text) {
            if (!isset($languages[$lang_code]) || empty(trim($text))) {
                continue;
            }
            // Store raw text without sanitization (admin-only access)
            $text = stripslashes($text);
            if ($entry_id) {
                // Update or insert
                $wpdb->replace($table_name, [
                    'entry_id' => $entry_id,
                    'language_code' => $lang_code,
                    'text_content' => $text
                ], ['%d', '%s', '%s']);
            } else {
                // New entry
                $entry_id = $entry_id ?: $wpdb->get_var("SELECT MAX(entry_id) + 1 FROM $table_name") ?: 1;
                $wpdb->insert($table_name, [
                    'entry_id' => $entry_id,
                    'language_code' => $lang_code,
                    'text_content' => $text
                ], ['%d', '%s', '%s']);
            }
        }
        echo '<div class="updated"><p>' . __('Text saved successfully.', 'wp-multilingual-text-display') . '</p></div>';
    }

    // Handle delete
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['entry_id']) && check_admin_referer('wmtd_delete')) {
        $entry_id = intval($_GET['entry_id']);
        $wpdb->delete($table_name, ['entry_id' => $entry_id], ['%d']);
        echo '<div class="updated"><p>' . __('Entry deleted.', 'wp-multilingual-text-display') . '</p></div>';
    }

    // Get all entries
    $entries = $wpdb->get_results("SELECT DISTINCT entry_id FROM $table_name ORDER BY entry_id", ARRAY_A);
    ?>
    <div class="wrap">
        <h1><?php _e('Multilingual Text Display', 'wp-multilingual-text-display'); ?></h1>
        <h2><?php _e('Add/Edit Text Entry', 'wp-multilingual-text-display'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('wmtd_save_text', 'wmtd_nonce'); ?>
            <?php
            $edit_entry_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
            $edit_texts = [];
            if ($edit_entry_id) {
                $edit_texts = $wpdb->get_results($wpdb->prepare("SELECT language_code, text_content FROM $table_name WHERE entry_id = %d", $edit_entry_id), OBJECT_K);
            }
            ?>
            <input type="hidden" name="entry_id" value="<?php echo esc_attr($edit_entry_id); ?>">
            <table class="form-table">
                <?php foreach (icl_get_languages() as $lang) : ?>
                    <tr>
                        <th><label for="wmtd_text_<?php echo esc_attr($lang['code']); ?>"><?php echo esc_html($lang['name']); ?></label></th>
                        <td>
                            <textarea name="wmtd_text[<?php echo esc_attr($lang['code']); ?>]" id="wmtd_text_<?php echo esc_attr($lang['code']); ?>" rows="4" cols="50"><?php echo isset($edit_texts[$lang['code']]) ? $edit_texts[$lang['code']]->text_content : ''; ?></textarea>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p class="submit">
                <input type="submit" name="wmtd_submit" class="button-primary" value="<?php _e('Save Text', 'wp-multilingual-text-display'); ?>">
            </p>
        </form>
        <h2><?php _e('Saved Entries', 'wp-multilingual-text-display'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Entry ID', 'wp-multilingual-text-display'); ?></th>
                    <th><?php _e('Shortcode', 'wp-multilingual-text-display'); ?></th>
                    <th><?php _e('Actions', 'wp-multilingual-text-display'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry) : ?>
                    <tr>
                        <td><?php echo esc_html($entry['entry_id']); ?></td>
                        <td><code>[multilingual_text id="<?php echo esc_attr($entry['entry_id']); ?>"]</code></td>
                        <td>
                            <a href="?page=wmtd-settings&edit=<?php echo esc_attr($entry['entry_id']); ?>" class="button"><?php _e('Edit', 'wp-multilingual-text-display'); ?></a>
                            <a href="?page=wmtd-settings&action=delete&entry_id=<?php echo esc_attr($entry['entry_id']); ?>&_wpnonce=<?php echo wp_create_nonce('wmtd_delete'); ?>" class="button" onclick="return confirm('<?php _e('Are you sure?', 'wp-multilingual-text-display'); ?>');"><?php _e('Delete', 'wp-multilingual-text-display'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Shortcode
add_shortcode('multilingual_text', 'wmtd_shortcode');
function wmtd_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wmtd_texts';
    
    $atts = shortcode_atts(['id' => 0, 'class' => ''], $atts, 'multilingual_text');
    if (!$atts['id']) {
        return '';
    }

    $lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : get_option('WPLANG', 'en_US');
    $text = $wpdb->get_var($wpdb->prepare(
        "SELECT text_content FROM $table_name WHERE entry_id = %d AND $lang_code = %s",
        $atts['id'], $lang
    ));

    // Fallback to default language
    if (!$text) {
        $default_lang = apply_filters('wpml_default_language', null);
        $text = $wpdb->get_var($wpdb->prepare(
            "SELECT text_content FROM $table_name WHERE entry_id = %d AND language_code = %s",
            $atts['id'], $default_lang
        ));
    }

    if ($text) {
        $output = do_shortcode($text);
        if ($atts['class']) {
            $output = '<div class="' . esc_attr($atts['class']) . '">' . $output . '</div>';
        }
        return $output;
    }
    return '';
}

// AJAX handlers
add_action('wp_ajax_wmtd_save_text', 'wmtd_ajax_save_text');
function wmtd_ajax_save_text() {
    check_ajax_referer('wmtd_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wmtd_texts';
    $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
    $texts = isset($_POST['texts']) ? (array) $_POST['texts'] : [];
    $languages = icl_get_languages();

    foreach ($texts as $lang_code => $text) {
        if (!isset($languages[$lang_code]) || empty(trim($text))) {
            continue;
        }
        // Store raw text without sanitization (admin-only access)
        $text = stripslashes($text);
        if ($entry_id) {
            $wpdb->replace($table_name, [
                'entry_id' => $entry_id,
                'language_code' => $lang_code,
                'text_content' => $text
            ], ['%d', '%s', '%s']);
        } else {
            $entry_id = $entry_id ?: $wpdb->get_var("SELECT MAX(entry_id) + 1 FROM $table_name") ?: 1;
            $wpdb->insert($table_name, [
                'entry_id' => $entry_id,
                'language_code' => $lang_code,
                'text_content' => $text
            ], ['%d', '%s', '%s']);
        }
    }

    wp_send_json_success(['entry_id' => $entry_id]);
}

?>