<?php

namespace models;

use database\DBView;

class orders_subtotals_view extends DBView {
    protected string $name = 'OrdersSubtotals';
    protected string $create_stmt = 'CREATE VIEW OrdersSubtotals as
        select 
            OrderID, 
            Sum(TotalGross) as SubtotalGross,
            Sum(TotalNet) as SubtotalNet
        from 
            OrderDetailsExtended
        group by
            OrderID';
}