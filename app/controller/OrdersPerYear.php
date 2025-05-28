<?php

namespace controller;

use database\DBView;
use dbgrid\DbGrid;
use models\orders_extended_view;

class OrdersPerYear extends GridController {

    function __construct() {
        parent::__construct();
        
        $this->grid = new Dbgrid(new orders_extended_view());
        $this->grid->grid_title = 'Orders per year';
        $this->grid->limit = 20;
        $this->grid->grid_search = '';
        $this->grid->gridSQL( DBView::getSQL('ordersperyear'));
        $this->grid->gridFields('Year,TotalOrders,TotalGross,TotalNet');
        $this->grid->fieldTitles('Year,TotalOrders,TotalGross,TotalNet','Year,Total Orders,Total Gross,Total Net');
        $this->grid->formatField('TotalGross', [$this, 'formatAmt']);
        $this->grid->formatField('TotalNet', [$this, 'formatAmt']);
        $this->grid->fieldAlign('TotalOrders','right');
        $this->grid->fieldAlign('TotalGross','right');
        $this->grid->fieldAlign('TotalNet','right');
    }

    public function formatAmt($src, $value, $column) : string {
        return number_format(round($value, 2), 2);
    }
}