$(document).ready(function(){
    $("#loadAttendance").load("../utils/manage_attendance.php");
    $("#modifyAttendance").load("../utils/modify_attendance.php");
    $("#loadCourseAdmin").load("./courses_admin.php");
    $("#loadModule").load("./create_module.php");
    $("#loadUsers").load("./view_users.php");
    $("#loadCreateUser").load("./create_user.php");
  });