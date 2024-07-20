<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
date_default_timezone_set('Asia/Kolkata');

include_once('../includes/crud.php');

$db = new Database();
$db->connect();
include_once('../includes/custom-functions.php');
include_once('../includes/functions.php');
include_once('verify-token.php');
$fn = new functions;


$date = date('Y-m-d');

// if (!isset($_POST['accesskey'])) {
//     $response['error'] = true;
//     $response['message'] = "Access key is invalid or not passed!";
//     print_r(json_encode($response));
//     return false;
// }
// $accesskey = $db->escapeString($_POST['accesskey']);
// if ($access_key != $accesskey) {
//     $response['error'] = true;
//     $response['message'] = "invalid accesskey!";
//     print_r(json_encode($response));
//     return false;
// }

// if (!verify_token()) {
//     return false;
// }

if (empty($_POST['user_id'])) {
    $response['success'] = false;
    $response['message'] = "User Id is Empty";
    print_r(json_encode($response));
    return false;
}

if (empty($_POST['plan_id'])) {
    $response['success'] = false;
    $response['message'] = "Plan Id is Empty";
    print_r(json_encode($response));
    return false;
}

$user_id = $db->escapeString($_POST['user_id']);
$plan_id = $db->escapeString($_POST['plan_id']);

$sql = "SELECT * FROM settings";
$db->sql($sql);
$settings = $db->getResult();
$scratch_card = $settings[0]['scratch_card'];


$sql = "SELECT * FROM users WHERE id = $user_id ";
$db->sql($sql);
$user = $db->getResult();

if (empty($user)) {
    $response['success'] = false;
    $response['message'] = "User not found";
    print_r(json_encode($response));
    return false;
}


$sql = "SELECT * FROM plan WHERE id = $plan_id ";
$db->sql($sql);
$plan = $db->getResult();

if (empty($plan)) {
    $response['success'] = false;
    $response['message'] = "Plan not found";
    print_r(json_encode($response));
    return false;
}

$products = $plan[0]['products'];

$invite_bonus = $plan[0]['invite_bonus'];
$price = $plan[0]['price'];
$daily_income = $plan[0]['daily_income'];
$stock = $plan[0]['stock'];
//$total_income = $plan[0]['total_income'];
//$validity = $plan[0]['validity'];
$num_times = $plan[0]['num_times'];
$balance = $user[0]['balance'];
$recharge = $user[0]['recharge'];
$valid = $user[0]['valid'];
$valid_team = $user[0]['valid_team'];
$total_assets = $user[0]['total_assets'];
$refer_code = $user[0]['refer_code'];
$referred_by = $user[0]['referred_by'];
$min_withdrawal = $user[0]['min_withdrawal'];

$datetime = date('Y-m-d H:i:s');

if ($stock == 0) {
    $response['success'] = false;
    $response['message'] = "Out of Stock";
    print_r(json_encode($response));
    return false;
}

$sql = "SELECT COUNT(*) AS count FROM user_plan WHERE plan_id = $plan_id AND user_id = $user_id";
$db->sql($sql);
$res_check_plan = $db->getResult();
$user_num_times = $res_check_plan[0]['count'];

if ($user_num_times >= $num_times) {
    $response['success'] = false;
    $response['message'] = "Plan already activated";
    print_r(json_encode($response));
    return false;
}


$t_plan_id = $plan_id - 1;

$sql_check = "SELECT * FROM plan WHERE id = $t_plan_id";
$db->sql($sql_check);
$res1 = $db->getResult();

if (!empty($res1)) {
    $t_products = $res1[0]['products'];
}

$sql_check = "SELECT * FROM user_plan up LEFT JOIN users u ON up.user_id = u.id WHERE u.referred_by = '$referred_by' AND up.plan_id = $t_plan_id";
$db->sql($sql_check);
$res_check_user = $db->getResult();
$num = $db->numRows($res_check_user);

// if ($num < 5 && $plan_id > 2) {
//     $response['success'] = false;
//     $response['message'] = "To unlock ".$products." production invite 5 members in ".$t_products." production";
//     print_r(json_encode($response));
//     return false;
// }

//if ($plan_id == 3 && $valid_team < 2) {
 //   $response['success'] = false;
 //   $response['message'] = "To unlock Tomato production invite 2 members in Chilli production";
 //   print_r(json_encode($response));
 //   return false;
//}

if ($recharge >= $price) {
    if($valid == 0 && $price > 0){
        $sql = "UPDATE users SET valid_team = valid_team + 1  WHERE refer_code = '$referred_by'";
        $db->sql($sql);
        $sql = "UPDATE users SET valid = 1  WHERE id = $user_id";
        $db->sql($sql);
    }

    $sql = "UPDATE users SET recharge = recharge - $price, total_assets = total_assets + $price WHERE id = $user_id";
    $db->sql($sql);



    if($refer_code){
        $sql = "SELECT * FROM users WHERE refer_code = '$referred_by'";
        $db->sql($sql);
        $res = $db->getResult();
        $num = $db->numRows($res);

        if ($num == 1) {
            $r_id = $res[0]['id'];
            $r_refer_code = $res[0]['refer_code'];
            if($plan_id == 1){
                $sql = "SELECT id FROM users WHERE referred_by = '$r_refer_code' AND valid = 0";
                $db->sql($sql);
                $res = $db->getResult();
                $num = $db->numRows($res);
                if($num > 5){
                    $invite_bonus = 0;

                }
            }
            $sql = "UPDATE users SET balance = balance + $invite_bonus,today_income = today_income + $invite_bonus,total_income = total_income + $invite_bonus,team_income = team_income + $invite_bonus  WHERE refer_code = '$referred_by'";
            $db->sql($sql);

            $sql = "INSERT INTO transactions (user_id, amount, datetime, type) VALUES ('$r_id', '$invite_bonus', '$datetime', 'invite_bonus')";
            $db->sql($sql);
            
        }

    }
    if ($plan_id == 8) {

        $min_withdrawal = 150;

        $sql = "UPDATE users SET min_withdrawal = $min_withdrawal WHERE id = '$user_id'";
        $db->sql($sql);
    }
    if ($plan_id == 7) {

        $min_withdrawal = 50;

        $sql = "UPDATE users SET min_withdrawal =  $min_withdrawal WHERE id = '$user_id'";
        $db->sql($sql);
    }

    if ($scratch_card == 1 && $plan_id != 1 ) {

        $price = $plan[0]['price'];
        $amount = rand(1,3) / 100 * $price ;

        // $sql = "UPDATE users SET chances = chances + 1 WHERE id = $user_id";
        // $db->sql($sql);

        // $sql_insert_user_plan = "INSERT INTO scratch_cards (user_id,amount,status) VALUES ('$user_id','$amount',0)";
        // $db->sql($sql_insert_user_plan);

        // $sql = "UPDATE users SET chances = chances + 1 WHERE refer_code = '$referred_by'";
        // $db->sql($sql);
      //  $sql_insert_user_plan = "INSERT INTO scratch_cards (user_id,amount,status) VALUES ('$r_id','$amount',0)";
      //  $db->sql($sql_insert_user_plan);
    }

    $sql_insert_user_plan = "INSERT INTO user_plan (user_id,plan_id,joined_date) VALUES ('$user_id','$plan_id','$date')";
    $db->sql($sql_insert_user_plan);

    $sql_insert_transaction = "INSERT INTO transactions (user_id, amount, datetime, type) VALUES ('$user_id', '$price', '$datetime', 'start_production')";
    $db->sql($sql_insert_transaction);

    $response['success'] = true;
    $response['message'] = "Production started successfully";
 }else {
    $response['success'] = false;
    $response['message'] = "Insufficient balance to start this production";
}

print_r(json_encode($response));
?>