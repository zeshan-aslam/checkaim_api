<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{P_Aianalytics, P_Aidailystats};
use DB;

class DashboardController extends Controller
{
    public function dailystates(Request $request)
    {
        $today     = date('Y-m-d');
        $lastorder    = P_Aianalytics::where(['mid' => $request->mid])->orderBy('createdate', 'desc')
                        ->limit(1)->first(['createdate']);
        $diff         = strtotime(date("Y-m-d")) - strtotime($lastorder["createdate"]);
        $lastOrderDay = abs(round($diff / 86400));
        $todayRecord  = P_Aidailystats::where(['mid' => $request->mid , 'date' => $today])
                        ->get(['sales', 'alerts', 'transactions', 'reports']);
        $todaysales =  0;
        $todayalerts = 0;
        $todayreports = 0;
        $todaytransactions = 0;
        if(count($todayRecord) > 0){
        $todaysales  = $todayRecord[0]->sales ? $todayRecord[0]->sales : '0';
        $todayalerts = $todayRecord[0]->alerts ? $todayRecord[0]->alerts : '0';
        $todaytransactions  = $todayRecord[0]->transactions ? $todayRecord[0]->transactions : '0';
        $todayreports  = $todayRecord[0]->reports ? $todayRecord[0]->reports : '0';
                       }            
        $yearlyRecord = P_Aidailystats::select(
                        DB::raw('YEAR(date) as year'),
                        DB::raw('MONTHNAME(date) as month'),
                        DB::raw('SUM(transactions) as transactions'),
                        DB::raw('SUM(alerts) as alerts'),
                        DB::raw('SUM(sales) as sales')
                        )
                        ->where('mid', '=', $request->mid)
                        ->whereYear('date', '=', date('Y'))
                        ->groupBy(DB::raw('YEAR(date)'), DB::raw('MONTHNAME(date)'))
                        ->orderBy('date', 'asc')
                        ->get();
                        $getCurrency = DB::table('av_usermeta')->selectRaw('user_meta')
                        ->WhereRaw('user_id = '.$request->mid.' AND user_key = "av_Currency"  ')->get();

                        //$consCurrency = DB::select('SELECT user_meta FROM  av_usermeta WHERE user_id = ? AND user_key', [$request->mid,'av_Currency'])->get();


        return ['lastorderDay'=> $lastOrderDay,'yearlyrecord'     => $yearlyRecord,
                'todaysales'  => $todaysales, 'todaytransactions' => $todaytransactions,
                'todayalerts' => $todayalerts, 'todayreports'     => $todayreports, 
                'currency'    => $getCurrency
        ];
        
    }
    public function weeklyReport(Request $request)
    {
        // $month_start = '2019-08-18';
        // $month_end   = '2019-08-24';
        $month_start = date('Y-m-d',strtotime('-7 days', time()));
        $month_end   = date('Y-m-d');
        /*Monthly Count*/
        $monthlySale  =  P_Aidailystats::where('mid',  $request->mid)
            ->whereBetween('date', [$month_start, $month_end])
            ->orderBy('date', 'asc')
            ->get(['date', 'sales', 'alerts', 'transactions', 'reports']);
        return $monthlySale;
    }
    public function yearlyReport(Request $request)
    {
        $yearlyReport=P_Aidailystats::select(
        DB::raw('YEAR(date) as year, MONTHNAME(date) as month, SUM(transactions) as transactions, SUM(alerts) as alerts, SUM(sales) as sales'))
        ->where('mid', $request->mid)
        ->whereYear('date', '=', date('Y'))
        ->groupBy(DB::raw('YEAR(date), MONTHNAME(date)'))
        ->orderBy('date', 'asc')
        ->get();

         return $yearlyReport;
    }
}
