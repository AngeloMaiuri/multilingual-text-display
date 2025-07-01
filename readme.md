
# WP Multilingual Text Display

![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg) ![Version](https://img.shields.io/badge/version-1.0.0-brightgreen.svg) ![License](https://img.shields.io/badge/license-GPLv2-blue.svg) ![Requires](https://img.shields.io/badge/requires-WordPress%205.0%2B%20%7C%20PHP%207.0%2B-orange.svg)

A lightweight WordPress plugin to manage and display multilingual text using shortcodes, seamlessly integrated with WPML (WordPress Multilingual Plugin). Perfect for multilingual websites needing dynamic, language-specific content.

## Features

-   **Multilingual Text Management**: Add, edit, or delete text entries for each WPML language via an intuitive admin interface.
-   **Shortcode Support**: Use `[multilingual_text id="X" class="optional-class"]` to display language-specific text on posts, pages, or widgets.
-   **WPML Integration**: Automatically detects WPML languages and syncs text with the active language.
-   **Fallback Mechanism**: Falls back to the default language if no text is available for the current language.
-   **Secure and Lightweight**: Sanitized inputs, validated data, and a custom database table for efficient storage.
-   **Extensible**: Supports nested shortcodes and custom CSS classes for styling.
-   **Responsive Admin UI**: Clean, user-friendly interface for managing text entries.

## Installation

1.  **Download**: Clone or download the repository from GitHub.
2.  **Upload**: Upload the `wp-multilingual-text-display` folder to your WordPress `/wp-content/plugins/` directory.
3.  **Activate**: Navigate to the WordPress admin panel, go to **Plugins**, and activate **WP Multilingual Text Display**.
4.  **Configure WPML**: Ensure WPML is installed and configured with your desired languages.

## Usage

1.  **Access Admin Panel**:
    -   Go to **Settings > Multilingual Text** in your WordPress admin dashboard.
2.  **Manage Text Entries**:
    -   Add new text or edit existing entries for each WPML language.
    -   Save to generate a unique shortcode (e.g., `[multilingual_text id="1"]`).
3.  **Embed Shortcode**:
    -   Copy the shortcode from the admin table.
    -   Paste it into posts, pages, or widgets where you want the text to appear.
    -   Optionally, add a `class` attribute for custom styling: `[multilingual_text id="1" class="my-custom-class"]`.
4.  **Test Language Switching**:
    -   Use WPML’s language switcher to verify the correct text displays for each language.

## Requirements

-   **WordPress**: Version 5.0 or higher
-   **PHP**: Version 7.0 or higher
-   **WPML**: Active installation of the WordPress Multilingual Plugin
-   **Tested Up To**: WordPress 6.6

## Database

-   Creates a custom table (`wp_wmtd_texts`) to store text entries.
-   Columns: `id`, `entry_id`, `language_code`, `text_content`, `created_at`, `updated_at`.
-   Table is automatically created on activation and removed on uninstall.

## Security

-   **Input Sanitization**: All inputs are sanitized using WordPress best practices.
-   **Nonces**: Used for form submissions and AJAX requests.
-   **Escaping**: All outputs are properly escaped to prevent XSS.

### Hooks and Filters

-   **Filter**: `wmtd_text_output` to modify the shortcode output.
-   **Action**: `wmtd_after_save_text` triggered after saving a text entry.

## Changelog

### 1.0.0

-   Initial release with WPML integration, shortcode support, and admin interface.

## License

This plugin is licensed under the [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html).

## Support

For issues, feature requests, or contributions, please:

-   Open an issue on [GitHub](https://github.com/AngeloMaiuri/wp-multilingual-text-display/issues).
-   Check the [WordPress Support Forum](https://wordpress.org/support/plugin/wp-multilingual-text-display).

## Keywords

WordPress, WPML, multilingual, shortcode, text display, internationalization, i18n
