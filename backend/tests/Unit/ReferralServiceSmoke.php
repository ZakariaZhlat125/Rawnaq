<?php
// Isolated logic tests: database/model doubles; not a concurrency or Laravel integration test.
namespace Illuminate\Validation { class ValidationException extends \RuntimeException { public static function withMessages($messages) { return new self(json_encode($messages)); } } }
namespace Illuminate\Support\Facades { class DB { public static function transaction($callback) { $snapshot=serialize([\App\Models\User::$rows,\App\Models\LoyaltyPoint::$rows,\App\Models\LoyaltyPointTransaction::$rows]);try { return $callback(); } catch (\Throwable $e) { [\App\Models\User::$rows,\App\Models\LoyaltyPoint::$rows,\App\Models\LoyaltyPointTransaction::$rows]=unserialize($snapshot);throw $e; } } } }
namespace App\Models {
class Query {
    private $class; private $filters=[];
    public function __construct($class) { $this->class=$class; }
    public function where($key,$value) { $this->filters[$key]=$value;return $this; }
    public function lockForUpdate() { return $this; }
    public function first() { foreach ($this->class::$rows as $row) { $ok=true;foreach($this->filters as $key=>$value)if(($row->$key??null)!=$value)$ok=false;if($ok)return $row; }return null; }
    public function firstOrFail() { return $this->first()??throw new \RuntimeException('Not found'); }
}
class User { public static $rows=[];public $id,$referral_code,$status=1,$is_banned=0,$referral_rewarded_at=null,$referred_by_user_id=null;
    public function __construct($id,$code) {$this->id=$id;$this->referral_code=$code;self::$rows[$id]=$this;}
    public static function whereKey($id) { return (new Query(self::class))->where('id',$id); }
    public static function active() { return (new Query(self::class))->where('status',1)->where('is_banned',0); }
    public function forceFill($values) {foreach($values as $key=>$value)$this->$key=$value;return $this;}
    public function save() { self::$rows[$this->id]=$this; }
}
class Setting {public static $points=25,$memberPoints=null;public static function get($key,$default=null){return $key === 'referral_new_user_points' ? (self::$memberPoints ?? $default) : self::$points;}}
class LoyaltyPoint { public static $rows=[];public $user_id,$points;
    public static function where($key,$value){return (new Query(self::class))->where($key,$value);}
    public static function create($values){$row=new self;foreach($values as $key=>$value)$row->$key=$value;self::$rows[$row->user_id]=$row;return $row;}
    public function increment($key,$n){$this->$key+=$n;}
    public function refresh(){}
}
class LoyaltyPointTransaction {public static $rows=[],$fail=false,$failUser=null;public static function create($data){if(self::$fail || self::$failUser === $data['user_id'])throw new \RuntimeException('Ledger failure');self::$rows[]=$data;}}
}
namespace {
function __($key){return $key;}function now(){return '2026-09-28 12:00:00';}
require __DIR__.'/../../app/Services/ReferralService.php';
function check($ok,$label){if(!$ok)throw new \RuntimeException($label);echo "PASS: $label\n";}
function invalid($callback){try{$callback();}catch(\Illuminate\Validation\ValidationException $e){return true;}return false;}
$s=new \App\Services\ReferralService;$owner=new \App\Models\User(1,'SCOWNER');$member=new \App\Models\User(2,'SCMEMBER');
$s->award($member,' scowner ');check(\App\Models\LoyaltyPoint::$rows[1]->points===25,'Inviter receives configured reward');check(\App\Models\LoyaltyPoint::$rows[2]->points===25,'New member defaults to same reward until separately configured');check(count(\App\Models\LoyaltyPointTransaction::$rows)===2,'Separate ledger entries for both parties');
$s->award($member,'SCOWNER');check(\App\Models\LoyaltyPoint::$rows[1]->points===25,'Repeated award is idempotent');
check(invalid(fn()=>$s->owner('MISSING')),'Unknown code rejected');check(invalid(fn()=>$s->award($owner,'SCOWNER')),'Self referral rejected');
$other=new \App\Models\User(3,'SCOTHER');$s->award($other,'');check($other->referral_rewarded_at===null,'Optional blank code awards nothing');
\App\Models\Setting::$points=0;$s->award($other,'SCOWNER');\App\Models\Setting::$points=50;$s->award($other,'SCOWNER');check(\App\Models\LoyaltyPoint::$rows[1]->points===25,'Zero reward cannot be replayed after settings change');
$next=new \App\Models\User(4,'SCNEXT');$s->award($next,'SCOWNER');check(\App\Models\LoyaltyPoint::$rows[1]->points===75,'New registration uses updated setting');
$failed=new \App\Models\User(5,'SCFAILED');\App\Models\LoyaltyPointTransaction::$fail=true;try{$s->award($failed,'SCOWNER');}catch(\RuntimeException $e){}check(\App\Models\LoyaltyPoint::$rows[1]->points===75 && \App\Models\User::$rows[5]->referral_rewarded_at===null,'Ledger failure rolls back balance and referral');

\App\Models\LoyaltyPointTransaction::$fail=false;
check(\App\Models\User::$rows[2]->registration_referral_code==='SCOWNER' && \App\Models\User::$rows[2]->referral_owner_points===25 && \App\Models\User::$rows[2]->referral_member_points===25,'Historical code and both point amounts are stored');
check(\App\Models\LoyaltyPoint::$rows[2]->points===25,'Repeated calls do not reward member twice');
\App\Models\Setting::$points=40;\App\Models\Setting::$memberPoints=10;
$custom=new \App\Models\User(6,'SCCUSTOM');$s->award($custom,'SCOWNER');
check(\App\Models\LoyaltyPoint::$rows[1]->points===115 && \App\Models\LoyaltyPoint::$rows[6]->points===10,'Independent dashboard settings for inviter and member');
\App\Models\Setting::$points=0;\App\Models\Setting::$memberPoints=12;
$memberOnly=new \App\Models\User(7,'SCMEMBERONLY');$s->award($memberOnly,'SCOWNER');
check(\App\Models\LoyaltyPoint::$rows[1]->points===115 && \App\Models\LoyaltyPoint::$rows[7]->points===12,'Disabling inviter reward does not disable member reward');
\App\Models\Setting::$points=25;\App\Models\Setting::$memberPoints=0;
$ownerOnly=new \App\Models\User(8,'SCOWNERONLY');$s->award($ownerOnly,'SCOWNER');
check(\App\Models\LoyaltyPoint::$rows[1]->points===140 && !isset(\App\Models\LoyaltyPoint::$rows[8]),'Disabling member reward does not disable inviter reward');
check(\App\Models\User::$rows[8]->referral_member_points===0,'Zero member reward is explicitly preserved for reporting');
\App\Models\Setting::$memberPoints=10;
$partial=new \App\Models\User(9,'SCPARTIAL');\App\Models\LoyaltyPointTransaction::$failUser=9;
try{$s->award($partial,'SCOWNER');}catch(\RuntimeException $e){}
check(\App\Models\LoyaltyPoint::$rows[1]->points===140 && !isset(\App\Models\LoyaltyPoint::$rows[9]) && \App\Models\User::$rows[9]->referral_rewarded_at===null,'Failure on second reward rolls back both balances and referral');
\App\Models\LoyaltyPointTransaction::$failUser=null;
\App\Models\User::$rows[1]->is_banned=1;check(invalid(fn()=>$s->owner('SCOWNER')),'Banned inviter rejected');
}
