DELETE FROM UserClients
WHERE
    UserClients.UserId = ? AND
    UserClients.Sequence not in
    (
        SELECT Sequence FROM UserClients 
        WHERE UserId = ?
        ORDER by Sequence DESC limit 3
    )
