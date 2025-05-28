select
	TerritoryID, TerritoryDescription, Regions.RegionDescription as RegionID
from Territories
	left join Regions on Territories.RegionID = Regions.RegionID 