<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\{Alert, P_Report, P_Aidailystats, AlertSetting};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PDO;
use Illuminate\Support\Facades\DB;

class AlertController extends Controller
{
   public function alerts()

   {
     
      $alerts = Alert::with('ipDetail', 'auidDetail', 'aiAnalyDetail')->where(['mid'=> Auth::user()->id ,'status' => 1])
         ->orderBy('alertid', 'desc')
         ->limit(100)
         ->get();
      $alertsDet = $alerts->map(function ($item) {
         return [
            'alertid'    => $item->alertid,
            'orderid'    => $item->orderid,
            'status'     => $item->status,
            'alerttype'  => $item->alerttype == 2 ? "Excess Use Alert" : "Fraud Reported",
            'mid'        => $item->mid,
            'createdate' => $item->createdate, 
            'analyticsid' =>isset($item->aiAnalyDetail[0]->id) ? $item->aiAnalyDetail[0]->id : null 
         ];
      });

      return response()->json($alertsDet);
   }
   // show single alert
   public function showalert(Request $request)
   {
     
      $alerts = Alert::with('ipDetail', 'auidDetail', 'aiAnalyDetail')
         ->where('alertid', $request->id)
         ->orderBy('alertid', 'desc')
         ->get();
      // use this function for getting location from ip address
      function getLocation($ip)
      {
         $transaction_ip = $ip;
         $details = json_decode(file_get_contents("http://ipinfo.io/{$transaction_ip}/json"));
         return  $details->city . ' ' . $details->country;
      }
      // use this function for getting related ownerIds  
      function getRelatedOwnerIds($ownerid, $relatedOwnerIds)
      {

         $auidIds = DB::table('partners_owner_auid_ip')
            ->whereIn('ownerid', [$relatedOwnerIds])
            ->selectRaw('GROUP_CONCAT(distinct auidid) as auidids')
            ->value('auidids');

         $relatedOwnerIds = DB::table('partners_owner_auid_ip')
            ->where('ownerid', '<>', $ownerid)
            ->whereIn('auidid',  [$auidIds])
            ->whereNotIn('ownerid', [$relatedOwnerIds])
            ->selectRaw('GROUP_CONCAT(distinct ownerid) as relatedownerids')
            ->value('relatedownerids');

         return $relatedOwnerIds;
      }
      $i = 0;
      // send main array to json response
      $alertsDet = $alerts->map(function ($item) use ($i) {
         return [
            'alertid'   => $item->alertid,
            'orderid'   => $item->orderid,
            'alerttype' => $item->alerttype,
            'status'    => $item->status,
            'userid'      => $item->mid,
            'deviceid'    => isset($item->auidDetail[$i]->id) ? $item->auidDetail[$i]->id : null,
            'ownerid'     => $item->ownerid,
            'ipaddress'   => isset($item->ipDetail[$i]->ipaddress) ? $item->ipDetail[$i]->ipaddress : null,
            'productid'   => isset($item->aiAnalyDetail[$i]->productids) ? $item->aiAnalyDetail[$i]->productids : null,
            'amount'      => isset($item->aiAnalyDetail[$i]->amount) ? $item->aiAnalyDetail[$i]->amount : null,
            'postage'     => isset($item->aiAnalyDetail[$i]->postage) ? $item->aiAnalyDetail[$i]->postage : null,
            'tax'         => isset($item->aiAnalyDetail[$i]->tax) ? $item->aiAnalyDetail[$i]->tax : null,
            'browser'     => isset($item->aiAnalyDetail[$i]->browser) ? $item->aiAnalyDetail[$i]->browser : null,
            'os'          => isset($item->aiAnalyDetail[$i]->os) ?  $item->aiAnalyDetail[$i]->os : null,
            'referer'     => isset($item->aiAnalyDetail[$i]->referer) ? $item->aiAnalyDetail[$i]->referer : null,
            'keyword'     => isset($item->aiAnalyDetail[$i]->keyword) ? $item->aiAnalyDetail[$i]->keyword : null,
            'currency'    => isset($item->aiAnalyDetail[$i]->currency) ? $item->aiAnalyDetail[$i]->currency : null,
            'date'        => isset($item->createdate) ? $item->createdate : null,
            'location'    =>  getLocation($item->ipDetail[$i]->ipaddress),
            'trafficsource' => isset($item->aiAnalyDetail[$i]->trafficsource) ? $item->aiAnalyDetail[$i]->trafficsource : null
         ];
         $i++;
      });
      $ownerid = isset($alertsDet[0]['ownerid']) ? $alertsDet[0]['ownerid'] : null;
      $relatedOwnerIds = DB::table('partners_owner_auid_ip as ta')
         ->join('partners_owner_auid_ip as tb', 'ta.auidid', '=', 'tb.auidid')
         ->where('ta.ownerid',  '=', $ownerid)
         ->where('tb.ownerid',  '<>', $ownerid)
         ->selectRaw('GROUP_CONCAT(distinct tb.ownerid) as relatedownerids')
         ->value('relatedownerids');

      $relOwnerIds = getRelatedOwnerIds($alertsDet[0]['ownerid'], $relatedOwnerIds);


      if ($relOwnerIds != "") {

         $relatedOwnerIdsArr = explode(",", $relOwnerIds);
      } else {

         $relatedOwnerIdsArr = "";
      }

      $relatedTransactions = DB::table('partners_aianalytics as ai')
         ->select('ai.*', 'ip.id as deviceid')
         ->leftJoin('partners_ipstats as ip', 'ai.ipaddress', '=', 'ip.ipaddress')
         ->whereIn('ai.ipaddress', function ($query) use ($ownerid, $relatedOwnerIdsArr) {
            $query->select('ips.ipaddress')
               ->from('partners_ipstats as ips')
               ->where('ips.ownerid', $ownerid)
               ->orWhereIn('ips.ownerid', [$relatedOwnerIdsArr]);
         });

      $totalUserDevices = 1;

      if ($relOwnerIds != "") {

         $relatedOwnerIdsArr = explode(",", $relOwnerIds);
         $totalUserDevices += count($relatedOwnerIdsArr);
      }

      $relatedTransactions->limit(5);

      $reltransTotal = DB::table('partners_aianalytics')
         ->whereIn('ipaddress', function ($query) use ($ownerid, $relatedOwnerIdsArr) {
            $query->select('ipaddress')
               ->from('partners_ipstats')
               ->where('ownerid', $ownerid);
            if (!empty($relatedOwnerIdsArr)) {
               $query->orWhereIn('ownerid', [$relatedOwnerIdsArr]);
            }
         })->count();
      // added sub_array to main array
      $alertsDet['relatedtransations'] = $relatedTransactions->get();
      $alertsDet['totalreltrans']      = $reltransTotal;
      $alertsDet['totaluserdevice']    = $totalUserDevices;
      // convert main array into json response and return it.That is final output.
      return response()->json($alertsDet);
   }
   //  Report Fruad Function 
   public function reportFruad(Request $request)
   {
      // return $request->mid;
      $mid          = $request->mid;
      $alertid      = $request->alertid;
      $alertstatus  = $request->alertstatus;
      $updateStatus = Alert::where('alertid', $alertid)->update(['status' => $alertstatus]);
      if ($updateStatus) {
         $status = "status changed";
      } else {
         $status = "status already same";
      }
      if ($alertstatus == 3) {

         $alerts       = Alert::with('reletedAi:id,amount,createdate,ipaddress,browser,os,currency')
                        ->where('alertid', $alertid)->get();
                     
         $fraudDet  = $alerts->map(function ($item) {
            return  [
               'alertid'   => $item->alertid,
               'mid'       => $item->mid,
               'alerttype' => $item->alerttype,
               'ipid'      => $item->ipid,
               'auidid'    => $item->auidid,
               'orderid'   => $item->orderid,
               'status'    => $item->status,
               'id'        => isset($item->reletedAi[0]->id) ? $item->reletedAi[0]->id : null,
               'amount'    => isset($item->reletedAi[0]->amount) ? $item->reletedAi[0]->amount : null,
               'date'      => isset($item->reletedAi[0]->createdate) ? $item->reletedAi[0]->createdate : null,
               'ipaddress' => isset($item->reletedAi[0]->ipaddress) ? $item->reletedAi[0]->ipaddress : null,
               'os'        => isset($item->reletedAi[0]->os) ? $item->reletedAi[0]->os : null,
               'browser'   => isset($item->reletedAi[0]->browser) ? $item->reletedAi[0]->browser : null,
               'currency'  => isset($item->reletedAi[0]->currency) ? $item->reletedAi[0]->currency : null
            ];
         });
         $orderid      = $alerts[0]->orderid;
         $cendresponse = DB::table('partners_cannedresponse')->get();
         $report       = P_Report::where(['mid' => $mid, 'orderid' => $orderid])->get();
         $sendResponse = ['cenndresponse' => $cendresponse, 'report' => $report, 'fraudDet' => $fraudDet, 'status' => $status];
      } else if ($alertstatus == 2) {

         $sendResponse = ['status' => $status ,'message' =>'alert dismissed'];
      }

      return $sendResponse;
   }
   //  Save Fraud Response
   public function saveFraudRes(Request $request)
   { 
     
      $mid        = $request->mid;
      $alertid    = $request->alertid;
      $auidid     = $request->auidid;
      $orderid    = $request->orderid;
      $responseid = $request->responseid;
      $notes      = $request->notes;
      $report     = P_Report::where(['mid' => $mid, 'orderid' => $orderid])->exists();
      
      if ($report) {
         // update partners_reports if mid already exist
         $result    =  P_Report::where(['mid' => $mid, 'orderid' => $orderid])
                       ->update(['orderid' => $orderid, 'cannedresponseid' => $responseid, 'notes' => $notes]); 
         $response  = ['message' => "Fraud Reported Updated Successfully"];   
         return response()->json($response);      
      } else {
         // insert partners_reports if mid is not exist
         $result        =   P_Report::create(['mid' => $mid, 'alertid' => $alertid, 'orderid' => $orderid, 'auidid' => $auidid, 'cannedresponseid' => $responseid, 'notes' => $notes]);
         $presentDate   =   date("Y-m-d");
         $dailystats    =   P_Aidailystats::where(['mid' => $mid, 'date' => $presentDate])->exists();
         if ($dailystats) {
            $dailyStatRec = P_Aidailystats::where(['mid' => $mid,'date' => $presentDate])->get();
         foreach ($dailyStatRec as $row) {
         $dailyreports  =  $row["reports"] + 1;
         $dailystatsUpdate = DB::table('partners_aidailystats')->where('mid', $mid)->update(['reports' => $dailyreports , 'date' => $presentDate]);
         $response  = ['message' => "Fraud Reported Inserted Successfully"];   
         return response()->json($response);    
   }
         } else {
            $dailyreports = 1;
            $dailystatsInsert  =  P_Aidailystats::create(['mid' => $mid, 'reports' => $dailyreports, 'date' => $presentDate]);  
            $response  = ['message' => "Fraud Reported Inserted Successfully"];   
            return response()->json($response);  
         }
      }
    
   }
   public function alertSetting(Request $request)
   {
      $alertSetting  = AlertSetting::where('mid', $request->mid)->get();
      return response()->json($alertSetting);
   }
   public function saveAlertSetting(Request $request)
   {   
     
      $today         =  date("Y-m-d H:i:s");
      $orderamount   =  $request->orderamount;
      $aov_threshold =  $request->averagevalue;
      $interactcount =  $request->interactcount;
      $searchprevtransterm = $request->searchprevioustransaction;
      $alertSetting  = AlertSetting::where(['mid' => $request->mid]);
      if ($alertSetting->exists()) {
        $updateAlertSetting = AlertSetting::where(['mid' => $request->mid])->update(['interactcount' => $interactcount, 'searchprevtransterm' => $searchprevtransterm, 'aov_threshold' => $aov_threshold ,'order_amount' => $orderamount]);
      if($updateAlertSetting){
         $response = ['message' => 'Alert Setting Updated'];
      } else {
         $response = ['message' => 'Alert Setting Not Updated'];
      }
      } else {
        
        $insertAlertSetting = AlertSetting::create(['mid' => $request->mid, 'interactcount' => $interactcount, 'searchprevtransterm' => $searchprevtransterm, 'aov_threshold' => $aov_threshold , 'order_amount' => $orderamount]);
        if($insertAlertSetting){
         $response = ['message' => 'Alert Setting Inserted'];
        }else{
         $response = ['message' => 'Alert Setting Not Inserted'];
        }
      }
      return response()->json($response);
   }
}

