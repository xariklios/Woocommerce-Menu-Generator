<?php

/**
 * Plugin Name: WooCommerce Menu Generator
 * Description: A plugin to generate a new WordPress navigation menu with all WooCommerce categories as menu items.
 * Version: 1.0.0
 * Author: Your Name
 */

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
        'nonce' => wp_create_nonce('wmg-script-nonce')
    ];

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
        __('Woo Menu Generator', 'woo-menu-generator'),
        __('Woo Menu Generator', 'woo-menu-generator'),
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
    $my_menus = get_option('woo_registered_menus_from_wmg');

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <?php if (!empty($my_menus)): ?>
            <div id="my-menus">
                <table>
                    <tr>
                        <th>Id</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>

                    <?php foreach ($my_menus as $menu): $menu = (object)$menu; ?>
                        <tr>
                            <td>
                                <?php echo $menu->id ?>
                            </td>
                            <td>
                                <?php echo $menu->name ?>
                            </td>
                            <td>
                                <table>
                                    <tr>
                                        <td>
                                            <a href="javascript:;" data-menu-id="<?php echo $menu->id ?>"
                                                class="update-menu">Update</a>
                                        </td>
                                        <td>
                                            <a href="javascript:;" data-menu-id="<?php echo $menu->id ?>"
                                                class="delete-menu">Delete</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php
            //            settings_fields('woocommerce_menu_generator_settings');
            //            do_settings_sections('woocommerce_menu_generator_settings');
            //            settings_errors('woocommerce_menu_generator_settings');
            ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Menu Name', 'woocommerce-menu-generator'); ?></th>
                    <td><input type="text" id="wmg_menu_name" name="wmg_menu_name"
                            value="" />
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Skip empty categories', 'woocommerce-menu-generator'); ?></th>
                    <td><input type="checkbox" id="wmg_skip_empty" name="wmg_skip_empty" />
                    </td>
                </tr>
            </table>
            <input type="submit" id="wmg_submit" class="button button-primary" value="Generate Menu">
        </form>
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

        wmg_add_items_to_menu($menu_id, $skip_empty);

        wp_send_json_success('Menu has been created successfully.', 200);
    else:
        wp_send_json_error('Enter a menu name', 400);
    endif;
}

/**
 * @param $menu_id
 * @param false $skip
 */
function wmg_add_items_to_menu($menu_id, $skip = false)
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

            add_subcategories_to_menu($categories, $menu_id, $menu_item_id, $category->term_id);
        }
    }

    return true;
}


/**
 * @param $categories
 * @param $menu_id
 * @param $parent_menu_item_id
 * @param $parent_category_id
 *
 * Recursive add subcategories
 */
function add_subcategories_to_menu($categories, $menu_id, $parent_menu_item_id, $parent_category_id)
{
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

            add_subcategories_to_menu($categories, $menu_id, $submenu_item_id, $subcategory->term_id);
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

    if (isset($_POST['menu_id'])) {
        $deleted = wp_delete_nav_menu($_POST['menu_id']);
        $registered_menus = get_option('woo_registered_menus_from_wmg');
        $deleted_menu_name = '';

        foreach ($registered_menus as $index => $registered_menu) {

            if ($registered_menu['id'] == $_POST['menu_id']) {
                unset($registered_menus[$index]);
                $deleted_menu_name = $registered_menu['name'];
            }
        }
        update_option('woo_registered_menus_from_wmg', $registered_menus);
    }

    if ($deleted) {
        $msg = 'Menu ' . $deleted_menu_name . ' Successfully deleted';
        wp_send_json_success($msg, 200);
    } else {
        wp_send_json_error('error', 400);
    }
}


function wmg_update_menu()
{
    if (!isset($_POST['nonce_ajax']) || !wp_verify_nonce($_POST['nonce_ajax'], 'wmg-script-nonce')) :
        wp_die('Unauthorized request. Go away!');
    endif;

    if (isset($_POST['menu_id'])):
        $menu = wp_get_nav_menu_object($_POST['menu_id']);

        // Get all the menu items
        $menu_items = wp_get_nav_menu_items($menu->name);

        // Loop through the menu items and delete them
        foreach ($menu_items as $menu_item) {
            wp_delete_post($menu_item->ID, true);
        }

        wmg_add_items_to_menu($_POST['menu_id']);
        $msg = 'Menu ' . $_POST['menu_id'] . ' Successfully updated';
        wp_send_json_success($msg, 200);
    endif;

    wp_send_json_error('Update terminated unsuccessfully', 400);
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
}

run();
