<?php
include "db.php";

$result = $conn->query("SELECT * FROM users");
?>

<h2>Manage Users</h2>

<table border="1">

<tr>
<th>ID</th>
<th>Username</th>
<th>Role</th>
<th>Action</th>
</tr>

<?php

while($row = $result->fetch_assoc()){

echo "<tr>
<td>{$row['id']}</td>
<td>{$row['username']}</td>
<td>{$row['role']}</td>

<td>
<a href='edit_user.php?id={$row['id']}'>Edit</a>
<a href='delete_user.php?id={$row['id']}'>Delete</a>
</td>

</tr>";

}

?>

</table>