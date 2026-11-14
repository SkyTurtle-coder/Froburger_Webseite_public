<?php
require __DIR__ . '/start-timing-check.php';
foreach (array('off'=>'Off','inoff'=>'Inoff','iaoff'=>'Iaoff','hochoff'=>'Hochoff') as $status=>$label) {
    foreach (array(false,true) as $internal) {
        $event = $normalize->invoke(new AVF_Events_API_Client(), $source + array('status'=>$status,'status_label'=>$label,'is_internal'=>$internal));
        $view = AVF_Events_View_Helpers::build_view($event);
        $expected = array(array('status'=>$status,'label'=>$label));
        if ($internal) { $expected[] = array('status'=>'intern','label'=>'Intern'); }
        same($expected, $view['status_badges']);
    }
}
same(array(array('status'=>'intern','label'=>'Intern')), AVF_Events_View_Helpers::status_badges(array('status'=>'INTERN','is_internal'=>true)));
same(array(array('status'=>'iaoff','label'=>'Iaoff'),array('status'=>'intern','label'=>'Intern')), AVF_Events_View_Helpers::status_badges(array('status'=>'iaoff','status_label'=>'Iaoff + Intern')));
same(array(array('status'=>'off','label'=>'Off')), AVF_Events_View_Helpers::status_badges(array('status'=>'off','is_internal'=>'false')));
same(array(), AVF_Events_View_Helpers::status_badges(array()));
echo "Status badges: all combinations, internal-only, cached payload and false value passed.\n";
