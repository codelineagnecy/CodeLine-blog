<?php
/**
 * Code Line Blog Elementor Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class Code_Line_Blog_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'code_line_blog';
    }

    public function get_title() {
        return 'Code Line Blog';
    }

    public function get_icon() {
        return 'eicon-post-list';
    }

    public function get_categories() {
        return array('general');
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => 'Blog Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $categories = get_terms(array(
            'taxonomy' => 'cl_blog_category',
            'hide_empty' => false,
        ));

        $category_options = array('' => 'Alle categorieen');
        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $category_options[$category->slug] = $category->name;
            }
        }

        $this->add_control(
            'category',
            array(
                'label' => 'Categorie filter',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $category_options,
                'default' => '',
            )
        );

        $this->add_control(
            'per_page',
            array(
                'label' => 'Aantal artikelen',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => -1,
                'step' => 1,
                'default' => 9,
                'description' => '-1 betekent: toon alles',
            )
        );

        $this->add_control(
            'columns',
            array(
                'label' => 'Kolommen desktop',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => array(
                    '2' => '2 kolommen',
                    '3' => '3 kolommen',
                    '4' => '4 kolommen',
                ),
            )
        );

        $this->add_control(
            'layout',
            array(
                'label' => 'Weergave',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'slider',
                'options' => array(
                    'grid' => 'Grid',
                    'slider' => 'Slider',
                ),
            )
        );

        $this->add_control(
            'slides_desktop',
            array(
                'label' => 'Slides op desktop',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 4,
                'step' => 1,
                'default' => 3,
                'condition' => array('layout' => 'slider'),
            )
        );

        $this->add_control(
            'max_items',
            array(
                'label' => 'Max artikelen',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 8,
                'step' => 1,
                'default' => 8,
                'condition' => array('layout' => 'slider'),
            )
        );

        $this->add_control(
            'info',
            array(
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => 'Dit widget rendert de [code_line_blog] shortcode met filters.',
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $layout = (!empty($settings['layout']) && in_array($settings['layout'], array('grid', 'slider'), true)) ? $settings['layout'] : 'slider';
        $slides_desktop = !empty($settings['slides_desktop']) ? (string) $settings['slides_desktop'] : '3';
        $max_items = !empty($settings['max_items']) ? (string) $settings['max_items'] : '8';

        $shortcode = sprintf(
            '[code_line_blog per_page="%s" category="%s" columns="%s" layout="%s" slides_desktop="%s" max_items="%s"]',
            esc_attr((string) $settings['per_page']),
            esc_attr((string) $settings['category']),
            esc_attr((string) $settings['columns']),
            esc_attr($layout),
            esc_attr($slides_desktop),
            esc_attr($max_items)
        );

        $is_elementor_editor = class_exists('\Elementor\Plugin') && (
            \Elementor\Plugin::$instance->editor->is_edit_mode() ||
            \Elementor\Plugin::$instance->preview->is_preview_mode()
        );

        if ($is_elementor_editor) {
            echo '<div class="elementor-panel-alert elementor-panel-alert-info" style="margin:12px 0;padding:12px;border-radius:8px;">';
            echo '<strong>Shortcode preview:</strong><br><code>' . esc_html($shortcode) . '</code>';
            echo '</div>';
            return;
        }

        echo do_shortcode($shortcode);
    }

    protected function content_template() {
        ?>
        <div>{{{ 'Code Line Blog widget' }}}</div>
        <?php
    }
}
