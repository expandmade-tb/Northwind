select
	substr(OrderDate,1,4) as Year,
	count(*) as TotalOrders,
	sum(SubtotalGross) as TotalGross,
	sum(SubtotalNet) as TotalNet
from
	OrdersExtended
group by
	Year