<?php

namespace models;

use database\DBView;

class orderdetails_extended_view extends DBView {
    protected string $name = 'OrderDetailsExtended';
    protected string $create_stmt = 'CREATE VIEW OrderDetailsExtended as
        select 
            *,
            (UnitPrice*Quantity) as TotalGross,
            (UnitPrice*Quantity*(1-Discount)/100)*100 AS TotalNet
        from 
            OrderDetails
    ';
}