<?php

/**
 * Grid component
 * Version 1.0.1
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace grid;

final class column {
    public string $title;
    public string $align;

    function __construct(string $name) {
        $this->title = ucwords(str_replace(['_', '-', '.'], ' ', $name));
    }
}

class Grid {
    private array $columns;
    private array $rows;

    public bool $show_titles = true;

    function __construct(string $columns) {
        foreach (array_map('trim',explode(',', $columns)) as $index => $name)
            $this->columns[$name] = new column($name);
    }

    public function header (string $column, string $title) : Grid {
        $this->columns[$column]->title = $title;
        return $this;
    }

    public function align(string $column, string $align='left') : Grid {
        $this->columns[$column]->align = $align;
        return $this;
    }

    public function row (array $data ) : Grid {
        $row = [];
        
        foreach ($this->columns as $key => $value)
            $row[$key] = $data[$key]??'';
            
        $this->rows[]= $row;
        return $this;
    }

    public function render() : string {
        $result = '<table class="table table-bordered table-hover grid-table">';

        if ( $this->show_titles ) {
            $result.= '<thead><tr>';

            foreach ($this->columns as $column => $value) {
                $align = $value->align??'';
                $style = empty($align) ? '' : " style=\"text-align: $align\""; 
                $title = $value->title??'';
                $result .= "<th{$style}>$title</th>";
            }

            $result.= '</tr></thead>';
        }

        $result.= '<tbody>';

        foreach ($this->rows as $key => $row) {
            $result.= '<tr>';

            foreach ($row as $column => $value) {
                $align = $this->columns[$column]->align??'';

                $style = empty($align) ? '' : " style=\"text-align: $align\""; 
                $result .= "<td{$style} data-row=\"$key\" data-col=\"$column\">$value</td>";
            }

            $result.= '</tr>';
        }

        $result .= '<tbody></table>';
        return $result;
    }

}