# Code Line Blog

WordPress plugin to build a modern blog overview with card and slider layouts, taxonomy filters, and optional Elementor support.

## Features
- Custom post type for articles: `cl_blog_article`
- Custom taxonomies:
  - `cl_blog_category` (categories)
  - `cl_blog_tag` (labels)
- Shortcode rendering with grid and slider layouts
- Optional filter toolbar (search, category, label, sort)
- Elementor widget support (optional)
- Native WordPress widget support
- Custom single and archive templates
- REST API endpoints for article data
- Admin style settings for gradient backgrounds
- Article card meta fields (read time, card excerpt, image type)

## Requirements
- WordPress 6.0+
- PHP 7.4+
- Elementor (optional)

## Installation
1. Copy plugin folder to `wp-content/plugins/code-line-blog`.
2. Activate "Code Line Agency Blog" in WordPress admin.
3. Create articles via Code Line Blog in the admin menu.
4. Add the shortcode to a page.

## Quick Usage
Basic usage:

```
[code_line_blog]
```

Common examples:

```
[code_line_blog layout="slider" slides_desktop="3" per_page="8"]
[code_line_blog layout="grid" columns="3" show_filter="1"]
[code_line_blog category="marketing" layout="grid" show_filter="1"]
```

### Shortcode Attributes
- `per_page`: number of items to query, default `-1`
- `category`: category slug filter
- `columns`: grid columns `1` to `4`, default `3`
- `show_filter`: show toolbar in grid mode, `0` or `1`
- `layout`: `grid` or `slider`, default `slider`
- `slides_desktop`: number of visible cards in slider, `1` to `4`
- `max_items`: optional cap for slider results
- `full_width`: wrapper width mode, `0` or `1`

Notes:
- Slider mode is optimized for recent content and limits output to a safe max.
- Filter toolbar is only active in grid mode.

## Content Model
### Post type
- `cl_blog_article`

### Taxonomies
- `cl_blog_category`
- `cl_blog_tag`

### Per article meta fields
- `cl_blog_read_time`
- `cl_blog_card_excerpt`
- `cl_blog_image_type` (`photo` or `logo`)

## Admin Settings
Menu path:

`Code Line Blog > Blog stijl`

Available controls:
- Enable or disable gradient backgrounds
- Set gradient start, mid, and end colors

## REST API
Base namespace: `code-line-blog/v1`

Endpoints:
- `GET /wp-json/code-line-blog/v1/articles`
- `GET /wp-json/code-line-blog/v1/articles/{id}`

Optional query params: `per_page`, `category`

## Templates And Assets
Core files:

- `wp-content/plugins/code-line-blog/code-line-blog.php`
- `wp-content/plugins/code-line-blog/elementor-widget.php`
- `wp-content/plugins/code-line-blog/templates/archive-cl-blog-article.php`
- `wp-content/plugins/code-line-blog/templates/single-cl-blog-article.php`
- `wp-content/plugins/code-line-blog/css/blog-cards.css`
- `wp-content/plugins/code-line-blog/css/blog-single.css`
- `wp-content/plugins/code-line-blog/dist/assets/blog-slider.js`

## Troubleshooting
**No cards appear:**
- Check if published `cl_blog_article` posts exist.
- Check shortcode category slug.
- Confirm featured images are set.

**Elementor widget not visible:**
- Verify Elementor is active.
- Reload the Elementor editor.

**Filter not visible:**
- `show_filter` works only with `layout="grid"`.

## Changelog
**1.0.0**
- Initial release
- CPT and taxonomies
- Shortcode grid and slider output
- Elementor and native widget integration
- REST API endpoints
- Admin style settings

## License
GPL v2 or later
