select 
	Resources.Controller
from 
	Users, Roles
inner join
    RolesResources on RolesResources.RoleId = Roles.RoleId
inner join
    Resources on Resources.ResourceId = RolesResources.RoleResourceId
where 
	Users.UserId = ? and 
	Roles.RoleId = Users.RoleId