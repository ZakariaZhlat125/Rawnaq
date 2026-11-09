<?php
require_once __DIR__.'/../../app/Services/ReviewReport.php';
use App\Services\ReviewReport;
$modern=[['booking_id'=>10,'user_id'=>20,'created_at'=>'2026-10-17 10:00:00','branch_rating'=>4,'rating'=>4,'is_approved'=>0,'status'=>1,'review_text'=>'Good',
    'booking'=>['branch_id'=>2,'branch'=>['name'=>'Branch'],'services'=>[['id'=>5,'service_name'=>'Haircut','employee'=>['full_name'=>'Staff']]]],
    'user'=>['full_name'=>'Customer'], 'service_ratings'=>[['booking_service_id'=>5,'service_id'=>7,'service_rating'=>5,'employee_id'=>3,'employee_rating'=>3]]]];
$legacy=[['id'=>1,'booking_id'=>10,'user_id'=>20,'employee_id'=>3,'rating'=>2,'created_at'=>'2026-10-17 10:00:00'],['id'=>2,'booking_id'=>11,'user_id'=>20,'employee_id'=>3,'rating'=>4,'created_at'=>'2026-10-17 10:00:00']];
$checks=0;
function check($ok,$label){global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
$rows=ReviewReport::rows($modern,$legacy);
check(count($rows)===4,'Branch, service, employee and unique legacy vote');
check(count(ReviewReport::filter($rows,['type'=>'branch']))===1,'Branch filter');
check(count(ReviewReport::filter($rows,['type'=>'service']))===1,'Service filter');
check(count(ReviewReport::filter($rows,['type'=>'employee']))===2,'Employee filter without legacy duplication');
check(count(ReviewReport::filter($rows,['approval'=>'pending']))===3,'Pending filter excludes legacy votes without approval');
check(count(ReviewReport::filter($rows,['approval'=>'approved']))===0,'Approval filter');
check(count(ReviewReport::filter($rows,['branch_id'=>2]))===3,'Branch filter');
check(count(ReviewReport::filter($rows,['rating'=>5]))===1,'Stars filter');
check(count(ReviewReport::filter($rows,['visibility'=>'hidden']))===0,'Visibility filter');
check($rows[1]['target']==='Haircut' && $rows[2]['target']==='Staff','Service and employee labels');
check($rows[0]['type']==='branch','General booking vote is not counted again for explicit branch review');
$old=$modern; $old[0]['branch_rating']=null;$old[0]['service_ratings']=[];
check(ReviewReport::rows($old)[0]['type']==='booking','Legacy general review remains a booking review');
$deleted=$modern;$deleted[0]['booking']=null;$deleted[0]['user']=null;
check(count(ReviewReport::rows($deleted))===3,'Deleted booking or customer does not lose stored votes');
echo "Review report: $checks checks passed.\n";
