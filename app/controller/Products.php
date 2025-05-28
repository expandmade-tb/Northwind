<?php

namespace controller;

use database\DBTable;
use dbgrid\DbCrud;
use models\products_model;

class Products extends CrudController {

    function __construct() {
        parent::__construct();
        
        $this->crud = new DbCrud(new products_model());
        $this->crud->grid_show = '';
        $this->crud->grid_delete = '';
        $this->crud->limit = 15;
        $this->crud->gridSQL( DBTable::getSQL('products-crud'));
        $this->crud->gridFields('ProductID,ProductName,SupplierName,CategoryName,QuantityPerUnit,UnitPrice,UnitsInStock,UnitsOnOrder,ReorderLevel,Discontinued');
        $this->crud->addFields('ProductName,SupplierID,CategoryID,QuantityPerUnit,UnitPrice,UnitsInStock,UnitsOnOrder,ReorderLevel,Discontinued');
        $this->crud->searchFields('ProductName,SupplierName,CategoryName');
        $this->crud->fieldType('Discontinued', 'checkbox', '0,1');
        
        $this->crud->fieldTitles('ProductName,SupplierName,SupplierID,CategoryName,CategoryID,QuantityPerUnit,UnitPrice,UnitsInStock,UnitsOnOrder,ReorderLevel,Discontinued',
                                 'Product,Supplier,Supplier,Category,Category,Quantity/Unit,Price,In Stock,On Order,Reorder Level,Discontinued');

        $this->crud->setRelation('SupplierID', 'CompanyName', 'Suppliers');
        $this->crud->setRelation('CategoryID', 'CategoryName', 'Categories');
        $this->crud->setContstraints('ProductID', 'OrderDetails', 'ProductID');
    }
}