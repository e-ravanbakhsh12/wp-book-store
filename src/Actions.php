<?php
namespace WPBookStore;

use WP_Error;

/**
 * Class Actions
 *
 * This class handles various actions related to book store functionality.
 *
 * @package WPBookStore
 */
class Actions
{
    /** @var Actions|null Instance of Actions class */
    private static $instance;

    /** Constructor */
    private function __construct()
    {
    }

    /**
     * Get the singleton instance of the Actions class.
     *
     * @return Actions The singleton instance of the Actions class.
     */
    public static function getInstance(): Actions
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Add main admin menu for the book store.
     *
     * @param string $menuName    The name of the menu.
     * @param string $slug        The slug for the menu.
     * @param string $capability  The capability required to access the menu.
     */
    public function addMainAdminMenu(string $menuName, string $slug, string $capability = "manage_options"): void
    {
        add_action('admin_menu', function () use ($menuName, $slug, $capability) {
            add_menu_page(
                $menuName,
                $menuName,
                $capability,
                $slug,
                [$this, 'displayStoreTable']
            );
        });
    }

    /**
     * Generate the book table in the database.
     */
    public function generateBookTable(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'books_info';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "
        CREATE TABLE IF NOT EXISTS $table_name (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
        isbn varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
        PRIMARY KEY (ID),
        KEY post_id (post_id)
        ) ENGINE=InnoDB $charset_collate;
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Generate the book post type.
     */
    public function generatePostType(): void
    {
        add_action('init', function () {
            if (!post_type_exists('Book')) {
                global $textDomain;

                // Set labels for Book post type
                $labels = array(
                    'name'          => esc_html__('Book', $textDomain),
                    'singular_name' => esc_html__('Book', $textDomain),
                    'search_items'  => esc_html__('Search Book', $textDomain),
                    'all_items'     => esc_html__('All Book', $textDomain),
                    'parent_item'   => esc_html__('Parent Book', $textDomain),
                    'add_new'   => esc_html__('Add New Book', $textDomain),
                    'not_found'   => esc_html__('No Book Found', $textDomain),
                    'archives'   => esc_html__('Book archives', $textDomain),
                    'insert_into_item'   => esc_html__('Inset Into Book', $textDomain),
                    'item_link'   => esc_html__('Book Link', $textDomain),
                    'edit_item'     => esc_html__('Edit Book', $textDomain),
                    'new_item'     => esc_html__('Add New Book', $textDomain),
                    'add_new_item'  => esc_html__('Add New Book', $textDomain),
                    'view_item'     => esc_html__('View Book', $textDomain),
                    'view_items'     => esc_html__('View Book', $textDomain),
                    'item_updated'   => esc_html__('Book Update', $textDomain),
                    'item_trashed'   => esc_html__('Book Trash', $textDomain),
                    'menu_name'     => esc_html__('Book', $textDomain)
                );

                // Set main arguments for Book post type
                $args = array(
                    'labels'          => $labels,
                    'singular_label'  => esc_html__('Book', $textDomain),
                    'public'          => true,
                    'capability_type' => 'post',
                    'show_ui'         => true,
                    'show_in_menu'    => true,
                    'hierarchical'    => false,
                    'menu_position'   => 10,
                    'menu_icon'       => 'dashicons-media-document',
                    'supports'        => ['title', 'editor', 'comments', 'revisions', 'author', 'excerpt', 'thumbnail', 'custom-fields'],
                    'rewrite'         => array(
                        'slug' => 'Book',
                        'with_front' => false
                    ),
                    // 'has_archive'=>'Book',
                );


                // Register Book post type
                register_post_type('Book', $args);

                self::generateTaxonomy(__('publisher', $textDomain), 'book', 'book-publisher');
                self::generateTaxonomy(__('author', $textDomain), 'book', 'author-publisher');
            }
        });
    }

    /**
     * Add meta box for the book options.
     */
    public function addMetaBox(): void
    {
        add_action('add_meta_boxes', function () {
            add_meta_box(
                'wp_book',
                'Book Options',
                [__class__, 'renderCustomMetaBox'],
                'book',
                'normal',
                'default'
            );
        });
    }

    /**
     * Save meta box data for the book.
     */
    public function saveMetaBoxData(): void
    {
        add_action('save_post_book', function ($post_id) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            if (isset($_POST['book_ISBN'])) {
                self::manageBookData('update', $post_id, sanitize_text_field($_POST['book_ISBN']));
            }
        });
    }

    /**
     * Generate taxonomy for the book.
     *
     * @param string $taxName      The name of the taxonomy.
     * @param string $taxPostType  The post type for the taxonomy.
     * @param string $slug         The slug for the taxonomy.
     */
    public function generateTaxonomy(string $taxName, string $taxPostType, string $slug): void
    {
        global $textDomain;
        $tax_labels = array(
            'name'          => sprintf(esc_html__('%s', $textDomain), $taxName),
            'singular_name' => sprintf(esc_html__('%s', $textDomain), $taxName),
            'search_items'  => sprintf(esc_html__('Search %s', $textDomain), $taxName),
            'all_items'     => sprintf(esc_html__('All %s', $textDomain), $taxName),
            'parent_item'   => sprintf(esc_html__('Parent %s', $textDomain), $taxName),
            'no_terms'      => sprintf(esc_html__('No %s', $textDomain), $taxName),
            'item_link'     => sprintf(esc_html__('%s Link', $textDomain), $taxName),
            'edit_item'     => sprintf(esc_html__('Edit %s', $textDomain), $taxName),
            'view_item'     => sprintf(esc_html__('View %s', $textDomain), $taxName),
            'view_items'    => sprintf(esc_html__('View %s', $textDomain), $taxName),
            'new_item'      => sprintf(esc_html__('Add New %s', $textDomain), $taxName),
            'add_new_item'  => sprintf(esc_html__('Add New %s', $textDomain), $taxName),
            'item_updated'  => sprintf(esc_html__('Update %s', $textDomain), $taxName),
            'item_trashed'  => sprintf(esc_html__('Trash %s', $textDomain), $taxName),
            'menu_name'     => sprintf(esc_html__('%s', $textDomain), $taxName)
        );
        $tax_args = array(
            'labels'          => $tax_labels,
            'public'          => true,
            'show_ui'         => true,
            'hierarchical'    => false,
            'show_in_quick_edit'    => true,
            'show_admin_column'    => true,
            'rewrite'         => array(
                'slug' => $slug,
                'with_front' => false,
                'hierarchical' => false,
            ),

        );
        register_taxonomy($slug, [$taxPostType], $tax_args);
    }

    /**
     * Render custom meta box for the book.
     *
     * @param object $post The post object.
     */
    public function renderCustomMetaBox($post): void
    {
        $data = self::manageBookData('get', $post->ID);
        $value = isset($data->isbn) ? esc_attr($data->isbn) : '';
        echo '<label for="book_ISBN">ISBN:</label>';
        echo '<input type="text" id="book_ISBN" name="book_ISBN" value="' . $value . '" />';
    }

    /**
     * Display the book store table.
     */
    public function displayStoreTable(): void
    {
        $table = new AdminTable();
        $table->displayTable();
    }

    /**
     * Manage book data.
     *
     * @param string $type   The type of action (update or get).
     * @param int    $postId The post ID.
     * @param string|null $ISBN The ISBN of the book.
     *
     * @return mixed False on failure, object on success, WP_Error on database error.
     */
    private function manageBookData(string $type = 'update', int $postId, ?string $ISBN = null)
    {
        global $wpdb;
        $postId = esc_sql($postId);
        $ISBN = esc_sql($ISBN);
        $tableName = $wpdb->prefix . 'books_info';

        if ($type === 'update' && !empty($ISBN)) {
            $response = $wpdb->update(
                $tableName,
                ['isbn' => $ISBN],
                ['post_id' => $postId]
            );
            if (!$response) {
                $wpdb->insert(
                    $tableName,
                    [
                        'isbn' => $ISBN,
                        'post_id' => $postId
                    ],
                );
            }
        } elseif ($type === 'get') {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tableName} WHERE post_id = %d", $postId));

            if ($wpdb->last_error) {
                return new WP_Error('database_error', $wpdb->last_error);
            }

            return $row;
        }

        return false;
    }
}
