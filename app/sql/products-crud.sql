 select
    ProductID,
    ProductName,
    Suppliers.CompanyName as SupplierName,
    Categories.CategoryName as CategoryName,
    QuantityPerUnit,
    UnitPrice,
    UnitsInStock,
    UnitsOnOrder,
    ReorderLevel,
    Discontinued
 from 
    Products
 left join
    Suppliers on Products.SupplierID = Suppliers.SupplierID
 left join
    Categories on Products.CategoryID = Categories.CategoryID 
	