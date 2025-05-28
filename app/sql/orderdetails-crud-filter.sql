 select
    OrderDetails.*,
	Products.ProductName
 from 
    OrderDetails
 left join
    Products on Products.ProductID = OrderDetails.ProductID
 where 
	OrderID = ?
 order by
	Products.ProductName   