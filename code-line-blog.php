<?php
/**
 * Plugin Name: Code Line Agency Blog
 * Plugin URI: https://codelineagency.com
 * Description: Een moderne blog overzichtspagina voor Code Line Agency
 * Version: 1.0.0
 * Author: Code Line Agency
 * Author URI: https://codelineagency.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: code-line-blog
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CODE_LINE_BLOG_DIR')) {
    define('CODE_LINE_BLOG_DIR', plugin_dir_path(__FILE__));
}

if (!defined('CODE_LINE_BLOG_URL')) {
    define('CODE_LINE_BLOG_URL', plugin_dir_url(__FILE__));
}

class CodeLineBlog {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new CodeLineBlog();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('widgets_init', array($this, 'register_wp_widget'));
        add_action('elementor/widgets/register', array($this, 'register_elementor_widget'));
        add_shortcode('code_line_blog', array($this, 'render_blog'));
        add_action('init', array($this, 'register_custom_post_type'));
        add_action('add_meta_boxes', array($this, 'register_article_metaboxes'));
        add_action('save_post_cl_blog_article', array($this, 'save_article_meta'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_filter('template_include', array($this, 'load_single_template'));
        add_action('admin_enqueue_scripts', array($this, 'admin_tag_scripts'));
        add_filter('rest_cl_blog_tag_query', array($this, 'preserve_blog_label_order_in_rest'), 10, 2);
        add_action('admin_menu', array($this, 'register_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_head', array($this, 'output_single_dynamic_styles'), 99);
    }

    /**
     * Register native WordPress widget.
     */
    public function register_wp_widget() {
        if (!class_exists('WP_Widget')) {
            return;
        }

        register_widget('CodeLineBlog_Widget');
    }

    /**
     * Register Elementor widget when Elementor is available.
     */
    public function register_elementor_widget($widgets_manager) {
        if (!class_exists('\\Elementor\\Widget_Base')) {
            return;
        }

        $widget_file = CODE_LINE_BLOG_DIR . 'elementor-widget.php';
        if (!file_exists($widget_file)) {
            return;
        }

        require_once $widget_file;
        $widgets_manager->register(new \Code_Line_Blog_Elementor_Widget());
    }
    
    /**
     * Register plugin assets used by cards, single pages and slider.
     */
    public function register_assets() {
        $plugin_dir = CODE_LINE_BLOG_DIR;
        $plugin_url = CODE_LINE_BLOG_URL;

        $cards_css_file = $plugin_dir . 'css/blog-cards.css';
        wp_register_style(
            'code-line-blog-cards-css',
            $plugin_url . 'css/blog-cards.css',
            array(),
            file_exists($cards_css_file) ? filemtime($cards_css_file) : '1.0.0'
        );

        $single_css_file = $plugin_dir . 'css/blog-single.css';
        wp_register_style(
            'code-line-blog-single-css',
            $plugin_url . 'css/blog-single.css',
            array(),
            file_exists($single_css_file) ? filemtime($single_css_file) : '1.0.0'
        );

        $slider_js_file = $plugin_dir . 'dist/assets/blog-slider.js';
        wp_register_script(
            'code-line-blog-slider-js',
            $plugin_url . 'dist/assets/blog-slider.js',
            array(),
            file_exists($slider_js_file) ? filemtime($slider_js_file) : '1.0.0',
            true
        );
    }

    /**
     * Load custom single template for blog articles.
     */
    public function load_single_template($template) {
        if (is_post_type_archive('cl_blog_article')) {
            $plugin_template = CODE_LINE_BLOG_DIR . 'templates/archive-cl-blog-article.php';
            if (file_exists($plugin_template)) {
                wp_enqueue_style('code-line-blog-cards-css');
                return $plugin_template;
            }
        }

        if (!is_singular('cl_blog_article')) {
            return $template;
        }

        $plugin_template = CODE_LINE_BLOG_DIR . 'templates/single-cl-blog-article.php';
        if (file_exists($plugin_template)) {
            wp_enqueue_style('code-line-blog-single-css');
            return $plugin_template;
        }

        return $template;
    }

    /**
     * Register plugin style settings in wp_options.
     */
    public function register_settings() {
        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_background_enabled',
            array(
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_single_bg_start',
            array('sanitize_callback' => 'sanitize_hex_color')
        );

        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_single_bg_mid',
            array('sanitize_callback' => 'sanitize_hex_color')
        );

        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_single_bg_end',
            array('sanitize_callback' => 'sanitize_hex_color')
        );

        // Read more button: enable + colors
        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_readmore_enabled',
            array(
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_readmore_bg',
            array('sanitize_callback' => 'sanitize_hex_color')
        );

        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_readmore_color',
            array('sanitize_callback' => 'sanitize_hex_color')
        );

        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_readmore_border',
            array('sanitize_callback' => 'sanitize_hex_color')
        );

        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_readmore_hover_bg',
            array('sanitize_callback' => 'sanitize_hex_color')
        );

        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_readmore_hover_color',
            array('sanitize_callback' => 'sanitize_hex_color')
        );

        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_show_date',
            array(
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => '0',
            )
        );

        register_setting(
            'code_line_blog_style_settings',
            'code_line_blog_show_read_time',
            array(
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => '0',
            )
        );
    }

    /**
     * Add admin page for blog style controls.
     */
    public function register_settings_page() {
        add_submenu_page(
            'edit.php?post_type=cl_blog_article',
            'Blog stijl',
            'Blog stijl',
            'manage_options',
            'code-line-blog-style',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render settings page with gradient color controls.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $background_enabled = get_option('code_line_blog_background_enabled', '1') === '1';
        $start = $this->get_color_setting('code_line_blog_single_bg_start', '#082414');
        $mid = $this->get_color_setting('code_line_blog_single_bg_mid', '#5d955f');
        $end = $this->get_color_setting('code_line_blog_single_bg_end', '#0b2615');
        $readmore_enabled = get_option('code_line_blog_readmore_enabled', '1') === '1';
        $readmore_bg = $this->get_color_setting('code_line_blog_readmore_bg', '');
        $readmore_color = $this->get_color_setting('code_line_blog_readmore_color', '');
        $readmore_border = $this->get_color_setting('code_line_blog_readmore_border', '');
        $readmore_hover_bg = $this->get_color_setting('code_line_blog_readmore_hover_bg', '');
        $readmore_hover_color = $this->get_color_setting('code_line_blog_readmore_hover_color', '');
        $show_date = get_option('code_line_blog_show_date', '0') === '1';
        $show_read_time = get_option('code_line_blog_show_read_time', '0') === '1';
        ?>
        <div class="wrap">
            <h1>Code Line Blog stijl</h1>
            <p>Zet hier de achtergrondkleuren aan of uit en pas de gradientkleuren aan.</p>

            <form method="post" action="options.php">
                <?php settings_fields('code_line_blog_style_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="code_line_blog_background_enabled">Achtergrondkleuren inschakelen</label></th>
                        <td>
                            <input type="hidden" name="code_line_blog_background_enabled" value="0" />
                            <label>
                                <input
                                    type="checkbox"
                                    id="code_line_blog_background_enabled"
                                    name="code_line_blog_background_enabled"
                                    value="1"
                                    <?php checked($background_enabled); ?>
                                />
                                Aan/uit voor card- en artikelafbeelding achtergrond
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="code_line_blog_single_bg_start">Gradient kleur start</label></th>
                        <td><input type="color" id="code_line_blog_single_bg_start" name="code_line_blog_single_bg_start" value="<?php echo esc_attr($start); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="code_line_blog_single_bg_mid">Gradient kleur midden</label></th>
                        <td><input type="color" id="code_line_blog_single_bg_mid" name="code_line_blog_single_bg_mid" value="<?php echo esc_attr($mid); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="code_line_blog_single_bg_end">Gradient kleur eind</label></th>
                        <td><input type="color" id="code_line_blog_single_bg_end" name="code_line_blog_single_bg_end" value="<?php echo esc_attr($end); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="code_line_blog_readmore_enabled">Lees meer knop tonen</label></th>
                        <td>
                            <input type="hidden" name="code_line_blog_readmore_enabled" value="0" />
                            <label>
                                <input
                                    type="checkbox"
                                    id="code_line_blog_readmore_enabled"
                                    name="code_line_blog_readmore_enabled"
                                    value="1"
                                    <?php checked($readmore_enabled); ?>
                                />
                                Aan/uit voor de "Lees meer" knop op kaarten
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="code_line_blog_readmore_bg">Lees meer achtergrond</label></th>
                        <td><input type="color" id="code_line_blog_readmore_bg" name="code_line_blog_readmore_bg" value="<?php echo esc_attr($readmore_bg); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="code_line_blog_readmore_color">Lees meer tekstkleur</label></th>
                        <td><input type="color" id="code_line_blog_readmore_color" name="code_line_blog_readmore_color" value="<?php echo esc_attr($readmore_color); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="code_line_blog_readmore_border">Lees meer randkleur</label></th>
                        <td><input type="color" id="code_line_blog_readmore_border" name="code_line_blog_readmore_border" value="<?php echo esc_attr($readmore_border); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="code_line_blog_readmore_hover_bg">Lees meer hover achtergrond</label></th>
                        <td><input type="color" id="code_line_blog_readmore_hover_bg" name="code_line_blog_readmore_hover_bg" value="<?php echo esc_attr($readmore_hover_bg); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="code_line_blog_readmore_hover_color">Lees meer hover tekstkleur</label></th>
                        <td><input type="color" id="code_line_blog_readmore_hover_color" name="code_line_blog_readmore_hover_color" value="<?php echo esc_attr($readmore_hover_color); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Meta informatie tonen</th>
                        <td>
                            <label class="clb-label">
                                <input type="hidden" name="code_line_blog_show_date" value="0" />
                                <input type="checkbox" name="code_line_blog_show_date" value="1" <?php checked($show_date); ?> />
                                Datum tonen op kaarten
                            </label>
                            <label class="clb-label">
                                <input type="hidden" name="code_line_blog_show_read_time" value="0" />
                                <input type="checkbox" name="code_line_blog_show_read_time" value="1" <?php checked($show_read_time); ?> />
                                Leestijd tonen op kaarten
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Opslaan'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize checkbox option values to 1/0.
     */
    public function sanitize_checkbox($value) {
        return (!empty($value) && $value !== '0') ? '1' : '0';
    }

    /**
     * Check if custom background colors are enabled.
     */
    private function is_background_enabled() {
        return get_option('code_line_blog_background_enabled', '1') === '1';
    }

    /**
     * Sanitize and read a hex color option with fallback default.
     */
    private function get_color_setting($option_name, $default) {
        $value = get_option($option_name, $default);
        $value = sanitize_hex_color($value);
        return $value ? $value : $default;
    }

    /**
     * Print dynamic single-page gradient CSS in head.
     */
    public function output_single_dynamic_styles() {
        if (!$this->is_background_enabled()) {
            ?>
            <style id="code-line-blog-single-dynamic-colors-disabled">
            .clb-media,
            .clb-single-bottom-image {
                background: transparent !important;
            }
            .clb-single-bottom-image::before {
                display: none !important;
            }
            </style>
            <?php
            return;
        }
        $start = $this->get_color_setting('code_line_blog_single_bg_start', '#082414');
        $mid = $this->get_color_setting('code_line_blog_single_bg_mid', '#5d955f');
        $end = $this->get_color_setting('code_line_blog_single_bg_end', '#0b2615');

        // Read-more button options
        $readmore_enabled = get_option('code_line_blog_readmore_enabled', '1') === '1';
        $readmore_bg = $this->get_color_setting('code_line_blog_readmore_bg', '');
        $readmore_color = $this->get_color_setting('code_line_blog_readmore_color', '');
        $readmore_border = $this->get_color_setting('code_line_blog_readmore_border', '');
        $readmore_hover_bg = $this->get_color_setting('code_line_blog_readmore_hover_bg', '');
        $readmore_hover_color = $this->get_color_setting('code_line_blog_readmore_hover_color', '');
        $show_date = get_option('code_line_blog_show_date', '0') === '1';
        $show_read_time = get_option('code_line_blog_show_read_time', '0') === '1';
        ?>
        <style id="code-line-blog-single-dynamic-colors">
        .clb-media {
            background: linear-gradient(135deg, <?php echo esc_html($start); ?> 0%, <?php echo esc_html($mid); ?> 52%, <?php echo esc_html($end); ?> 100%) !important;
        }
        .clb-single-bottom-image {
            background: linear-gradient(135deg, <?php echo esc_html($start); ?> 0%, <?php echo esc_html($mid); ?> 52%, <?php echo esc_html($end); ?> 100%) !important;
        }
        /* Read more button styles from settings */
        <?php if (!$readmore_enabled) : ?>
        .clb-wrap .clb-read-more { display: none !important; }
        <?php else : ?>
        .clb-wrap .clb-read-more {
            <?php if ($readmore_bg) : ?>background: <?php echo esc_html($readmore_bg); ?>;<?php endif; ?>
            <?php if ($readmore_color) : ?>color: <?php echo esc_html($readmore_color); ?>;<?php endif; ?>
            <?php if ($readmore_border) : ?>border-color: <?php echo esc_html($readmore_border); ?>;<?php endif; ?>
        }
        .clb-card:hover .clb-read-more {
            <?php if ($readmore_hover_bg) : ?>background: <?php echo esc_html($readmore_hover_bg); ?>;<?php endif; ?>
            <?php if ($readmore_hover_color) : ?>color: <?php echo esc_html($readmore_hover_color); ?>;<?php endif; ?>
            <?php if ($readmore_hover_bg) : ?>border-color: <?php echo esc_html($readmore_hover_bg); ?>;<?php endif; ?>
        }
        <?php endif; ?>
        </style>
        <?php
    }
    
    /**
     * Register custom post type for blog articles
     */
    public function register_custom_post_type() {
        $labels = array(
            'name' => 'Blog Artikelen',
            'singular_name' => 'Blog Artikel',
            'menu_name' => 'Code Line Blog',
            'add_new' => 'Nieuw Artikel',
            'add_new_item' => 'Nieuw Artikel Toevoegen',
            'edit_item' => 'Artikel Bewerken',
            'view_item' => 'Artikel Bekijken',
            'all_items' => 'Alle Artikelen',
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-admin-post',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail'),
            'rewrite' => array('slug' => 'blog'),
        );
        
        register_post_type('cl_blog_article', $args);
        
        // Register taxonomy for categories
        register_taxonomy('cl_blog_category', 'cl_blog_article', array(
            'hierarchical' => true,
            'labels' => array(
                'name' => 'Categorieën',
                'singular_name' => 'Categorie',
            ),
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'blog-categorie'),
        ));

        register_taxonomy('cl_blog_tag', 'cl_blog_article', array(
            'hierarchical' => false,
            'labels' => array(
                'name'                       => 'Blog Labels',
                'singular_name'              => 'Blog Label',
                'add_new_item'               => 'Label toevoegen',
                'new_item_name'              => 'Nieuw label',
                'separate_items_with_commas' => 'Bijv. ecommerce, shopify',
                'add_or_remove_items'        => 'Labels toevoegen of verwijderen',
                'choose_from_most_used'      => 'Kies uit veelgebruikte labels',
                'not_found'                  => 'Geen labels gevonden',
                'menu_name'                  => 'Labels',
            ),
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'blog-label'),
        ));
    }

    /**
     * Enqueue admin JS to enforce max 5 labels and set placeholder text.
     */
    public function admin_tag_scripts($hook) {
        // Only load on post edit/new screens for our custom post type
        if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'cl_blog_article') {
            return;
        }

        // Enqueue admin helper script
        $script_path = plugin_dir_path(__FILE__) . 'assets/admin-tag.js';
        wp_enqueue_script(
            'code-line-blog-admin-tags',
            plugin_dir_url(__FILE__) . 'assets/admin-tag.js',
            array('jquery'),
            file_exists($script_path) ? filemtime($script_path) : false,
            true
        );
        // Enqueue admin styles for blog meta box
        $style_path = plugin_dir_path(__FILE__) . 'assets/admin-blog-styles.css';
        wp_enqueue_style(
            'code-line-blog-admin-css',
            plugin_dir_url(__FILE__) . 'assets/admin-blog-styles.css',
            array(),
            file_exists($style_path) ? filemtime($style_path) : false
        );
    }

    /**
     * Keep selected labels in the same order as provided by Gutenberg.
     *
     * By default terms are often returned alphabetically, which makes a newly
     * added label appear in the middle of existing chips.
     */
    public function preserve_blog_label_order_in_rest($prepared_args, $request) {
        $include = $request->get_param('include');

        if (!empty($include)) {
            $prepared_args['orderby'] = 'include';
            $prepared_args['order'] = 'ASC';
        }

        return $prepared_args;
    }

    /**
     * Register article detail meta box for card-specific fields.
     */
    public function register_article_metaboxes() {
        add_meta_box(
            'cl_blog_article_details',
            'Artikel Card Details',
            array($this, 'render_article_details_metabox'),
            'cl_blog_article',
            'side',
            'default'
        );
    }

    /**
     * Render the card details fields in the post editor.
     */
    public function render_article_details_metabox($post) {
        wp_nonce_field('cl_blog_article_details_nonce_action', 'cl_blog_article_details_nonce');

        $read_time = get_post_meta($post->ID, 'cl_blog_read_time', true);
        $card_excerpt = get_post_meta($post->ID, 'cl_blog_card_excerpt', true);
        $image_type = get_post_meta($post->ID, 'cl_blog_image_type', true);
        if (!in_array($image_type, array('photo', 'logo'), true)) {
            $image_type = 'photo';
        }
        ?>
        <p>
            <label for="cl_blog_read_time"><strong>Leestijd</strong></label><br />
                <input
                type="text"
                id="cl_blog_read_time"
                name="cl_blog_read_time"
                value="<?php echo esc_attr($read_time); ?>"
                class="clb-fullinput"
                placeholder="Bijv. 4 min read"
            />
        </p>
        <p>
            <label for="cl_blog_card_excerpt"><strong>Kaart samenvatting</strong></label><br />
            <textarea
                id="cl_blog_card_excerpt"
                name="cl_blog_card_excerpt"
                rows="4"
                class="clb-fullinput"
                placeholder="Korte intro die op de kaart wordt getoond"
            ><?php echo esc_textarea($card_excerpt); ?></textarea>
        </p>
        <p>
            <label for="cl_blog_image_type"><strong>Afbeelding type</strong></label><br />
            <select
                id="cl_blog_image_type"
                name="cl_blog_image_type"
                class="clb-fullinput"
            >
                <option value="photo" <?php selected($image_type, 'photo'); ?>>Foto (vult kaart)</option>
                <option value="logo" <?php selected($image_type, 'logo'); ?>>Logo (volledig zichtbaar)</option>
            </select>
        </p>
        <?php
    }

    /**
     * Save card details meta fields.
     */
    public function save_article_meta($post_id) {
        if (!isset($_POST['cl_blog_article_details_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['cl_blog_article_details_nonce'], 'cl_blog_article_details_nonce_action')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['cl_blog_read_time'])) {
            update_post_meta($post_id, 'cl_blog_read_time', sanitize_text_field(wp_unslash($_POST['cl_blog_read_time'])));
        }

        if (isset($_POST['cl_blog_card_excerpt'])) {
            update_post_meta($post_id, 'cl_blog_card_excerpt', sanitize_textarea_field(wp_unslash($_POST['cl_blog_card_excerpt'])));
        }

        if (isset($_POST['cl_blog_image_type'])) {
            $image_type = sanitize_key(wp_unslash($_POST['cl_blog_image_type']));
            if (!in_array($image_type, array('photo', 'logo'), true)) {
                $image_type = 'photo';
            }
            update_post_meta($post_id, 'cl_blog_image_type', $image_type);
        }

        if (isset($_POST['tax_input']['cl_blog_tag'])) {
            $raw_tags = wp_unslash($_POST['tax_input']['cl_blog_tag']);
            $tags = array();

            if (is_array($raw_tags)) {
                $tags = $raw_tags;
            } else {
                $tags = explode(',', (string) $raw_tags);
            }

            $tags = array_map('trim', $tags);
            $tags = array_filter($tags, 'strlen');
            $tags = array_slice(array_values(array_unique($tags)), 0, 5);

            wp_set_post_terms($post_id, $tags, 'cl_blog_tag', false);
        }
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('code-line-blog/v1', '/articles', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_articles'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('code-line-blog/v1', '/articles/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_article'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Get all blog articles via REST API
     */
    public function get_articles($request) {
        $per_page = isset($request['per_page']) ? (int) $request['per_page'] : -1;
        $per_page = $per_page > 0 ? $per_page : -1;

        $args = array(
            'post_type' => 'cl_blog_article',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        if (!empty($request['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'cl_blog_category',
                    'field' => 'slug',
                    'terms' => sanitize_title($request['category']),
                ),
            );
        }
        
        $posts = get_posts($args);
        $articles = array();
        
        foreach ($posts as $post) {
            $articles[] = $this->format_article($post);
        }
        
        return new WP_REST_Response($articles, 200);
    }
    
    /**
     * Get single blog article via REST API
     */
    public function get_article($request) {
        $id = $request['id'];
        $post = get_post($id);

        if (!$post || $post->post_type !== 'cl_blog_article') {
            return new WP_Error('not_found', 'Artikel niet gevonden', array('status' => 404));
        }

        // Keep unpublished content private for public API callers.
        if ($post->post_status !== 'publish' && !current_user_can('edit_post', $post->ID)) {
            return new WP_Error('not_found', 'Artikel niet gevonden', array('status' => 404));
        }
        
        return new WP_REST_Response($this->format_article($post), 200);
    }
    
    /**
     * Format article data for API response
     */
    private function format_article($post) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : '';
        
        $categories = get_the_terms($post->ID, 'cl_blog_category');
        $category = $categories && !is_wp_error($categories) ? $categories[0]->name : 'Algemeen';
        $tags = get_the_terms($post->ID, 'cl_blog_tag');
        $tag_names = array();

        if ($tags && !is_wp_error($tags)) {
            foreach ($tags as $tag) {
                $tag_names[] = $tag->name;
            }
        }

        $card_excerpt = get_post_meta($post->ID, 'cl_blog_card_excerpt', true);
        $read_time = get_post_meta($post->ID, 'cl_blog_read_time', true);
        $image_type = get_post_meta($post->ID, 'cl_blog_image_type', true);
        if (!in_array($image_type, array('photo', 'logo'), true)) {
            $image_type = 'photo';
        }
        $excerpt = $card_excerpt ? $card_excerpt : ($post->post_excerpt ? $post->post_excerpt : wp_trim_words($post->post_content, 30));
        
        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'excerpt' => $excerpt,
            'content' => apply_filters('the_content', $post->post_content),
            'imageUrl' => $thumbnail_url,
            'publishDate' => $post->post_date,
            'publishDateHuman' => get_the_date('j F Y', $post),
            'category' => $category,
            'tags' => $tag_names,
            'readTime' => $read_time ? $read_time : '5 min read',
            'imageType' => $image_type,
            'author' => get_the_author_meta('display_name', $post->post_author),
            'permalink' => get_permalink($post),
        );
    }
    
    /**
     * Render the blog via shortcode
     */
    public function render_blog($atts) {
        return $this->render_blog_markup($atts);
    }

    /**
     * Render blog markup for both shortcode and widget usage.
     */
    public function render_blog_markup($atts = array()) {
        $atts = shortcode_atts(array(
            'per_page'    => '-1',
            'category'    => '',
            'columns'     => '3',
            'show_filter' => '0',
            'layout'      => 'slider',
            'slides_desktop' => '3',
            'max_items'   => '8',
            'full_width'  => '1',
        ), (array) $atts, 'code_line_blog');

        $layout = in_array($atts['layout'], array('grid', 'slider'), true) ? $atts['layout'] : 'slider';
        $is_slider = ($layout === 'slider');
        $show_filter = ($atts['show_filter'] === '1') && !$is_slider;
        $is_full_width = ($atts['full_width'] === '1');

        $slides_desktop = (int) $atts['slides_desktop'];
        if ($slides_desktop < 1 || $slides_desktop > 4) {
            $slides_desktop = 3;
        }

        $per_page = (int) $atts['per_page'];
        if ($per_page <= 0) {
            $per_page = -1;
        }

        $max_items = (int) $atts['max_items'];
        if ($is_slider) {
            // Slider is intentionally limited to recent content only.
            if ($per_page === -1 || $per_page > 8) {
                $per_page = 8;
            }
            if ($max_items > 0 && $max_items < $per_page) {
                $per_page = $max_items;
            }
            if ($per_page < 1) {
                $per_page = 8;
            }
        }

        wp_enqueue_style('code-line-blog-cards-css');
        if ($is_slider) {
            wp_enqueue_script('code-line-blog-slider-js');
        }

        $search_term = isset($_GET['clb_search']) ? sanitize_text_field(wp_unslash($_GET['clb_search'])) : '';
        $filter_category = isset($_GET['clb_category']) ? sanitize_title(wp_unslash($_GET['clb_category'])) : '';
        $filter_label = isset($_GET['clb_label']) ? sanitize_title(wp_unslash($_GET['clb_label'])) : '';
        $sort = isset($_GET['clb_sort']) ? sanitize_key(wp_unslash($_GET['clb_sort'])) : 'newest';

        if ($is_slider) {
            // Slider should always show the latest entries only.
            $search_term = '';
            $filter_category = '';
            $filter_label = '';
            $sort = 'newest';
        }

        $allowed_sorts = array('newest', 'oldest', 'title_asc', 'title_desc');
        if (!in_array($sort, $allowed_sorts, true)) {
            $sort = 'newest';
        }

        $columns = (int) $atts['columns'];
        if ($columns < 1 || $columns > 4) {
            $columns = 3;
        }

        $args = array(
            'post_type' => 'cl_blog_article',
            'posts_per_page' => $per_page,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        if ($sort === 'oldest') {
            $args['order'] = 'ASC';
        } elseif ($sort === 'title_asc') {
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
        } elseif ($sort === 'title_desc') {
            $args['orderby'] = 'title';
            $args['order'] = 'DESC';
        }

        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }

        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'cl_blog_category',
                    'field' => 'slug',
                    'terms' => sanitize_title($atts['category']),
                ),
            );
        }

        if (!empty($filter_category) || !empty($filter_label)) {
            if (!isset($args['tax_query']) || !is_array($args['tax_query'])) {
                $args['tax_query'] = array();
            }

            if (!empty($filter_category)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'cl_blog_category',
                    'field' => 'slug',
                    'terms' => $filter_category,
                );
            }

            if (!empty($filter_label)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'cl_blog_tag',
                    'field' => 'slug',
                    'terms' => $filter_label,
                );
            }

            if (count($args['tax_query']) > 1) {
                $args['tax_query']['relation'] = 'AND';
            }
        }

        $query = new WP_Query($args);

        $has_custom_articles = (int) wp_count_posts('cl_blog_article')->publish > 0;
        $show_date = get_option('code_line_blog_show_date', '0') === '1';
        $show_read_time = get_option('code_line_blog_show_read_time', '0') === '1';

        // Fallback only when custom blog articles do not exist yet.
        if (!$has_custom_articles) {
            $fallback_args = array(
                'post_type' => 'post',
                'posts_per_page' => $per_page,
                'orderby' => 'date',
                'order' => 'DESC',
            );

            if ($sort === 'oldest') {
                $fallback_args['order'] = 'ASC';
            } elseif ($sort === 'title_asc') {
                $fallback_args['orderby'] = 'title';
                $fallback_args['order'] = 'ASC';
            } elseif ($sort === 'title_desc') {
                $fallback_args['orderby'] = 'title';
                $fallback_args['order'] = 'DESC';
            }

            if (!empty($search_term)) {
                $fallback_args['s'] = $search_term;
            }

            if (!empty($filter_category)) {
                $fallback_args['category_name'] = $filter_category;
            }

            if (!empty($filter_label)) {
                $fallback_args['tag'] = $filter_label;
            }

            $query = new WP_Query($fallback_args);
        }

        $category_terms = get_terms(array(
            'taxonomy' => 'cl_blog_category',
            'hide_empty' => true,
        ));
        $label_terms = get_terms(array(
            'taxonomy' => 'cl_blog_tag',
            'hide_empty' => true,
        ));

        $form_action = remove_query_arg(array('clb_search', 'clb_category', 'clb_label', 'clb_sort'));

        ob_start();
        ?>
        <div
            class="clb-wrap clb-cols-<?php echo esc_attr($columns); ?><?php echo $is_slider ? ' clb-slider-mode' : ''; ?><?php echo $is_full_width ? ' clb-full-width' : ''; ?>"
            <?php if ($is_slider) : ?>data-clb-slider="1" style="--clb-slider-cols: <?php echo esc_attr($slides_desktop); ?>;"<?php endif; ?>
        >
            <?php if ($show_filter) : ?>
            <form class="clb-toolbar" method="get" action="<?php echo esc_url($form_action); ?>">
                <div class="clb-toolbar-grid">
                    <div class="clb-field clb-field-search">
                        <label for="clb_search">Zoeken</label>
                        <input type="text" id="clb_search" name="clb_search" value="<?php echo esc_attr($search_term); ?>" placeholder="Zoek artikel..." />
                    </div>

                    <div class="clb-field">
                        <label for="clb_category">Categorie</label>
                        <select id="clb_category" name="clb_category">
                            <option value="">Alle categorieen</option>
                            <?php if (!is_wp_error($category_terms) && !empty($category_terms)) : ?>
                                <?php foreach ($category_terms as $term) : ?>
                                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($filter_category, $term->slug); ?>>
                                        <?php echo esc_html($term->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="clb-field">
                        <label for="clb_label">Label</label>
                        <select id="clb_label" name="clb_label">
                            <option value="">Alle labels</option>
                            <?php if (!is_wp_error($label_terms) && !empty($label_terms)) : ?>
                                <?php foreach ($label_terms as $term) : ?>
                                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($filter_label, $term->slug); ?>>
                                        <?php echo esc_html($term->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="clb-field">
                        <label for="clb_sort">Sorteren</label>
                        <select id="clb_sort" name="clb_sort">
                            <option value="newest" <?php selected($sort, 'newest'); ?>>Nieuwste eerst</option>
                            <option value="oldest" <?php selected($sort, 'oldest'); ?>>Oudste eerst</option>
                            <option value="title_asc" <?php selected($sort, 'title_asc'); ?>>Titel A-Z</option>
                            <option value="title_desc" <?php selected($sort, 'title_desc'); ?>>Titel Z-A</option>
                        </select>
                    </div>
                </div>

                <div class="clb-toolbar-actions">
                    <button type="submit" class="clb-btn clb-btn-primary">Filter toepassen</button>
                    <a class="clb-btn clb-btn-ghost" href="<?php echo esc_url($form_action); ?>">Reset</a>
                </div>
            </form>
            <?php endif; ?>

            <?php if ($is_slider) : ?>
                <div class="clb-slider-controls" aria-label="Blog slider navigatie">
                    <button type="button" class="clb-slider-btn clb-slider-prev" aria-label="Vorige blogs">
                        <span aria-hidden="true">&#8249;</span>
                    </button>
                    <button type="button" class="clb-slider-btn clb-slider-next" aria-label="Volgende blogs">
                        <span aria-hidden="true">&#8250;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="clb-grid<?php echo $is_slider ? ' clb-slider-track' : ''; ?>">
                <?php if ($query->have_posts()) : ?>
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <?php
                        $post_id = get_the_ID();
                        $image = get_the_post_thumbnail_url($post_id, 'large');
                        $title = get_the_title();
                        $excerpt = get_post_meta($post_id, 'cl_blog_card_excerpt', true);
                        if (empty($excerpt)) {
                            $excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words(get_the_content(), 30);
                        }
                        // Normalize and hard-trim excerpt text to avoid rendering artifacts in multi-line clamped cards.
                        $excerpt = wp_strip_all_tags((string) $excerpt);
                        $excerpt = preg_replace('/\s+/u', ' ', trim($excerpt));
                        $max_excerpt_chars = 155;
                        if (function_exists('mb_strimwidth')) {
                            $excerpt = mb_strimwidth($excerpt, 0, $max_excerpt_chars, '...', 'UTF-8');
                        } elseif (strlen($excerpt) > $max_excerpt_chars) {
                            $excerpt = substr($excerpt, 0, $max_excerpt_chars - 3) . '...';
                        }
                        $read_time = get_post_meta($post_id, 'cl_blog_read_time', true);
                        if (empty($read_time)) {
                            $read_time = '5 min read';
                        }
                        $image_type = get_post_meta($post_id, 'cl_blog_image_type', true);
                        if (!in_array($image_type, array('photo', 'logo'), true)) {
                            $image_type = 'photo';
                        }
                        $is_logo = ($image_type === 'logo');
                        $categories = get_the_terms($post_id, 'cl_blog_category');
                        if (!$categories || is_wp_error($categories)) {
                            $categories = get_the_category($post_id);
                        }

                        $tags = get_the_terms($post_id, 'cl_blog_tag');
                        if (!$tags || is_wp_error($tags)) {
                            $tags = get_the_tags($post_id);
                        }
                        ?>
                        <article class="clb-card">
                            <a class="clb-card-link" href="<?php echo esc_url(get_permalink()); ?>">
                                <div class="clb-media <?php echo $is_logo ? 'clb-media-logo' : 'clb-media-photo'; ?> <?php echo ($this->is_background_enabled() && $is_logo) ? 'clb-media-gradient' : ''; ?>">
                                    <?php if (!empty($image)) : ?>
                                        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
                                    <?php else : ?>
                                        <div class="clb-placeholder">No Image</div>
                                    <?php endif; ?>

                                </div>

                                <div class="clb-content">
                                    <div class="clb-chips">
                                        <?php
                                        if ($tags && !is_wp_error($tags)) {
                                            foreach (array_slice($tags, 0, 5) as $tag) {
                                                echo '<span class="clb-chip">' . esc_html($tag->name) . '</span>';
                                            }
                                        } elseif ($categories && !is_wp_error($categories)) {
                                            // Show one category only when no labels are set.
                                            foreach (array_slice($categories, 0, 1) as $category) {
                                                echo '<span class="clb-chip">' . esc_html($category->name) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>

                                    <h3 class="clb-title"><?php echo esc_html($title); ?></h3>
                                    <p class="clb-excerpt"><?php echo esc_html($excerpt); ?></p>

                                    <?php if ($show_date || $show_read_time) : ?>
                                    <div class="clb-meta">
                                        <?php if ($show_date) : ?>
                                            <span><?php echo esc_html(get_the_date('j M Y')); ?></span>
                                        <?php endif; ?>
                                        <?php if ($show_date && $show_read_time) : ?>
                                            <span class="clb-dot">•</span>
                                        <?php endif; ?>
                                        <?php if ($show_read_time) : ?>
                                            <span><?php echo esc_html($read_time); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <span class="clb-read-more">Lees meer</span>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <p>Er zijn nog geen artikelen gevonden.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Native WordPress widget: codeline-blog
 */
class CodeLineBlog_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'codeline-blog',
            'codeline-blog',
            array(
                'classname' => 'codeline-blog-widget',
                'description' => 'Toon Code Line blogartikelen zonder shortcode.',
            )
        );
    }

    public function widget($args, $instance) {
        $title = isset($instance['title']) ? $instance['title'] : '';
        $per_page = isset($instance['per_page']) ? $instance['per_page'] : '9';
        $category = isset($instance['category']) ? $instance['category'] : '';
        $columns = isset($instance['columns']) ? $instance['columns'] : '3';

        echo $args['before_widget'];

        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }

        echo CodeLineBlog::get_instance()->render_blog_markup(array(
            'per_page' => $per_page,
            'category' => $category,
            'columns' => $columns,
            'full_width' => '0',
        ));

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : 'Blog';
        $per_page = isset($instance['per_page']) ? $instance['per_page'] : '9';
        $category = isset($instance['category']) ? $instance['category'] : '';
        $columns = isset($instance['columns']) ? $instance['columns'] : '3';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Titel</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('per_page')); ?>">Aantal artikelen</label>
            <input class="small-text" id="<?php echo esc_attr($this->get_field_id('per_page')); ?>" name="<?php echo esc_attr($this->get_field_name('per_page')); ?>" type="number" min="-1" value="<?php echo esc_attr($per_page); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('category')); ?>">Categorie slug (optioneel)</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('category')); ?>" name="<?php echo esc_attr($this->get_field_name('category')); ?>" type="text" value="<?php echo esc_attr($category); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('columns')); ?>">Kolommen desktop</label>
            <select id="<?php echo esc_attr($this->get_field_id('columns')); ?>" name="<?php echo esc_attr($this->get_field_name('columns')); ?>" class="widefat">
                <option value="2" <?php selected($columns, '2'); ?>>2</option>
                <option value="3" <?php selected($columns, '3'); ?>>3</option>
                <option value="4" <?php selected($columns, '4'); ?>>4</option>
            </select>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['per_page'] = sanitize_text_field($new_instance['per_page']);
        $instance['category'] = sanitize_title($new_instance['category']);
        $instance['columns'] = in_array($new_instance['columns'], array('2', '3', '4'), true) ? $new_instance['columns'] : '3';
        return $instance;
    }
}

// Initialize the plugin
CodeLineBlog::get_instance();

/**
 * Ensure there is a published Blog page with shortcode content.
 */
function code_line_blog_ensure_blog_page() {
    $blog_page = get_page_by_path('blog', OBJECT, 'page');

    if (!$blog_page) {
        $existing = get_posts(array(
            'post_type' => 'page',
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'title' => 'Blog',
            'numberposts' => 1,
        ));
        if (!empty($existing)) {
            $blog_page = $existing[0];
        }
    }

    if (!$blog_page) {
        $page_id = wp_insert_post(array(
            'post_title' => 'Blog',
            'post_name' => 'blog',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => '[code_line_blog]',
        ));

        if (is_wp_error($page_id) || !$page_id) {
            return 0;
        }

        return (int) $page_id;
    }

    $content = (string) $blog_page->post_content;
    if (strpos($content, '[code_line_blog') === false) {
        wp_update_post(array(
            'ID' => $blog_page->ID,
            'post_content' => trim($content . "\n\n[code_line_blog]"),
        ));
    }

    if ($blog_page->post_status !== 'publish') {
        wp_update_post(array(
            'ID' => $blog_page->ID,
            'post_status' => 'publish',
        ));
    }

    return (int) $blog_page->ID;
}

/**
 * Ensure Blog page is linked in the main navigation menu.
 */
function code_line_blog_ensure_menu_item($page_id) {
    if (!$page_id) {
        return;
    }

    $locations = get_nav_menu_locations();
    $menu_id = 0;
    $preferred_locations = array('primary', 'main', 'header', 'top', 'menu-1');

    foreach ($preferred_locations as $location_key) {
        if (!empty($locations[$location_key])) {
            $menu_id = (int) $locations[$location_key];
            break;
        }
    }

    if (!$menu_id) {
        $menus = wp_get_nav_menus();
        if (!empty($menus)) {
            $menu_id = (int) $menus[0]->term_id;
        }
    }

    if (!$menu_id) {
        $menu_id = (int) wp_create_nav_menu('Main Menu');
    }

    if (!$menu_id) {
        return;
    }

    $items = wp_get_nav_menu_items($menu_id);
    if (!empty($items)) {
        foreach ($items as $item) {
            if ((int) $item->object_id === (int) $page_id && $item->object === 'page') {
                return;
            }
        }
    }

    wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-title' => 'Blog',
        'menu-item-object' => 'page',
        'menu-item-object-id' => $page_id,
        'menu-item-type' => 'post_type',
        'menu-item-status' => 'publish',
    ));

    foreach ($preferred_locations as $location_key) {
        if (array_key_exists($location_key, $locations) && empty($locations[$location_key])) {
            $locations[$location_key] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
            break;
        }
    }
}

/**
 * Setup helper for creating the Blog page and linking it in menu.
 */
function code_line_blog_ensure_blog_page_and_menu() {
    $page_id = code_line_blog_ensure_blog_page();
    code_line_blog_ensure_menu_item($page_id);
}

/**
 * Run setup once in admin for existing installations.
 */
function code_line_blog_maybe_setup_blog_page_and_menu() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (get_option('code_line_blog_setup_done') === '1') {
        return;
    }

    code_line_blog_ensure_blog_page_and_menu();
    update_option('code_line_blog_setup_done', '1');
}

/**
 * Activation callback.
 */
function code_line_blog_activate() {
    code_line_blog_ensure_blog_page();
    update_option('code_line_blog_setup_done', '1');
    flush_rewrite_rules();
}

/**
 * Deactivation callback.
 */
function code_line_blog_deactivate() {
    flush_rewrite_rules();
}

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'code_line_blog_activate');

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'code_line_blog_deactivate');
