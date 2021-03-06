<html>
    <head>
        <title>ZK Test</title>
    </head>
    
    <body>
<?php
    include("ZKLib.php");
    include("ZKLib/User.php");
    
    $zk = new ZKLib("192.168.1.201", 4370);
    
    $ret = $zk->connect();
    if ( $ret ): 
        $zk->disable();
    ?>
        
        <table border="1" cellpadding="5" cellspacing="2">
            <tr>
                <td><b>Status</b></td>
                <td>Connected</td>
                <td><b>Version</b></td>
                <td><?php echo $zk->getVersion() ?></td>
                <td><b>OS Version</b></td>
                <td><?php echo $zk->getOs() ?></td>
                <td><b>Platform</b></td>
                <td><?php echo $zk->getPlatform() ?></td>
            </tr>
            <tr>
                <td><b>Firmware Version</b></td>
                <td><?php echo $zk->getPlatformVersion() ?></td>
                <td><b>WorkCode</b></td>
                <td><?php echo $zk->getWorkCode() ?></td>
                <td><b>SSR</b></td>
                <td><?php echo $zk->getSsr() ?></td>
                <td><b>Pin Width</b></td>
                <td><?php echo $zk->getPinWidth() ?></td>
            </tr>
            <tr>
                <td><b>Face Function On</b></td>
                <td><?php echo $zk->getFaceOn() ?></td>
                <td><b>Serial Number</b></td>
                <td><?php echo $zk->getSerialNumber() ?></td>
                <td><b>Device Name</b></td>
                <td><?php echo $zk->getDeviceName(); ?></td>
                <td><b>Get Time</b></td>
                <td><?php echo $zk->getTime()->format('r') ?></td>
            </tr>
        </table>
        <hr />
        <table border="1" cellpadding="5" cellspacing="2" style="float: left; margin-right: 10px;">
            <tr>
                <th colspan="5">Data User</th>
            </tr>
            <tr>
                <th>UID</th>
                <th>ID</th>
                <th>Name</th>
                <th>Role</th>
                <th>Password</th>
		<th>Card number</th>
            </tr>
            <?php
            try {
                //$zk->clearUsers();
		//$zk->setUser(\ZKLib\User::construct(1, \ZKLib\User::PRIVILEGE_SUPERADMIN, '1', 'Admin', '', ''));
		foreach($zk->getUser() as $user):
		    $role = 'Unknown';
		    switch ($user->getRole()){
			case \ZKLib\User::PRIVILEGE_COMMON_USER : $role = 'USER'; break;
			case \ZKLib\User::PRIVILEGE_ENROLLER    : $role = 'ENROLLER'; break;
			case \ZKLib\User::PRIVILEGE_MANAGER     : $role = 'MANAGER'; break;
			case \ZKLib\User::PRIVILEGE_SUPERADMIN  : $role = 'ADMIN'; break;
		    }
                ?>
                <tr>
                    <td><?php echo $user->getRecordId(); ?></td>
                    <td><?php echo $user->getUserId(); ?></td>
                    <td><?php echo $user->getName(); ?></td>
                    <td><?php echo $role; ?></td>
                    <td><?php echo $user->getPassword(); ?></td>
                    <td><?php echo $user->getCardNo(); ?></td>
                </tr>
                <?php
                endforeach;
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                header('HTTP', true, 500); // 500 internal server error                
            }
            //$zk->clearAdmins();
            ?>
        </table>
        
        <table border="1" cellpadding="5" cellspacing="2">
            <tr>
                <th colspan="6">Data Attendance</th>
            </tr>
            <tr>
                <th>Index</th>
                <th>UID</th>
                <th>Type</th>
                <th>DateTime</th>
            </tr>
            <?php
		foreach($zk->getAttendance() as $attendance):
 	    ?>
            <tr>
                <td><?php echo $attendance->getRecordId(); ?></td>
                <td><?php echo $attendance->getUserId(); ?></td>
                <td><?php echo $attendance->getType(); ?></td>
                <td><?php echo $attendance->getDateTime()->format('r'); ?></td>
            </tr>
            <?php
                endforeach;
            ?>
        </table>
        
    <?php
        $zk->enable();
        $zk->disconnect();
    endif
?>
    </body>
</html>
