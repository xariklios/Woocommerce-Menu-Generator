<?php

/**
 * Plugin Name: WooCommerce Menu Generator
 * Description: A plugin to generate a new WordPress navigation menu with all WooCommerce categories as menu items.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: Woocommerce-Menu-Generator
 * Domain Path: /languages/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Load plugin text domain
function wmg_load_textdomain() {
    load_plugin_textdomain('Woocommerce-Menu-Generator', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'wmg_load_textdomain');

/**
 * Enqueue scripts
 * @since    1.0.0
 */
function wmg_enqueue_scripts()
{
    wp_enqueue_style('main-styles', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0', 'all');

    /* Scripts */

    $ajax_data = [
        'ajaxurl' => plugin_dir_url(__FILE__) . 'wmg-ajax.php',
        'nonce' => wp_create_nonce('wmg-script-nonce'),
        'error_messages' => [
            'general' => esc_html__('An error occurred. Please try again.', 'Woocommerce-Menu-Generator'),
            'invalid_response' => esc_html__('Invalid response from server. Please try again.', 'Woocommerce-Menu-Generator'),
            'no_action' => esc_html__('Please select an action to perform.', 'Woocommerce-Menu-Generator'),
            'no_selection' => esc_html__('Please select at least one menu to perform this action.', 'Woocommerce-Menu-Generator'),
            'empty_name' => esc_html__('Please enter a menu name.', 'Woocommerce-Menu-Generator'),
            'delete' => esc_html__('An error occurred while deleting. Please try again.', 'Woocommerce-Menu-Generator'),
            'update' => esc_html__('An error occurred during update. Please try again.', 'Woocommerce-Menu-Generator'),
            'generate' => esc_html__('An error occurred during menu generation. Please try again.', 'Woocommerce-Menu-Generator'),
            'bulk_delete' => esc_html__('An error occurred during bulk delete. Please try again.', 'Woocommerce-Menu-Generator')
        ],
        'confirm_messages' => [
            'delete' => esc_html__('Are you sure you want to delete this menu? This action cannot be undone.', 'Woocommerce-Menu-Generator'),
            'update' => esc_html__('Are you sure you want to update this menu? This will regenerate all menu items.', 'Woocommerce-Menu-Generator'),
            'bulk_delete' => esc_html__('Are you sure you want to delete the selected menus? This action cannot be undone.', 'Woocommerce-Menu-Generator')
        ]
    ];

    // Allow filtering of ajax data
    $ajax_data = apply_filters('wmg_ajax_data', $ajax_data);

    wp_register_script('wmg_script', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), time(), true);
    wp_localize_script('wmg_script', 'wmg_ajax', $ajax_data);

    wp_enqueue_script('wmg_script');
}


/**
 *
 * @since    1.0.0
 */
function woocommerce_menu_generator_register_menu_page()
{
    add_submenu_page(
        'woocommerce',
        esc_html__('Woo Menu Generator', 'Woocommerce-Menu-Generator'),
        esc_html__('Woo Menu Generator', 'Woocommerce-Menu-Generator'),
        'manage_options',
        'woo-menu-generator',
        'wmg_render_menu_page'
    );
}

/**
 *
 * @since    1.0.0
 */
function wmg_render_menu_page()
{
    $my_menus = get_option('woo_registered_menus_from_wmg', array());

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'Woocommerce-Menu-Generator'));
    }

    // Calculate statistics
    $total_menus = count($my_menus);
    $total_menu_items = 0;
    $total_categories = count(get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false)));
    
    if (!empty($my_menus)) {
        foreach ($my_menus as $menu) {
            $menu_items = wp_get_nav_menu_items($menu['id']);
            $total_menu_items += $menu_items ? count($menu_items) : 0;
        }
    }
    
    // Allow filtering of statistics
    $stats = apply_filters('wmg_statistics', array(
        'total_menus' => $total_menus,
        'total_menu_items' => $total_menu_items,
        'total_categories' => $total_categories
    ));

?>
    <div class="wrap wmg-container">
        <!-- Simplified Loading Overlay -->
        <div id="wmg-loading-overlay" class="wmg-loading-overlay">
            <div class="wmg-loading-spinner"></div>
            <div class="wmg-loading-text"><?php esc_html_e('Working Magic', 'Woocommerce-Menu-Generator'); ?></div>
            <div class="wmg-loading-subtext"><?php esc_html_e('Creating menus in progress...', 'Woocommerce-Menu-Generator'); ?></div>
        </div>
        
        <div class="wmg-header">
            <h1 class="wmg-heading"><?php echo esc_html(get_admin_page_title()); ?> </h1>
            <div class="wmg-docs-link">
                <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . 'hooks-documentation.html'); ?>" target="_blank" class="wmg-link">
                    <?php esc_html_e('Developer Documentation', 'Woocommerce-Menu-Generator'); ?>
                    <span class="dashicons dashicons-external"></span>
                </a>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <div id="wmg-success-message" class="wmg-message wmg-success-message"></div>
        <div id="wmg-error-message" class="wmg-message wmg-error-message"></div>
        
        <?php do_action('wmg_before_statistics'); ?>
        
        <!-- Stats Section -->
        <div class="wmg-stats">
            <h2 class="wmg-stats-heading"><?php esc_html_e('Menu Statistics', 'Woocommerce-Menu-Generator'); ?></h2>
            <div class="wmg-stats-grid">
                <div class="wmg-stat-card">
                    <div class="wmg-stat-number"><?php echo esc_html($stats['total_menus']); ?></div>
                    <div class="wmg-stat-label"><?php esc_html_e('Generated Menus', 'Woocommerce-Menu-Generator'); ?></div>
                </div>
                <div class="wmg-stat-card">
                    <div class="wmg-stat-number"><?php echo esc_html($stats['total_menu_items']); ?></div>
                    <div class="wmg-stat-label"><?php esc_html_e('Menu Items', 'Woocommerce-Menu-Generator'); ?></div>
                </div>
                <div class="wmg-stat-card">
                    <div class="wmg-stat-number"><?php echo esc_html($stats['total_categories']); ?></div>
                    <div class="wmg-stat-label"><?php esc_html_e('Product Categories', 'Woocommerce-Menu-Generator'); ?></div>
                </div>
                <?php do_action('wmg_statistics_extra_cards'); ?>
            </div>
        </div>
        
        <?php do_action('wmg_after_statistics'); ?>
        
        <!-- Get started fast - Form first for new users -->
        <?php if (empty($my_menus)): ?>
        <div class="wmg-card">
            <h2 class="wmg-card-heading"><?php esc_html_e('Create Your First Menu', 'Woocommerce-Menu-Generator'); ?></h2>
            <form id="menu-generator-form" class="wmg-form">
                <div class="wmg-field-row">
                    <label for="menu-name" class="wmg-label"><?php esc_html_e('Menu Name:', 'Woocommerce-Menu-Generator'); ?></label>
                    <input type="text" id="menu-name" name="menu-name" class="wmg-input" placeholder="<?php esc_attr_e('Enter menu name', 'Woocommerce-Menu-Generator'); ?>" required>
                </div>
                
                <div class="wmg-field-row">
                    <label for="menu-depth" class="wmg-label"><?php esc_html_e('Menu Depth:', 'Woocommerce-Menu-Generator'); ?></label>
                    <select id="menu-depth" name="menu-depth" class="wmg-input">
                        <option value="0"><?php esc_html_e('All levels (unlimited depth)', 'Woocommerce-Menu-Generator'); ?></option>
                        <option value="1"><?php esc_html_e('Top level categories only', 'Woocommerce-Menu-Generator'); ?></option>
                        <option value="2"><?php esc_html_e('Top level + one subcategory level', 'Woocommerce-Menu-Generator'); ?></option>
                        <option value="3"><?php esc_html_e('Top level + two subcategory levels', 'Woocommerce-Menu-Generator'); ?></option>
                    </select>
                    <p class="wmg-field-description"><?php esc_html_e('Select how many levels of subcategories to include in the menu.', 'Woocommerce-Menu-Generator'); ?></p>
                </div>
                
                <?php 
                // Allow plugins to add custom fields
                do_action('wmg_form_fields'); 
                
                // Generate options for the form using a filter
                $form_options = apply_filters('wmg_form_options', array(
                    'skip_empty' => array(
                        'id' => 'skip-empty',
                        'name' => 'skip-empty',
                        'label' => __('Skip empty categories', 'woocommerce-menu-generator'),
                        'description' => __('Categories with no products will be excluded from the menu.', 'woocommerce-menu-generator')
                    )
                ));
                
                foreach ($form_options as $option): 
                ?>
                <div class="wmg-field-row">
                    <label for="<?php echo esc_attr($option['id']); ?>" class="wmg-checkbox-label">
                        <input type="checkbox" id="<?php echo esc_attr($option['id']); ?>" name="<?php echo esc_attr($option['name']); ?>" class="wmg-checkbox">
                        <?php echo esc_html($option['label']); ?>
                    </label>
                    <?php if (!empty($option['description'])): ?>
                        <p class="wmg-field-description"><?php echo esc_html($option['description']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <div class="wmg-submit-row">
                    <button type="submit" id="generate-menu" class="button button-primary"><?php esc_html_e('Generate Menu', 'Woocommerce-Menu-Generator'); ?></button>
                    <div id="loading-new" class="wmg-loading"></div>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Instructions Section -->
        <div class="wmg-card wmg-instructions">
            <h2 class="wmg-card-heading"><?php esc_html_e('How to Use', 'Woocommerce-Menu-Generator'); ?></h2>
            <div class="wmg-notice">
                <?php echo wp_kses_post(apply_filters('wmg_instructions_intro', __('This plugin helps you automatically generate WordPress navigation menus from your WooCommerce product categories.', 'woocommerce-menu-generator'))); ?>
            </div>
            <?php 
            $instructions = apply_filters('wmg_instructions_steps', array(
                __('Enter a name for your new menu in the form below.', 'woocommerce-menu-generator'),
                __('Check "Skip empty categories" if you want to exclude categories with no products.', 'woocommerce-menu-generator'),
                __('Click "Generate Menu" to create your menu.', 'woocommerce-menu-generator'),
                __('Your new menu will appear in the table below and will be available in Appearance > Menus.', 'woocommerce-menu-generator'),
                __('You can update or delete your menus using the action buttons.', 'woocommerce-menu-generator')
            ));
            ?>
            <ol>
                <?php foreach ($instructions as $step): ?>
                    <li><?php echo wp_kses_post($step); ?></li>
                <?php endforeach; ?>
            </ol>
            <?php do_action('wmg_after_instructions_list'); ?>
        </div>

        <?php do_action('wmg_before_menus_table'); ?>

        <?php if (!empty($my_menus)): ?>
        <!-- Existing Menus Section -->
        <div class="wmg-card">
            <h2 class="wmg-card-heading"><?php esc_html_e('Your Generated Menus', 'Woocommerce-Menu-Generator'); ?></h2>
            
            <div class="wmg-bulk-actions">
                <select id="wmg-bulk-action">
                    <option value=""><?php esc_html_e('Bulk Actions', 'Woocommerce-Menu-Generator'); ?></option>
                    <?php 
                    $bulk_actions = apply_filters('wmg_bulk_actions', array(
                        'delete' => __('Delete', 'woocommerce-menu-generator')
                    ));
                    
                    foreach ($bulk_actions as $action => $label): 
                    ?>
                        <option value="<?php echo esc_attr($action); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="wmg-bulk-apply" class="button wmg-action-button"><?php esc_html_e('Apply', 'Woocommerce-Menu-Generator'); ?></button>
                <span id="wmg-bulk-loading" class="wmg-loading"></span>
                <div class="wmg-select-all-wrap">
                    <label>
                        <input type="checkbox" id="wmg-select-all" />
                        <span><?php esc_html_e('Select All', 'Woocommerce-Menu-Generator'); ?></span>
                    </label>
                </div>
            </div>
            
            <table class="wmg-menus-table">
                <thead>
                    <tr>
                        <th class="wmg-checkbox-column"><span class="screen-reader-text"><?php esc_html_e('Select', 'Woocommerce-Menu-Generator'); ?></span></th>
                        <?php 
                        $table_headers = apply_filters('wmg_table_headers', array(
                            'id' => __('ID', 'woocommerce-menu-generator'),
                            'name' => __('Name', 'woocommerce-menu-generator'),
                            'items' => __('Items', 'woocommerce-menu-generator'),
                            'actions' => __('Actions', 'woocommerce-menu-generator')
                        ));
                        
                        foreach ($table_headers as $id => $header): 
                        ?>
                            <th class="wmg-column-<?php echo esc_attr($id); ?>"><?php echo esc_html($header); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($my_menus as $menu): 
                        $menu = (object)$menu; 
                        $menu_items_count = wp_get_nav_menu_items($menu->id) ? count(wp_get_nav_menu_items($menu->id)) : 0;
                        
                        // Allow plugins to skip showing certain menus
                        if (apply_filters('wmg_should_display_menu', true, $menu)) :
                    ?>
                        <tr>
                            <td><input type="checkbox" class="wmg-menu-checkbox" value="<?php echo esc_attr($menu->id); ?>" /></td>
                            <td><?php echo esc_html($menu->id); ?></td>
                            <td><?php echo esc_html($menu->name); ?></td>
                            <td><?php echo esc_html($menu_items_count); ?></td>
                            <td>
                                <div class="wmg-actions">
                                    <?php 
                                    // Allow plugins to add custom actions
                                    do_action('wmg_before_menu_actions', $menu);
                                    ?>
                                    <a href="javascript:;" data-menu-id="<?php echo esc_attr($menu->id); ?>" class="wmg-action-button wmg-update-button update-menu">
                                        <span class="dashicons dashicons-update"></span> <?php esc_html_e('Update', 'Woocommerce-Menu-Generator'); ?>
                                        <span id="loading-<?php echo esc_attr($menu->id); ?>" class="wmg-loading"></span>
                                    </a>
                                    <a href="javascript:;" data-menu-id="<?php echo esc_attr($menu->id); ?>" class="wmg-action-button wmg-delete-button delete-menu">
                                        <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete', 'Woocommerce-Menu-Generator'); ?>
                                    </a>
                                    <?php 
                                    // Allow plugins to add custom actions
                                    do_action('wmg_after_menu_actions', $menu);
                                    ?>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php do_action('wmg_before_generator_form'); ?>
        
        <?php if (!empty($my_menus)): ?>
        <!-- Menu Generator Form -->
        <div class="wmg-card">
            <h2 class="wmg-card-heading"><?php esc_html_e('Generate New Menu', 'Woocommerce-Menu-Generator'); ?></h2>
            <form id="menu-generator-form" class="wmg-form">
                <div class="wmg-field-row">
                    <label for="menu-name" class="wmg-label"><?php esc_html_e('Menu Name:', 'Woocommerce-Menu-Generator'); ?></label>
                    <input type="text" id="menu-name" name="menu-name" class="wmg-input" placeholder="<?php esc_attr_e('Enter menu name', 'Woocommerce-Menu-Generator'); ?>" required>
                </div>
                
                <div class="wmg-field-row">
                    <label for="menu-depth" class="wmg-label"><?php esc_html_e('Menu Depth:', 'Woocommerce-Menu-Generator'); ?></label>
                    <select id="menu-depth" name="menu-depth" class="wmg-input">
                        <option value="0"><?php esc_html_e('All levels (unlimited depth)', 'Woocommerce-Menu-Generator'); ?></option>
                        <option value="1"><?php esc_html_e('Top level categories only', 'Woocommerce-Menu-Generator'); ?></option>
                        <option value="2"><?php esc_html_e('Top level + one subcategory level', 'Woocommerce-Menu-Generator'); ?></option>
                        <option value="3"><?php esc_html_e('Top level + two subcategory levels', 'Woocommerce-Menu-Generator'); ?></option>
                    </select>
                    <p class="wmg-field-description"><?php esc_html_e('Select how many levels of subcategories to include in the menu.', 'Woocommerce-Menu-Generator'); ?></p>
                </div>
                
                <?php 
                // Allow plugins to add custom fields
                do_action('wmg_form_fields'); 
                
                // Generate options for the form using a filter
                $form_options = apply_filters('wmg_form_options', array(
                    'skip_empty' => array(
                        'id' => 'skip-empty',
                        'name' => 'skip-empty',
                        'label' => __('Skip empty categories', 'woocommerce-menu-generator'),
                        'description' => __('Categories with no products will be excluded from the menu.', 'woocommerce-menu-generator')
                    )
                ));
                
                foreach ($form_options as $option): 
                ?>
                <div class="wmg-field-row">
                    <label for="<?php echo esc_attr($option['id']); ?>" class="wmg-checkbox-label">
                        <input type="checkbox" id="<?php echo esc_attr($option['id']); ?>" name="<?php echo esc_attr($option['name']); ?>" class="wmg-checkbox">
                        <?php echo esc_html($option['label']); ?>
                    </label>
                    <?php if (!empty($option['description'])): ?>
                        <p class="wmg-field-description"><?php echo esc_html($option['description']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <div class="wmg-submit-row">
                    <button type="submit" id="generate-menu" class="button button-primary"><?php esc_html_e('Generate Menu', 'Woocommerce-Menu-Generator'); ?></button>
                    <div id="loading-new" class="wmg-loading"></div>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <?php do_action('wmg_after_generator_form'); ?>
    </div>
    <?php
}

/**
 *
 * @since    1.0.0
 */

function wmg_init_settings()
{
    register_setting('woocommerce_menu_generator_settings', 'woocommerce_menu_generator_settings');

    add_settings_section(
        'woocommerce_menu_generator_section',
        __('Menu Settings', 'woocommerce-menu-generator'),
        'woocommerce_menu_generator_render_settings_section',
        'woocommerce-menu-generator'
    );
}

/**
 *
 * @since    1.0.0
 */
function wmg_render_settings_section()
{
    echo '<p>' . __('Enter the settings for your new menu.', 'woocommerce-menu-generator') . '</p>';
}

/**
 * Ajax Generate Menu
 * @since    1.0.0
 */
function wmg_generate_menu()
{

    if (!isset($_POST['nonce_ajax']) || !wp_verify_nonce($_POST['nonce_ajax'], 'wmg-script-nonce')):
        wp_die('Unauthorized request. Go away!');
    endif;
    $menu_name = isset($_POST['menu_name']) ? sanitize_text_field($_POST['menu_name']) : '';
    $skip_empty = $_POST['skip_empty'] == 'true';
    $menu_depth = isset($_POST['menu_depth']) ? intval($_POST['menu_depth']) : 0; // Get menu depth parameter

   
    if (!empty($menu_name)):

        $registered_menus = get_option('woo_registered_menus_from_wmg');

        if (!empty($registered_menus)):
            foreach ($registered_menus as $index => $registered_menu):
                if ($registered_menu['name'] == $menu_name):
                    add_settings_error('woocommerce_menu_generator_settings', 'error', __('Menu already exists.'), 'error');
                    wp_send_json_error('Menu already exists.', 400);
                endif;
            endforeach;
        endif;

        $menu_id = wp_create_nav_menu($menu_name);

        // add menu to list
        if (empty($registered_menus)):
            $registered_menus = [];
        endif;

        $registered_menus[] = [
            'id' => $menu_id,
            'name' => $menu_name
        ];

        update_option('woo_registered_menus_from_wmg', $registered_menus);

        wmg_add_items_to_menu($menu_id, $skip_empty, $menu_depth); // Pass the menu depth

        $msg = sprintf(__('Menu "%s" has been successfully created.', 'woocommerce-menu-generator'), $menu_name);
        wp_send_json_success($msg, 200);
    else:
        wp_send_json_error('Enter a menu name', 400);
    endif;
}

/**
 * @param $menu_id
 * @param false $skip
 * @param int $depth Maximum depth of subcategories to include (0 = unlimited)
 */
function wmg_add_items_to_menu($menu_id, $skip = false, $depth = 0)
{
    // add menu items
    $categories = $skip ?
        get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        )) :
        get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
    // Loop through the categories and add them as menu items
    foreach ($categories as $category) {
        if ($category->parent == 0) {
            $menu_item_data = array(
                'menu-item-object-id' => $category->term_id,
                'menu-item-object' => 'product_cat',
                'menu-item-type' => 'taxonomy',
                'menu-item-title' => $category->name,
                'menu-item-status' => 'publish'
            );
            $menu_item_id = wp_update_nav_menu_item($menu_id, 0, $menu_item_data);

            // Only add subcategories if depth is 0 (unlimited) or greater than 1
            if ($depth === 0 || $depth > 1) {
                add_subcategories_to_menu($categories, $menu_id, $menu_item_id, $category->term_id, $depth, 2);
            }
        }
    }

    return true;
}


/**
 * @param $categories
 * @param $menu_id
 * @param $parent_menu_item_id
 * @param $parent_category_id
 * @param int $max_depth Maximum depth to include (0 = unlimited)
 * @param int $current_depth Current depth level (starts at 2 for first level of subcategories)
 *
 * Recursive add subcategories
 */
function add_subcategories_to_menu($categories, $menu_id, $parent_menu_item_id, $parent_category_id, $max_depth = 0, $current_depth = 2)
{
    // If we've reached the maximum depth, stop recursion
    if ($max_depth > 0 && $current_depth > $max_depth) {
        return;
    }

    foreach ($categories as $subcategory) {
        if ($subcategory->parent == $parent_category_id) {
            $submenu_item_data = array(
                'menu-item-object-id' => $subcategory->term_id,
                'menu-item-object' => 'product_cat',
                'menu-item-type' => 'taxonomy',
                'menu-item-title' => $subcategory->name,
                'menu-item-parent-id' => $parent_menu_item_id,
                'menu-item-status' => 'publish'
            );
            $submenu_item_id = wp_update_nav_menu_item($menu_id, 0, $submenu_item_data);

            // Continue recursion for next level if depth allows
            if ($max_depth === 0 || $current_depth < $max_depth) {
                add_subcategories_to_menu($categories, $menu_id, $submenu_item_id, $subcategory->term_id, $max_depth, $current_depth + 1);
            }
        }
    }
}

/**
 * Delete Menu
 */
function wmg_delete_menu()
{
    if (!isset($_POST['nonce_ajax']) || !wp_verify_nonce($_POST['nonce_ajax'], 'wmg-script-nonce')) {
        wp_die('Unauthorized request. Go away!');
    }

    // Initialize to false before we try to delete
    $deleted = false;
    $deleted_menu_name = '';

    if (isset($_POST['menu_id'])) {
        $menu_id = intval($_POST['menu_id']);
        
        // Check if the menu exists before attempting to delete
        $menu_object = wp_get_nav_menu_object($menu_id);
        if (!$menu_object) {
            wp_send_json_error('Menu does not exist or has already been deleted.', 400);
            return;
        }
        
        $deleted = wp_delete_nav_menu($menu_id);
        $registered_menus = get_option('woo_registered_menus_from_wmg', array());
        
        foreach ($registered_menus as $index => $registered_menu) {
            if ($registered_menu['id'] == $menu_id) {
                unset($registered_menus[$index]);
                $deleted_menu_name = $registered_menu['name'];
                break;
            }
        }
        
        // Update registered menus option with re-indexed array
        update_option('woo_registered_menus_from_wmg', array_values($registered_menus));
    } else {
        wp_send_json_error('No menu ID specified.', 400);
        return;
    }

    if ($deleted) {
        $msg = sprintf(__('Menu "%s" has been successfully deleted.', 'woocommerce-menu-generator'), $deleted_menu_name);
        wp_send_json_success($msg, 200);
    } else {
        wp_send_json_error('Failed to delete menu. It may have already been deleted or does not exist.', 400);
    }
}


function wmg_update_menu()
{
    if (!isset($_POST['nonce_ajax']) || !wp_verify_nonce($_POST['nonce_ajax'], 'wmg-script-nonce')) :
        wp_die('Unauthorized request. Go away!');
    endif;

    if (isset($_POST['menu_id'])):
        $menu_id = intval($_POST['menu_id']);
        $skip_empty = isset($_POST['skip_empty']) ? $_POST['skip_empty'] == 'true' : false;
        $menu_depth = isset($_POST['menu_depth']) ? intval($_POST['menu_depth']) : 0;
        
        // Check if the menu exists before attempting to update
        $menu = wp_get_nav_menu_object($menu_id);
        if (!$menu) {
            wp_send_json_error('Menu does not exist or has already been deleted.', 400);
            return;
        }

        // Get all the menu items
        $menu_items = wp_get_nav_menu_items($menu->name);

        // Loop through the menu items and delete them
        foreach ($menu_items as $menu_item) {
            wp_delete_post($menu_item->ID, true);
        }

        wmg_add_items_to_menu($menu_id, $skip_empty, $menu_depth);
        $msg = sprintf(__('Menu "%s" has been successfully updated.', 'woocommerce-menu-generator'), $menu->name);
        wp_send_json_success($msg, 200);
    endif;

    wp_send_json_error('Update terminated unsuccessfully', 400);
}

/**
 * Bulk Delete Menus
 * @since    1.0.0
 */
function wmg_bulk_delete_menus() {
    if (!isset($_POST['nonce_ajax']) || !wp_verify_nonce($_POST['nonce_ajax'], 'wmg-script-nonce')) {
        wp_die('Unauthorized request. Go away!');
    }

    if (!isset($_POST['menu_ids']) || !is_array($_POST['menu_ids'])) {
        wp_send_json_error('No menus selected for deletion.', 400);
        return;
    }

    $menu_ids = array_map('intval', $_POST['menu_ids']);
    $registered_menus = get_option('woo_registered_menus_from_wmg', array());
    $deleted_count = 0;
    $failed_count = 0;
    $deleted_menu_names = array();

    foreach ($menu_ids as $menu_id) {
        $deleted = wp_delete_nav_menu($menu_id);
        
        if ($deleted) {
            $deleted_count++;
            
            // Remove from our registered menus
            foreach ($registered_menus as $index => $registered_menu) {
                if ($registered_menu['id'] == $menu_id) {
                    $deleted_menu_names[] = $registered_menu['name'];
                    unset($registered_menus[$index]);
                    break;
                }
            }
        } else {
            $failed_count++;
        }
    }
    
    // Update the registered menus option
    update_option('woo_registered_menus_from_wmg', array_values($registered_menus));
    
    if ($deleted_count > 0) {
        // Translators: %d is the number of menus that were deleted
        $message = sprintf(
            _n(
                '%d menu has been deleted successfully.',
                '%d menus have been deleted successfully.',
                $deleted_count,
                'Woocommerce-Menu-Generator'
            ),
            $deleted_count
        );
        
        if ($failed_count > 0) {
            // Translators: %d is the number of menus that failed to delete
            $message .= ' ' . sprintf(
                _n(
                    '%d menu could not be deleted.',
                    '%d menus could not be deleted.',
                    $failed_count,
                    'Woocommerce-Menu-Generator'
                ),
                $failed_count
            );
        }
        
        wp_send_json_success($message, 200);
    } else {
        wp_send_json_error('No menus were deleted. Please try again.', 400);
    }
}

/**
 * Run Plugin function
 */
function run()
{
    add_action('admin_menu', 'woocommerce_menu_generator_register_menu_page');
    add_action('admin_enqueue_scripts', 'wmg_enqueue_scripts');
    add_action('wmg_ajax_delete_menu', 'wmg_delete_menu');
    add_action('wmg_ajax_update_menu', 'wmg_update_menu');
    add_action('wmg_ajax_generate_menu', 'wmg_generate_menu');
    add_action('wmg_ajax_bulk_delete_menus', 'wmg_bulk_delete_menus');
}

run();
