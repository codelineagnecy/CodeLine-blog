<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
wp_enqueue_style('code-line-blog-cards-css');
?>

<main class="clb-archive-main">
    <div class="clb-archive-inner">
        <?php echo CodeLineBlog::get_instance()->render_blog_markup(array(
            'per_page'    => '-1',
            'columns'     => '3',
            'show_filter' => '1',
            'layout'      => 'grid',
        )); ?>
    </div>
</main>

<?php
get_footer();
