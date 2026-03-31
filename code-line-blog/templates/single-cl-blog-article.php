<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main class="clb-single">
    <?php while (have_posts()) : the_post(); ?>
        <?php
        $card_excerpt = get_post_meta(get_the_ID(), 'cl_blog_card_excerpt', true);
        $summary = '';
        $author_role = get_the_author_meta('description');
        $category_labels = array();

        $category_terms = get_the_terms(get_the_ID(), 'cl_blog_category');
        if (!empty($category_terms) && !is_wp_error($category_terms)) {
            foreach ($category_terms as $term) {
                if (!empty($term->name)) {
                    $category_labels[] = $term->name;
                }
            }
        } else {
            $wp_categories = get_the_category();
            if (!empty($wp_categories) && !is_wp_error($wp_categories)) {
                foreach ($wp_categories as $wp_category) {
                    if (!empty($wp_category->name)) {
                        $category_labels[] = $wp_category->name;
                    }
                }
            }
        }
        $category_labels = array_values(array_unique($category_labels));

        if (!empty($card_excerpt)) {
            $summary = $card_excerpt;
        } elseif (has_excerpt()) {
            $summary = get_the_excerpt();
        }

        $image_type = get_post_meta(get_the_ID(), 'cl_blog_image_type', true);
        if (!in_array($image_type, array('photo', 'logo'), true)) {
            $image_type = 'photo';
        }
        $is_logo = ($image_type === 'logo');
        ?>
        <article class="clb-single-article">
            <header class="clb-single-header">
                <?php if (!empty($category_labels)) : ?>
                    <div class="clb-single-kickers" aria-label="Categorieen">
                        <?php foreach ($category_labels as $category_label) : ?>
                            <?php
                            $label_text = function_exists('mb_strtoupper')
                                ? mb_strtoupper($category_label, 'UTF-8')
                                : strtoupper($category_label);
                            ?>
                            <span class="clb-single-kicker"><?php echo esc_html($label_text); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <h1 class="clb-single-title"><?php the_title(); ?></h1>
            </header>

            <?php if (!empty($summary)) : ?>
                <section class="clb-single-summary">
                    <?php echo wp_kses_post(wpautop($summary)); ?>
                </section>
            <?php endif; ?>

            <section class="clb-single-author-block" aria-label="Artikel info">
                <p class="clb-single-updated">Last updated: <?php echo esc_html(get_the_modified_date('j M Y')); ?></p>
                <?php if (!empty($author_role)) : ?>
                    <p class="clb-single-author-role"><?php echo esc_html($author_role); ?></p>
                <?php endif; ?>
            </section>

            <?php if (has_post_thumbnail()) : ?>
                <section class="clb-single-bottom-image <?php echo $is_logo ? 'clb-single-bottom-image-logo' : 'clb-single-bottom-image-photo'; ?>">
                    <?php the_post_thumbnail('full', array('loading' => 'eager')); ?>
                </section>
            <?php endif; ?>

            <section class="clb-single-content">
                <?php the_content(); ?>
            </section>
        </article>
    <?php endwhile; ?>
</main>

<?php
get_footer();
