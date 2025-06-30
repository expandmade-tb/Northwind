<?php

namespace controller;

use dbgrid\DbGrid;
use models\orders_extended_view;

class OrdersExtended extends GridController {

    function __construct() {
        parent::__construct();
        $this->grid = new DbGrid(new orders_extended_view());
        $this->grid->grid_title = '';
        $this->grid->limit = 20;
        $this->grid->Fields('OrderID,CustomerID,OrderDate,ShipName,SubtotalGross,SubtotalNet');
        $this->grid->fieldTitles('OrderID,CustomerID,OrderDate,ShipName,SubtotalGross,SubtotalNet','Order,Customer,Order Date,Ship Name,Subtotal Gross,Subtotal Net');
        $this->grid->formatField('SubtotalGross', [$this, 'formatAmt']);
        $this->grid->formatField('SubtotalNet', [$this, 'formatAmt']);
        $this->grid->searchFields('CustomerID,ShipName');
        $this->grid->fieldAlign('SubtotalGross','right');
        $this->grid->fieldAlign('SubtotalNet','right');
    }

    public function formatAmt($src, $value, $column) : string {
        return number_format(round($value, 2), 2);
    }
}