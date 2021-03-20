<?php

namespace App\Http\Controllers;

// use App\Imports\RapportMed;
use App\RapportMed;
use App\Medecin;
use App\User;
use App\RapportPh;
use App\Specialite;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Charts\UserChart;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        //
    }

    public function home(Request $request)
    {
        $user = Auth::user();
        $user = ($graph1_is_visible = !(isset($request->Delegue_Id) && $user->user_id != $request->Delegue_Id)) ? $user : User::find($request->Delegue_Id);

        $RapportMedMonthNow = RapportMed::wherein('DELEGUE_id', [$user->user_id])->whereYear('date_de_visite', now()->year)->whereMonth('date_de_visite', now()->month);
        $nbr_v_mois = $RapportMedMonthNow->count();
        $nbr_v_week = $RapportMedMonthNow->where(DB::raw('WEEK(date_de_visite)'), now()->weekOfYear)->count();

        // dd(RapportMed::wherein('DELEGUE_id', [$user->user_id])->whereYear('date_de_visite', 2021)->get(), now()->year, now()->month, $nbr_v_mois, $user->prenom, $user->nom);
        // dd(RapportMed::wherein('DELEGUE', [$user->prenom, $user->nom])->whereYear('date_de_visite', 2021)->get(), now()->year, now()->month, $nbr_v_mois, $user->prenom, $user->nom);

        $RapportPHMonthNow = RapportPh::wherein('DELEGUE_id', [$user->user_id])->whereMonth('date_de_visite', now()->month)->whereYear('date_de_visite', now()->year);
        $nbr_ph_mois = $RapportPHMonthNow->count();
        $nbr_ph_week = $RapportPHMonthNow->where(DB::raw('WEEK(date_de_visite)'), now()->weekOfYear)->count();
        
        $medecins = RapportMed::wherein('DELEGUE_id', [$user->user_id])->whereYear('date_de_visite', now()->year)->whereMonth('date_de_visite', now()->month);
        $medecins->select(DB::raw('Nom_Prenom, count(*) as Nombre_Medecin'))
        ->groupBy('Nom_Prenom')
        ->orderBy('Nombre_Medecin', 'desc');

        // $nbr_ph_mois = $user->visitePharmacies()
        //     ->whereBetween('date_visite', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
        //     ->where('etat', '!=', 'plan')
        //     ->count();

        // $nbr_v_week = $user->visiteMedicales()->whereBetween('date_visite', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->where('etat', '!=', 'plan')->count();

        // $nbr_ph_week = $user->visitePharmacies()->whereBetween('date_visite', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->where('etat', '!=', 'plan')->count();

        // $medecins = Medecin::select('medecin_id', 'nom', 'prenom', 'specialite_id')->whereHas('visites', function (Builder $query) use ($user) {
        //     $query->where('user_id', $user->user_id);
        // })->withCount(['visites' => function (Builder $query) use ($user) {
        //     $query->where('user_id', $user->user_id)->where('etat', '!=', 'plan');
        // }])->orderBy('visites_count', 'desc')->take(15)->get();

        // $usersChart = new UserChart;
        // $usersChart->labels(['Jan', 'Feb', 'Mar']);
        // $usersChart->dataset('Users by trimester', 'line', [10, 25, 13]);
        // $name = 'salim';

        // return View::make('home')->with('users', $users);

        //Nombre visites par Jours
        $lists = User::select('user_id',  DB::raw('CONCAT(nom," ", prenom) as Nom_Prenom'), 'nom', 'prenom')
            ->where('manager_id', Auth::user()->user_id)
            ->orderBy('role_id')
            ->pluck('Nom_Prenom', 'user_id');
        
        $Delegue_Id = $request->input('Delegue_Id');
        return view('home', compact([
            'nbr_v_mois',
            'nbr_ph_mois',
            'nbr_v_week',
            'nbr_ph_week',
            'lists',
            'graph1_is_visible',
            'Delegue_Id'
            // 'usersChart'
        ]))->with('medecins', $medecins);
    }

    public function visiteBySpecialite(Request $request)
    {
        // if ($request->ajax()) {

        $user = Auth::user();

        // $request->validate([
        //     'timeRange' => 'nullable|in:month,ninthyday',
        // ]);

        // $beginDate = $request->input('timeRange') === 'ninthyday' ?  Carbon::now()->subDays(90) : Carbon::now()->startOfMonth();
        // $endDate = $request->input('timeRange') === 'ninthyday' ?  Carbon::now() : Carbon::now()->endOfMonth();

        // $spec = DB::table('visite_medicals')
        //     ->select('specialites.code', DB::raw('count(*) as nombreVisites'))
        //     ->join('medecins', 'visite_medicals.medecin_id', '=', 'medecins.medecin_id')
        //     ->join('specialites', 'medecins.specialite_id', '=', 'specialites.specialite_id')
        //     ->whereBetween('visite_medicals.date_visite', [$beginDate, $endDate])
        //     ->where('visite_medicals.user_id', Auth::id())
        //     ->groupBy('specialites.code')
        //     ->get();

        // return response()->json($spec, 200);
        //         return "{
        //      cols: [{id: 'task', label: 'Employee Name', type: 'string'},
        //             {id: 'startDate', label: 'Start Date', type: 'date'}],
        //      rows: [{c:[{v: 'Mike'}, {v: new Date(2008, 1, 28), f:'February 28, 2008'}]},
        //             {c:[{v: 'Bob'}, {v: new Date(2007, 5, 1)}]},
        //             {c:[{v: 'Alice'}, {v: new Date(2006, 7, 16)}]},
        //             {c:[{v: 'Frank'}, {v: new Date(2007, 11, 28)}]},
        //             {c:[{v: 'Floyd'}, {v: new Date(2005, 3, 13)}]},
        //             {c:[{v: 'Fritz'}, {v: new Date(2011, 6, 1)}]}
        //            ]
        //    }";
        $RapportMed = RapportMed::
        select('Specialité', DB::raw('count(*) as total'))->
        wherein('delegue', [$user->prenom, $user->nom])->
        whereYear('date_de_visite', now()->year)->
        whereMonth('date_de_visite', now()->month)->
        groupBy('Specialité');

        return $RapportMed->get();
        //{"rapport_med_id":2084,"Date_de_visite":"2020-01-06","Nom_Prenom":"EL ANDALOUSSI YASSER",
            //"Specialit\u00e9":"TRAUM","Etablissement":"CHU","Potentiel":"A","Montant_Inv_Pr\u00e9c\u00e9dents":8931,"Zone_Ville":"CASABLANCA",
            //"P1_pr\u00e9sent\u00e9":"NOCI","P1_Feedback":"Prescription effective","P1_Ech":1,"P2_pr\u00e9sent\u00e9":"ACD","P2_Feedback":"Prescription effective",
            //"P2_Ech":1,"P3_pr\u00e9sent\u00e9":"OST","P3_Feedback":"Prescription effective","P3_Ech":0,"P4_pr\u00e9sent\u00e9":"FLEXIMAX",
            //"P4_Feedback":"Prescription effective","P4_Ech":0,"P5_pr\u00e9sent\u00e9":"RAFINAT","P5_Feedback":"Prescription promise",
            //"P5_Ech":0,"Materiel_Promotion":null,"Invitation_promise":null,"Plan\/R\u00e9alis\u00e9":"R\u00e9alis\u00e9","DELEGUE":"FOUAD",
            //"DELEGUE_id":null,"ville_id":1,"created_at":null,"updated_at":null}
        return Specialite::where('gamme_id', 2)->pluck('specialite_id');
        //{"specialite_id":1,"gamme_id":2,"code":"URG","libelle":"m\u00e9decine d'urgence","created_at":null,"updated_at":null}

        // }
        return null;
    }

    public function visiteByVille(Request $request)
    {
        if ($request->ajax()) {

            $request->validate([
                'timeRange' => 'nullable|in:month,ninthyday',
            ]);

            $beginDate = $request->input('timeRange') === 'ninthyday' ?  Carbon::now()->subDays(90) : Carbon::now()->startOfMonth();
            $endDate = $request->input('timeRange') === 'ninthyday' ?  Carbon::now() : Carbon::now()->endOfMonth();

            $spec = DB::table('visite_medicals')
                ->select('villes.libelle', DB::raw('count(*) as nombreVisites'))
                ->join('medecins', 'visite_medicals.medecin_id', '=', 'medecins.medecin_id')
                ->join('villes', 'medecins.ville_id', '=', 'villes.ville_id')
                ->whereBetween('visite_medicals.date_visite', [$beginDate, $endDate])
                ->where('visite_medicals.user_id', Auth::id())
                ->groupBy('villes.libelle')
                ->get();

            return response()->json($spec, 200);
        }
        return null;
    }

    public function visiteByMed(Request $request)
    {
        $user = Auth::user();

        $list[] = ['Jours', 'Nombre Visites', 'dzedz', 'azdaz'];
        $list[] = [1, 50, 75, 44];
        $list[] = [2, 33, 79, 40];
        
        // $list[] = [3, 99, 4];
        // $list[] = [4, 150, 4];
        // $list[] = [3, 44];
        // $list[] = [14, 44];
        return $list;
        // return $list;
        // return response()->json('[["Move","Percentage"],["King\'s pawn (e4)",44],["Queen\'s pawn (d4)",31],["Knight to King 3 (Nf3)",12],["Queen\'s bishop pawn (c4)",10],["Other",3]]', 200);

        // return '[{"delegue":"ABDERRAHMANE","mois":1,"total":8},{"delegue":"ABDERRAHMANE1","mois":2,"total":19},
        // {"delegue":"ABDERRAHMANE2","mois":3,"total":38},{"delegue":"ABDERRAHMANE","mois":4,"total":87},
        // {"delegue":"ABDERRAHMANE","mois":7,"total":50},{"delegue":"ABDERRAHMANE","mois":9,"total":47},
        // {"delegue":"ABDERRAHMANE","mois":15,"total":63},{"delegue":"ABDERRAHMANE","mois":20,"total":90},
        // {"delegue":"ABDERRAHMANE","mois":22,"total":33},{"delegue":"ABDERRAHMANE","mois":25,"total":40},
        // {"delegue":"ABDERRAHMANE","mois":27,"total":53},{"delegue":"ABDERRAHMANE","mois":30,"total":60}
        // ]';

        // $liste = [
        //     ['delegue' => 'Mathilde', 'mois' => 1, 'total' => 8],
        //     ['delegue' => 'Mathilde', 'mois' => 5, 'total' => 40],
        //     ['delegue' => 'Mathilde', 'mois' => 9, 'total' => 110],
        //     ['delegue' => 'Mathilde', 'mois' => 4, 'total' => 80],
        //     ['delegue' => 'Mathilde', 'mois' => 3, 'total' => 95]

        // ];
        // return response()->array($liste, 200);

        // if ($request->ajax()) {
            $ville = $request->input('q');
            $mois = $request->input('m');
            /*
            select DELEGUE, month(Date_de_visite) as mois, COUNT(*) as total
            from rapport_meds
            GROUP BY DELEGUE, mois
            $data = array (
                array("Volvo",2,18),
                array("BMW",5,13),
                array("Saab",5,2),
                array("Land Rover",7,15)
              );
            */
            // $visites =  DB::table('rapport_meds')
            //                 ->select('delegue',
            //                     DB::raw('DAY(Date_de_visite) as mois'),
            //                     DB::raw('count(*) as total')
            //                     )
            //                 ->where('Zone_Ville', $ville)
            //                 ->whereYear('Date_de_visite', now()->year)
            //                 ->groupBy('delegue', 'mois')
            //                 // ->pluck('mois');
            //                 ->get();
            $visites = DB::table('rapport_meds')
            ->select(
                DB::raw('month(Date_de_visite) as mois'),
                DB::raw('count(*) as total')
            )
            ->where('Zone_Ville', $ville)
            ->whereYear('Date_de_visite', 2020)
            ->groupBy('delegue', 'mois')
            ->get();
    
            return response()->json($visites, 200);
        // }

        // $NombreJourReel = 5; // 5 + 2
        // $NombreJoureAvecWeekEnd = $NombreJourReel + now()->diffInDaysFiltered(function(Carbon $date) { return $date->isWeekend(); }, now()->subDays($NombreJourReel));
                    
        return null;
    }

    /*
    public function listeDm(Request $request) {
        //$results = DB::table('rapport_meds');
        $results = User::select('nom','prenom')->join('roles', 'users.user_id', '=', 'roles.role_id')->where('roles.libelle', 'KAM')->get();
        return $results;
    }*/

    public function listdelegue(Request $request) {
        dd($request);
        return view('ps.dash');
    }

}
