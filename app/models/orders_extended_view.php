<?php

namespace models;

use database\DBView;

class orders_extended_view extends DBView {
    protected string $name = 'OrdersExtended';
    protected string $create_stmt = 'CREATE VIEW OrdersExtended as
        select
            Orders.*,
            OrdersSubtotals.SubtotalGross,
            OrdersSubtotals.SubtotalNet
        from 
            Orders
        left join OrdersSubtotals on Orders.OrderID = OrdersSubtotals.OrderID
    ';
}