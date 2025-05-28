select
  *,
  Roles.Name as RoleName,
  Resources.Controller as Controller
from
  RolesResources
left join
  Roles on Roles.RoleId = RolesResources.RoleId
left join
  Resources on Resources.ResourceId = RolesResources.ResourceId
order by 
  RoleId