<?php  //$Id: filtersettings.php,v 1.1.2.2 2007/12/19 17:38:45 vf Exp $

// this will give same access to original multilang setting as we share it
$settings->add(new admin_setting_configcheckbox('filter_multilang_force_old', 'filter_multilang_force_old',
                   get_string('multilangforceold', 'admin'), 0));

?>
