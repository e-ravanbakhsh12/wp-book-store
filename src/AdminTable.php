<?php

namespace WPBookStore;

use WP_List_Table;

/**
 * Class AdminTable
 *
 * This class extends WP_List_Table to display custom database data in a table format.
 *
 * @package WPBookStore
 */
class AdminTable extends WP_List_Table
{
    /**
     * AdminTable constructor.
     *
     * Sets up the table properties.
     */
    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'book',
            'plural' => 'books',
            'ajax' => false
        ));
    }

    /**
     * Prepare the items for the table to display.
     */
    public function prepareItems(): void
    {
        // Query your custom database table to retrieve data
        $data = $this->getCustomData();

        $columns = $this->get_columns();
        $hidden = array(); // Hidden columns
        $sortable = $this->getSortableColumns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        // Paginate the items
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ));

        // Slice the data based on pagination
        $this->items = array_slice($data, (($current_page - 1) * $per_page), $per_page);
    }

    /**
     * Define the columns for the table.
     *
     * @return array The columns for the table.
     */
    public function get_columns(): array
    {
        global $textDomain;
        return array(
            'ID' => __('ID', $textDomain),
            'post_id' => __('Post ID', $textDomain),
            'isbn' => __('ISBN', $textDomain)
        );
    }

    /**
     * Define sortable columns.
     *
     * @return array The sortable columns.
     */
    public function getSortableColumns(): array
    {
        return array(
            'ID' => array('ID', false),
            'post_id' => array('post_id', false),
        );
    }

    /**
     * Render each column of the table.
     *
     * @param array  $item         The current item being rendered.
     * @param string $column_name  The name of the column.
     *
     * @return string The content for the column.
     */
    public function column_default($item, $column_name): string
    {
        switch ($column_name) {
            case 'ID':
            case 'post_id':
            case 'isbn':
                return $item[$column_name];
            default:
                return ''; // Return empty string for other columns
        }
    }

    /**
     * Fetch custom data from the database.
     *
     * @return array The custom data fetched from the database.
     */
    private function getCustomData(): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'books_info';
        $data = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        return $data;
    }

    /**
     * Render the table.
     */
    public function displayTable(): void
    {
        $this->prepareItems();
        $this->display();
    }
}