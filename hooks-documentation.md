# WooCommerce Menu Generator - Hooks Documentation

This document provides a comprehensive list of all the hooks (actions and filters) available in the WooCommerce Menu Generator plugin, organized by category.

## Action Hooks

Action hooks allow you to execute custom code at specific points during the plugin's execution.

### AJAX Actions

These actions are triggered when AJAX requests are processed:

| Hook | Description | Parameters | File Location |
|------|-------------|------------|--------------|
| `wmg_ajax_delete_menu` | Triggered when a menu deletion request is received | None | wmg-ajax.php |
| `wmg_ajax_update_menu` | Triggered when a menu update request is received | None | wmg-ajax.php |
| `wmg_ajax_generate_menu` | Triggered when a menu generation request is received | None | wmg-ajax.php |
| `wmg_ajax_bulk_delete_menus` | Triggered when a bulk menu deletion request is received | None | wmg-ajax.php |

### UI Hooks

These actions allow you to add custom content to the admin interface:

| Hook | Description | Parameters | File Location |
|------|-------------|------------|--------------|
| `wmg_before_statistics` | Executes before the Statistics section | None | woocommerce-menu-categories.php |
| `wmg_after_statistics` | Executes after the Statistics section | None | woocommerce-menu-categories.php |
| `wmg_statistics_extra_cards` | Allows adding extra statistic cards | None | woocommerce-menu-categories.php |
| `wmg_before_menus_table` | Executes before the menus table | None | woocommerce-menu-categories.php |
| `wmg_after_instructions_list` | Executes after the instructions list | None | woocommerce-menu-categories.php |
| `wmg_before_generator_form` | Executes before the menu generator form | None | woocommerce-menu-categories.php |
| `wmg_after_generator_form` | Executes after the menu generator form | None | woocommerce-menu-categories.php |
| `wmg_form_fields` | Allows adding custom fields to the form | None | woocommerce-menu-categories.php |
| `wmg_before_menu_actions` | Executes before menu actions buttons | `$menu` (object) | woocommerce-menu-categories.php |
| `wmg_after_menu_actions` | Executes after menu actions buttons | `$menu` (object) | woocommerce-menu-categories.php |

## Filter Hooks

Filter hooks allow you to modify data during the plugin's execution.

### Data Filters

| Hook | Description | Parameters | Default | File Location |
|------|-------------|------------|---------|--------------|
| `wmg_ajax_data` | Modifies the AJAX data passed to JavaScript | `$ajax_data` (array) | Array of AJAX settings | woocommerce-menu-categories.php |
| `wmg_statistics` | Modifies the statistics displayed in the UI | `$stats` (array) | Array of statistics | woocommerce-menu-categories.php |
| `wmg_instructions_intro` | Modifies the instructions intro text | `$text` (string) | Default intro text | woocommerce-menu-categories.php |
| `wmg_instructions_steps` | Modifies the instructions steps | `$steps` (array) | Array of instructions | woocommerce-menu-categories.php |
| `wmg_form_options` | Modifies the form options | `$options` (array) | Array of form options | woocommerce-menu-categories.php |
| `wmg_bulk_actions` | Modifies the available bulk actions | `$actions` (array) | Array of bulk actions | woocommerce-menu-categories.php |
| `wmg_table_headers` | Modifies the table headers | `$headers` (array) | Array of table headers | woocommerce-menu-categories.php |
| `wmg_should_display_menu` | Determines whether a menu should be displayed | `$display` (bool), `$menu` (object) | true | woocommerce-menu-categories.php |

## Usage Examples

### Adding a Custom Action Button

```php
// Add a custom action button to each menu
function my_custom_menu_action($menu) {
    ?>
    <a href="javascript:;" data-menu-id="<?php echo esc_attr($menu->id); ?>" class="wmg-action-button my-custom-action">
        <?php _e('Custom Action', 'my-plugin'); ?>
    </a>
    <?php
}
add_action('wmg_after_menu_actions', 'my_custom_menu_action');
```

### Adding Custom Statistics

```php
// Add custom statistics to the statistics section
function my_custom_statistics($stats) {
    $stats['custom_stat'] = 42; // Add your custom statistic
    return $stats;
}
add_filter('wmg_statistics', 'my_custom_statistics');

// Display the custom statistic
function my_custom_statistic_card() {
    $stats = apply_filters('wmg_statistics', array());
    ?>
    <div class="wmg-stat-card">
        <div class="wmg-stat-number"><?php echo esc_html($stats['custom_stat']); ?></div>
        <div class="wmg-stat-label"><?php _e('Custom Stat', 'my-plugin'); ?></div>
    </div>
    <?php
}
add_action('wmg_statistics_extra_cards', 'my_custom_statistic_card');
```

### Adding Custom Form Fields

```php
// Add a custom field to the menu generator form
function my_custom_form_field() {
    ?>
    <div class="wmg-field-row">
        <label for="my-custom-field" class="wmg-label">
            <?php _e('Custom Field:', 'my-plugin'); ?>
        </label>
        <div class="wmg-field">
            <input type="text" id="my-custom-field" name="my-custom-field" class="wmg-input">
        </div>
    </div>
    <?php
}
add_action('wmg_form_fields', 'my_custom_form_field');
```

### Filtering Form Options

```php
// Add a custom option to the form
function my_custom_form_option($options) {
    $options['my_option'] = array(
        'id' => 'my-option',
        'name' => 'my-option',
        'label' => __('My custom option', 'my-plugin'),
        'description' => __('This is a custom option added by my plugin.', 'my-plugin')
    );
    return $options;
}
add_filter('wmg_form_options', 'my_custom_form_option');
```

### Modifying AJAX Data

```php
// Add custom data to the AJAX configuration
function my_custom_ajax_data($ajax_data) {
    $ajax_data['custom_data'] = array(
        'key1' => 'value1',
        'key2' => 'value2'
    );
    return $ajax_data;
}
add_filter('wmg_ajax_data', 'my_custom_ajax_data');
```

### JavaScript Integration with Custom Form Data

```javascript
// Define a custom form data function to extend the form data
function wmg_custom_form_data($form) {
    return {
        'custom_field': $('#my-custom-field').val()
    };
}
``` 