<div class="topbar">

<div>

<h4>

Dashboard

</h4>

</div>

<div>

<i class="bi bi-bell fs-4 me-3"></i>

<i class="bi bi-envelope fs-4 me-3"></i>

<span>

👤

<?php

if(isset($_SESSION['admin_name']))
{
    echo $_SESSION['admin_name'];
}
elseif(isset($_SESSION['instructor_name']))
{
    echo $_SESSION['instructor_name'];
}
elseif(isset($_SESSION['student_name']))
{
    echo $_SESSION['student_name'];
}

?>

</span>

</div>

</div>