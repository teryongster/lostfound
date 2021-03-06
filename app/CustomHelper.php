<?php
use App\Mail\JoinerMail;
use App\Conversation;
use App\Participation;
use App\Message;
use App\User;
use App\LostItem;
use App\FoundItem;
use App\Rating;
use App\Log;
use Carbon\Carbon;
use App\Notification;
use App\MessageQuery;

function countMessageQueries(){
    $mq = new MessageQuery;
    $mqCount = $mq->where('read', 0)->count();
    return $mqCount;
}

function checkPostsThenDelete(){
    $losts = LostItem::where('created_at', '<=', Carbon::now()->subDays(90)->toDateTimeString())->get();
    $founds = FoundItem::where('created_at', '<=', Carbon::now()->subDays(90)->toDateTimeString())->get();
    if($losts){
        foreach($losts as $item){
            $item->delete();
            storeLog('A lost item has been automatically deleted because it is older than 90 days');
        }
    }
    if($founds){
        foreach($founds as $item){
            $item->delete();
            storeLog('A found item has been automatically deleted because it is older than 90 days');
        }
    }
}

function storeNotification($users, $body, $url){
    // if(count($users) > 1){
    //     foreach($users as $user){
    //         $n = new Notification;
    //         $n->user_id = $user;
    //         $n->body = $body;
    //         $n->save();
    //     }
    // }else{
        $n = new Notification;
        $n->user_id = $users;
        $n->body = $body;
        $n->url = $url;
        $n->save();
    // }
}

function storeLog($log){
    $l = new Log;
    $l->body = $log;
    $l->save();
}

function countRegistrationRequests(){
    $u = new User;
    $count = $u->where('approved', '0')->count();
    if($count > 0){
        return $count;
    }
}

function sendSMS($type){
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://rest.nexmo.com/sms/json");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "api_key=b444db65&api_secret=uThRO8vfP5Cc9iA2&to=639488578196&from=\"missingZ\"&text=\"A user has posted a new ".$type." item");
    curl_setopt($ch, CURLOPT_POST, 1);

    $headers = array();
    $headers[] = "Content-Type: application/x-www-form-urlencoded";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close ($ch);
}

function computeRatings(){
    $r = new Rating;
    $count = $r->count();
    $ratings  = $r->all();
    if($count > 0){
        $sum = 0;
        foreach($ratings as $rating){
            $sum += $rating->rating;
        }
        $average = $sum / $count;
        return $average;
    }else{
        return 0;
    }
}

function countRaters(){
    $r = new Rating;
    $count = $r->count();
    return $count;
}

function countRetrievedItems(){
    $li = new LostItem;
    $fi = new FoundItem;
    $count_li = $li->where('status', 1)->count();
    $count_fi = $fi->where('status', 1)->count();
    $total = $count_fi + $count_li;
    return $total;
}

function checkIfRated(){
    $user = User::find(session('id'));
    if($user && $user->ratings()->count() > 0){
        return 1;
    }
}

function countUnreadMessages(){
    $messagesCount = 0;
    $count = 0;
    $p = new Participation;
    $myParticipations = $p->where('user_id', session('id'))->get();
    if(count($myParticipations) > 0){
        foreach($myParticipations as $myParticipation){
            $messagesCount = $messagesCount + $myParticipation->conversation->messages->where('user_id', '!=', session('id'))->where('seenby', '!=', session('id'))->count();
        }
        if($messagesCount == 0){
            return '';
        }else{
            return $messagesCount;
        }
    }else{
        return '';
    }
}

function countNotifications(){
    $n = new Notification; 
    $notificationsCount = $n->where('user_id', session('id'))->where('read', 0)->count();
    if($notificationsCount > 0){
        return '('.$notificationsCount.')';
    }else{
        return '';
    }
}

function countUnreadMessagesOnThisUser($user_id){
    $messagesCount = 0;
    $count = 0;
    $p = new Participation;
    $myParticipations = $p->where('user_id', session('id'))->get();
    if(count($myParticipations) > 0){
        foreach($myParticipations as $myParticipation){
            $messagesCount = $messagesCount + $myParticipation->conversation->messages->where('user_id', $user_id)->where('seenby', '!=', session('id'))->count();
        }
        if($messagesCount == 0){
            return '';
        }else{
            return $messagesCount;
        }
    }else{
        return '';
    }
}

function setActive($path){
    if(Request::is($path . '*')){
    	return 'activenavlink';
    }
}

function navSetActive($path){
    if(Request::is($path . '*')){
    	return 'link_active';
    }
}

function adminSetActive2($path){
    if(Request::is($path . '*')){
    	return 'mydropdown_active';
    }
}

function checkCurrentDirectory($path){
    if(Request::is($path)){
    	return 'true';
    }
}

function adminSetActive3($path){
    if(Request::is($path . '*')){
        return 'active';
    }
}