<?php

/**
 * Grid for database tables
 * Version 1.8.0
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace dbgrid;

use database\DBView;
use Exception;
use helper\Helper;
use Formbuilder\StatelessCSRF;

class DbGrid {
    // Public Properties: Configuration and Output
    public string $grid_search = '<form class="d-flex" method="post"> <input class="form-control" type="search" name="search" value="[:search]" placeholder="Search" aria-label="Search"><a href="[:script_name]clear" style="margin: 0 10px 0 -20px; display: inline-block;" title="Clear Search">x</a> <input type="submit" name="search_submit" value="Search" id="search_submit" class="btn btn-primary"/> </form>';
    public string $grid_title = '';
    public int $limit = 100; // SQL selection limit for one page
    public string $grid_sql = ''; // SQL from grid selection
    public ?array $grid_sql_params = null; // Params for grid SQL
    public string $date_fmt = 'Y-m-d'; // The output format for dates
    public string $time_fmt = 'G:i'; // The output format for time
    public bool $show_titles = true;
    public string $onRowClick = ''; // Add JavaScript double click event to each row

    // Protected Properties: Internal State and Data
    protected DBView $table; // Database table we are using
    protected array $field_titles = []; // Titles / headers for fields
    protected array $field_types = []; // The field types to deal with
    protected array $grid_fields = []; // Fields to show in grid
    protected array $field_align = []; // How to align the value
    protected array $search_fields = []; // Searchable fields
    protected array $callback_fields = []; // Fields to callback to format values
    protected string $grid_info = ''; // Additional grid information
    protected array $uri = []; // Current URI segments and query
    protected string $external_identifyer = ''; // Field used as an external identifier for rows

    // Constants for field types for better maintainability
    protected const FIELD_TYPE_TEXT = 'text';
    protected const FIELD_TYPE_INTEGER = 'integer';
    protected const FIELD_TYPE_NUMERIC = 'numeric';
    protected const FIELD_TYPE_CHECKBOX = 'checkbox';
    protected const FIELD_TYPE_SELECT = 'select';
    protected const FIELD_TYPE_DATE = 'date';
    protected const FIELD_TYPE_DATETEXT = 'datetext';
    protected const FIELD_TYPE_DATETIME = 'datetime';
    protected const FIELD_TYPE_DATALIST = 'datalist';
    protected const FIELD_TYPE_TEXTAREA = 'textarea';
    protected const FIELD_TYPE_TIMETEXT = 'timetext';
    protected const FIELD_TYPE_GRID = 'grid'; // Nested grid?

    /**
     * Constructor for the DbGrid class.
     * Initializes the grid with a database view and sets up default field types.
     * @param DBView $table The database view instance.
     */
    public function __construct(DBView $table) {
        $this->table = $table;
        $this->grid_title = $table->name();
        $this->uri = $this->currentUri();
        $this->fields($this->table->fieldlist());

        // Initialize basic datatypes based on database schema
        foreach (explode(',', $this->table->fieldlist()) as $field) {
            $field = trim($field);
            if (empty($field)) {
                continue;
            }
            switch ($this->table->fields($field)['type'] ?? '') {
                case 'INTEGER':
                    $this->fieldType($field, self::FIELD_TYPE_INTEGER);
                    break;
                case 'REAL':
                case 'NUMERIC':
                    $this->fieldType($field, self::FIELD_TYPE_NUMERIC);
                    break;
                default:
                    $this->fieldType($field, self::FIELD_TYPE_TEXT);
                    break;
            }
        }
    }

    /**
     * Sets the grid fields and initializes their titles.
     * @param string $fields Comma-separated list of field names.
     * @return DbGrid
     */
    public function fields(string $fields): DbGrid {
        $this->gridFields($fields);
        $this->field_titles = []; // Reset titles before setting new ones
        $this->fieldTitles($fields);
        return $this;
    }

    /**
     * Sets the fields to be displayed in the grid.
     * @param string $fields Comma-separated list of field names.
     * @return DbGrid
     */
    public function gridFields(string $fields): DbGrid {
        $this->grid_fields = array_map('trim', explode(',', $fields));
        return $this;
    }

    /**
     * Sets the fields that are searchable.
     * @param string $fields Comma-separated list of field names.
     * @return DbGrid
     */
    public function searchFields(string $fields): DbGrid {
        $this->search_fields = array_map('trim', explode(',', $fields));
        return $this;
    }

    /**
     * Sets the titles/headers for the grid fields.
     * If titles are not provided, they are generated from field names.
     * @param string $fields Comma-separated list of field names.
     * @param string $titles Comma-separated list of titles (optional).
     * @return DbGrid
     * @throws Exception If there is a mismatch between the number of fields and titles.
     */
    public function fieldTitles(string $fields, string $titles = ''): DbGrid {
        $aFields = array_map('trim', explode(',', $fields));
        $aTitles = [];

        if (empty($titles)) {
            foreach ($aFields as $field) {
                $aTitles[] = ucwords(str_replace(['_', '-', '.'], ' ', $field));
            }
        } else {
            $aTitles = array_map('trim', explode(',', $titles));
        }

        if (count($aFields) !== count($aTitles)) {
            throw new Exception("Mismatch of fields (" . count($aFields) . ") and titles (" . count($aTitles) . ")");
        }

        $this->field_titles = array_merge($this->field_titles, array_combine($aFields, $aTitles));
        return $this;
    }

    /**
     * Sets the type for a specific field, influencing how it's rendered.
     * @param string $field The field name.
     * @param string $type The type of the field (e.g., 'text', 'date', 'checkbox').
     * @param string $valuelist A comma-separated list of values, especially for 'select' or 'checkbox'.
     * @param int $rows For 'textarea' type, number of rows.
     * @param int $cols For 'textarea' type, number of columns.
     * @return DbGrid
     * @throws Exception If an unsupported field type is provided.
     */
    public function fieldType(string $field, string $type, string $valuelist = '', int $rows = 2, int $cols = 40): DbGrid {
        $supportedTypes = [
            self::FIELD_TYPE_TEXT, self::FIELD_TYPE_INTEGER, self::FIELD_TYPE_NUMERIC,
            self::FIELD_TYPE_CHECKBOX, self::FIELD_TYPE_SELECT, self::FIELD_TYPE_DATE,
            self::FIELD_TYPE_DATETEXT, self::FIELD_TYPE_DATETIME, self::FIELD_TYPE_DATALIST,
            self::FIELD_TYPE_TEXTAREA, self::FIELD_TYPE_TIMETEXT, self::FIELD_TYPE_GRID
        ];

        if (!in_array($type, $supportedTypes)) {
            throw new Exception("Unsupported field type: $type");
        }

        if ($type === self::FIELD_TYPE_CHECKBOX && empty($valuelist)) {
            $valuelist = '0,1';
        }

        if (in_array($type, [self::FIELD_TYPE_GRID, self::FIELD_TYPE_TEXTAREA])) {
            $this->field_types[$field] = ['type' => $type, 'values' => $valuelist, 'rows' => $rows, 'cols' => $cols];
        } else {
            $this->field_types[$field] = ['type' => $type, 'values' => $valuelist];
        }

        return $this;
    }

    /**
     * Sets the alignment for a specific field's values in the grid.
     * @param string $field The field name.
     * @param string $align The alignment (e.g., 'left', 'right', 'center').
     * @return DbGrid
     */
    public function fieldAlign(string $field, string $align = 'left'): DbGrid {
        $this->field_align[$field] = $align;
        return $this;
    }

    /**
     * Assigns a callable function to format the output of a specific field.
     * @param string $field The field name.
     * @param callable $callable The callback function.
     * @return DbGrid
     */
    public function formatField(string $field, callable $callable): DbGrid {
        $this->callback_fields[$field] = $callable;
        return $this;
    }

    /**
     * Sets the custom SQL query for the grid and its parameters.
     * @param string $sql The SQL query.
     * @param array|null $params Optional parameters for the SQL query.
     * @return DbGrid
     */
    public function gridSQL(string $sql, ?array $params = null): DbGrid {
        $this->grid_sql = $sql;
        $this->grid_sql_params = $params;
        return $this;
    }

    /**
     * Generates and returns the HTML for the database grid.
     * @param int $page The current page number.
     * @param string $orderby The field to order the grid by.
     * @return string The HTML representation of the grid.
     */
    public function grid(int $page = 1, string $orderby = ''): string {
        if (empty($orderby) && !empty($_REQUEST['orderby'])) {
            $orderby = $_REQUEST['orderby'];
        }

        ob_start();
        $this->showGrid($page, $orderby);
        $html = ob_get_clean();

        if ( $html === false )
            $html = 'error';

        return '<div id="dbv-container">' . $html . '</div>';
    }

    /**
     * Clears search parameters and redirects to the first page of the grid.
     */
    public function clear(): void {
        $to = '/' . $this->uri['class'];
        Helper::redirect("$to/grid/1");
    }

    /**
     * Returns the underlying DBView model instance.
     * @return DBView
     */
    public function model(): DBView {
        return $this->table;
    }

    /**
     * Returns the total number of rows based on the current grid SQL and search.
     * @return int
     */
    public function rowcount(): int {
        // Apply search conditions before counting
        $this->applyGridSearchConditions();
        return $this->table->count($this->grid_sql, $this->grid_sql_params);
    }

    /**
     * Sets a field to be used as an external identifier for rows (e.g., for data attributes).
     * @param string $field The field name.
     */
    public function addExternalIdentifyer(string $field): void {
        $this->external_identifyer = $field;
    }

    /**
     * Main method to generate the grid HTML structure.
     * @param int $page The current page number.
     * @param string $orderby The field to order by.
     */
    protected function showGrid(int $page, string $orderby): void {
        $offset = ($page - 1) * $this->limit;

        // Header bar with title and search form
        $this->renderHeaderBar();
        echo $this->grid_info;

        // Apply search conditions to the table model
        $this->applyGridSearchConditions();
        $total_rows = $this->rowcount(); // Count after applying search

        // Fetch data
        if (empty($orderby)) {
            $data = $this->table->limit($this->limit)->offset($offset)->findAll($this->grid_sql, $this->grid_sql_params);
        } else {
            $data = $this->table->limit($this->limit)->offset($offset)->orderby($orderby)->findAll($this->grid_sql, $this->grid_sql_params);
        }

        $total_pages = (int)ceil($total_rows / $this->limit);

        // Start grid table
        echo '<table class="table table-bordered table-hover dbv-table" data-token="' . $this->token() . '">';

        // Grid table header titles
        $this->renderGridHeader($orderby);

        // Grid table rows
        $this->renderGridBody($data);

        // End grid table
        echo '</table>';

        // Footer bar with pagination
        $this->renderFooterBar($page, $total_pages);
    }

    /**
     * Renders the HTML for the grid table header.
     * @param string $orderby The current order-by field.
     */
    protected function renderGridHeader(string $orderby): void {
        if ($this->show_titles === false) {
            return;
        }

        echo '<thead><tr>';
        
        foreach ($this->grid_fields as $field) {
            $isSearchField = in_array($field, $this->search_fields ?? [], true);
            $marker = $isSearchField ? '&nbsp;<i class="bi bi-search"></i>' : '';

            $align = $this->field_align[$field] ?? '';
            $style = $align !== '' ? " style=\"text-align: $align\"" : '';

            $title = $this->field_titles[$field] ?? '';
            $sort = ($field === $orderby) ? '<i class="bi bi-sort-alpha-down">&nbsp;</i>' : '';

            $link = '/' . $this->uri['class'] . $this->buildQuery('orderby', $field);
            $html = '<span class="page-item"><a class="page-link" href="' . htmlspecialchars($link) . '">' . $sort . htmlspecialchars($title) . $marker . '</a></span>';

            echo "<th{$style}>$html</th>";
        }

        echo '</tr></thead>';
    }

    /**
     * Renders the HTML for the grid table body (rows and cells).
     * @param array $data The data to display in the grid.
     */
    protected function renderGridBody(array $data): void {
        echo '<tbody>';

        foreach ($data as $column) {
            $onclick = empty($this->onRowClick) ? '' : " onclick=\"" . htmlspecialchars($this->onRowClick) . "\"";

            $dataExternalId = '';
            if (!empty($this->external_identifyer) && isset($column[$this->external_identifyer])) {
                $externalId = htmlspecialchars($column[$this->external_identifyer]);
                $dataExternalId = " data-externalid='{$externalId}'";
            }

            echo "<tr{$onclick}{$dataExternalId}>";

            foreach ($this->grid_fields as $field) {
                $value = $column[$field] ?? null; // Handle potential missing keys safely
                $formattedValue = $this->formatGridCellValue($field, $value, $column);

                $align = $this->field_align[$field] ?? '';
                $style = empty($align) ? '' : " style=\"text-align: {$align}\"";

                echo "<td{$style}>{$formattedValue}</td>";
            }

            echo '</tr>';
        }

        echo '</tbody>';
    }

    /**
     * Formats a single cell's value based on its type and registered callbacks.
     * @param string $field The field name.
     * @param mixed $value The raw cell value.
     * @param array $row The entire row data.
     * @return string The formatted HTML for the cell.
     */
    protected function formatGridCellValue(string $field, $value, array $row): string {
        switch ($this->field_types[$field]['type'] ?? '') {
            case self::FIELD_TYPE_DATE:
            case self::FIELD_TYPE_DATETEXT:
                if (!empty($value)) {
                    $value = date($this->date_fmt, (int)$value); // Ensure timestamp
                }
                break;
            case self::FIELD_TYPE_TIMETEXT:
                if (!empty($value)) {
                    $value = gmdate($this->time_fmt, (int)$value); // Ensure timestamp
                }
                break;
            case self::FIELD_TYPE_DATETIME:
                if (!empty($value)) {
                    $value = date($this->date_fmt . ' ' . $this->time_fmt, (int)$value); // Ensure timestamp
                }
                break;
            case self::FIELD_TYPE_CHECKBOX:
                $values = explode(',', $this->field_types[$field]['values']);
                $checked = (array_search($value, $values) == 1) ? 'checked' : '';
                $value = '<input type="checkbox" class="form-check-input" ' . $checked . ' readonly >';
                break;
        }

        if (isset($this->callback_fields[$field])) {
            // Callback function gets 'grid', value, and the full row data
            $value = call_user_func($this->callback_fields[$field], 'grid', $value, $row);
        }

        // Basic HTML escaping for values not explicitly formatted as HTML by callbacks
        if (is_string($value) && strpos($value, '<') === false && strpos($value, '>') === false) {
             return htmlspecialchars($value);
        }
        return (string)$value; // Cast to string for other types
    }

    /**
     * Parses the current URI into segments for easier access.
     * @return array An associative array containing URI components.
     */
    protected function currentUri(): array {
        $result = parse_url(urldecode($_SERVER['REQUEST_URI'] ?? ''));
        $path = ltrim($result["path"] ?? '', '/'); // Remove leading slash
        $uriSegments = explode("/", $path);

        $class = $uriSegments[0] ?? '';
        $method = $uriSegments[1] ?? '';
        $id = $uriSegments[2] ?? '';
        $query = $result['query'] ?? '';

        return ['path' => $path, 'uri' => $class . '/' . $method, 'class' => $class, 'method' => $method, 'id' => $id, 'query' => $query];
    }

    /**
     * Builds a query string by modifying or adding a specific variable.
     * @param string $var The query variable name.
     * @param string $value The value for the query variable (empty to unset).
     * @return string The new query string prefixed with '?' or empty if no query.
     */
    protected function buildQuery(string $var, string $value = ''): string {
        $qry = [];
        parse_str($this->uri['query'] ?? '', $qry);

        if (empty($value)) {
            unset($qry[$var]);
        } else {
            $qry[$var] = $value;
        }

        $result = http_build_query($qry);
        return empty($result) ? '' : '?' . $result;
    }

    /**
     * Applies search conditions to the table model based on $_REQUEST['search'] and $this->search_fields.
     * @return bool True if search conditions were applied, false otherwise.
     */
    protected function applyGridSearchConditions(): bool {
        if (empty($this->grid_search) || empty($this->search_fields)) {
            return false;
        }

        $search = $_REQUEST["search"] ?? '';
        if (empty($search)) {
            return false;
        }

        $value = "%$search%";
        foreach ($this->search_fields as $field) {
            // Using coalesce to handle NULL values in search fields
            $this->table->where("coalesce($field, '')", $value, 'like', 'or');
        }
        return true;
    }

    /**
     * Renders the HTML for the header bar, including the grid title and search form.
     */
    protected function renderHeaderBar(): void {

        if (!empty($this->grid_title)) {
            echo '<h4 class="">' . htmlspecialchars($this->grid_title) . '</h4>';
        }

        if (empty($this->grid_search)) {
            return;
        }

        // Handle search form submission and redirect
        if (isset($_REQUEST["search_submit"])) {
            $search = $_REQUEST["search"] ?? '';
            $to = '/' . $this->uri['class'];
            $query = empty($search) ? $this->buildQuery('search') : $this->buildQuery('search', $search);
            Helper::redirect("$to/grid/1{$query}");
            // It's crucial to exit or stop further execution after a redirect.
            // For a web context, typically `exit()` or `die()` would follow `Helper::redirect()`.
            // As this is a library method, the caller needs to handle the exit.
            return;
        } else {
            $search = $_REQUEST["search"] ?? '';
        }

        $uri = "/{$this->uri['class']}/";
        echo '<table class="table table-bordered table-hover dbv-headerbar"><tr>';

        $gridSearchForm = str_replace(['[:script_name]', '[:search]'], [htmlspecialchars($uri), htmlspecialchars($search)], $this->grid_search);
        echo '<td>' . $gridSearchForm . '</td>';

        echo '</tr></table>';
    }

    /**
     * Renders the HTML for the pagination footer bar.
     * @param int $currentPage The current page number.
     * @param int $totalPages The total number of pages.
     * @param int $maxPages The maximum number of page links to show at once.
     */
    protected function renderFooterBar(int $currentPage, int $totalPages, int $maxPages = 5): void {
        if ($totalPages <= 1) {
            return;
        }
    
        $query = $this->buildQuery('search', $_REQUEST["search"] ?? '');
        echo '<div class="dbc-footerbar">';
        $c = $currentPage - 1; // Used for calculation of min/max page range
    
        if ($c < 1) {
            $c = 1;  // This ensures $c is never less than 1
        }
    
        // Calculate the range of pages to display
        $min = (int)floor(($c) / $maxPages) * $maxPages + 1;
        $max = $min + $maxPages; // Next block start
    
        if ($max > $totalPages + 1) { // Adjust max if it exceeds total pages
            $max = $totalPages + 1;
        }
    
        $uri = "/{$this->uri['class']}";
        echo '<nav aria-label="Page navigation"><ul class="pagination">';
    
        // "First" and "Previous" links
        // No need to check if $min > 1 because $min will always be >= 2
        $link = $uri . "/grid/1{$query}";
        echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($link) . '">First</a></li>';
    
        // Handle Previous Page
        $prevPage = $min > 1 ? $min - 1 : 1;  // Safeguard to ensure prevPage is never less than 1
        $link = $uri . "/grid/{$prevPage}{$query}";
        echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($link) . '">Previous</a></li>&nbsp;';
    
        // Page numbers
        for ($i = $min; $i < $max; $i++) {
            $link = $uri . "/grid/{$i}{$query}";
            if ($i == $currentPage) {
                echo '<li class="page-item active" aria-current="page"><a class="page-link" href="' . htmlspecialchars($link) . '">' . $i . '</a></li> ';
            } else {
                echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($link) . '">' . $i . '</a></li>';
            }
        }
    
        // "Next" and "Last" links
        if ($max <= $totalPages) { // Check if there are more pages beyond the current block
            $nextPage = $max; // Link to the first page of the next block
            $link = $uri . "/grid/{$nextPage}{$query}";
            echo '&nbsp;<li class="page-item"><a class="page-link" href="' . htmlspecialchars($link) . '">Next</a></li>';
            $link = $uri . "/grid/{$totalPages}{$query}";
            echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($link) . '">Last</a></li>';
        }
    
        echo '</ul></nav></div>';
    }
        
    /**
     * Generates a CSRF token for security.
     * @return string The generated CSRF token.
     */
    private function token(): string {
        $csrf_generator = new StatelessCSRF(Helper::env('app_secret', 'empty_secret'));
        $csrf_generator->setGlueData('ip', $_SERVER['REMOTE_ADDR'] ?? ''); // Null coalesce for safety
        $csrf_generator->setGlueData('user-agent', $_SERVER['HTTP_USER_AGENT'] ?? ''); // Null coalesce for safety
        $token = $csrf_generator->getToken(Helper::env('app_identifier', 'empty_identifier'), time() + 900); // Valid for 15 mins.
        return $token;
    }
}