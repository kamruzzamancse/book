<?php
/*
 * Plugin Name: Book
 * Description: Custom plugin for managing books with a gallery.
 * Version: 1.0.1
 * Author: Md. Kamruzzaman
 * Author URI:  https://kamruzzaman.great-site.net/
 * Text Domain: smarto-book-plugin
*/

// book gallery plugin class
class BookGalleryPlugin {

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
        add_action('add_meta_boxes', [$this, 'add_custom_meta_box']);
        add_action('save_post', [$this, 'save_custom_meta_box']);
        add_shortcode('books', [$this, 'book_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_load_more_books', [$this, 'load_more_books']);
        add_action('wp_ajax_nopriv_load_more_books', [$this, 'load_more_books']);
    }

    // Register custom post type 'book'
    public function register_post_type() {
        $args = array(
            'public' => true,
            'label'  => 'Books',
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-book', // Add a dashicon
        );
        register_post_type('book', $args);
    }

    // Register custom taxonomy 'book_category'
    public function register_taxonomy() {
        $args = array(
            'label' => 'Book Categories',
            'rewrite' => array('slug' => 'book-category'),
            'hierarchical' => true,
        );
        register_taxonomy('book_category', 'book', $args);
    }

    // Add custom meta box for book gallery
    public function add_custom_meta_box() {
        add_meta_box(
            'book_gallery_meta_box',
            'Book Gallery',
            [$this, 'display_custom_meta_box'],
            'book',
            'normal',
            'high'
        );
    }

    // Display the custom meta box
    public function display_custom_meta_box($post) {
        wp_nonce_field(basename(__FILE__), 'book_gallery_nonce');
        $gallery = get_post_meta($post->ID, 'book_gallery', true);
        echo '<input type="text" name="book_gallery" value="' . esc_attr($gallery) . '" size="25" />';
    }

    // Save the custom meta box data
    public function save_custom_meta_box($post_id) {
        if (!isset($_POST['book_gallery_nonce']) || !wp_verify_nonce($_POST['book_gallery_nonce'], basename(__FILE__))) {
            return $post_id;
        }
        $new_meta_value = (isset($_POST['book_gallery']) ? sanitize_text_field($_POST['book_gallery']) : '');
        update_post_meta($post_id, 'book_gallery', $new_meta_value);
    }

    // Enqueue necessary scripts and styles
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('book-gallery-script', plugin_dir_url(__FILE__) . 'book-gallery.js', array('jquery'), null, true);
        wp_localize_script('book-gallery-script', 'book_gallery_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
        wp_enqueue_style('book-gallery-style', plugin_dir_url(__FILE__) . 'book-gallery.css', array(), '1.0.0', 'all');
    }

    // Shortcode to display books with category filter and load more button
    public function book_shortcode($atts) {
        $atts = shortcode_atts(array('per_page' => 5), $atts, 'books');
        ob_start();
        $this->display_books($atts['per_page']);
        return ob_get_clean();
    }

    // Display books and category filter
    private function display_books($per_page) {
        $categories = get_terms(array('taxonomy' => 'book_category', 'hide_empty' => true));
        ?>
        <div class="book-category-filter">
            <button class="category-button" data-category="all">All</button>
            <?php foreach ($categories as $category) : ?>
                <button class="category-button" data-category="<?php echo esc_attr($category->slug); ?>">
                    <?php echo esc_html($category->name); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div id="book-container">
            <?php
            $this->get_books($per_page);
            ?>
        </div>
        <button id="load-more-books">Load More</button>
        <?php
    }

    // Query and display books
    private function get_books($per_page, $category = '') {
        $args = array(
            'post_type' => 'book',
            'posts_per_page' => $per_page,
        );
        if (!empty($category) && $category !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'book_category',
                    'field' => 'slug',
                    'terms' => $category,
                ),
            );
        }
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                ?>
                <div class="book-item">
                    <?php the_post_thumbnail(); ?>
                </div>
                <?php
            }
        }
        wp_reset_postdata();
    }

    // Handle AJAX request for loading more books
    public function load_more_books() {
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 5;
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $this->get_books($per_page * $page, $category);
        wp_die();
    }
}

new BookGalleryPlugin();
?>

