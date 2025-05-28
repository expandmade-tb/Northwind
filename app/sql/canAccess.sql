select
    Resources.Controller
from
    Users, Roles, RolesResources, Resources
where
    Users.UserId = ? and
    Roles.RoleId = Users.RoleId and
    Roles.RoleId = RolesResources.RoleId and
    RolesResources.ResourceId = Resources.ResourceId and
    Resources.Controller = ?;