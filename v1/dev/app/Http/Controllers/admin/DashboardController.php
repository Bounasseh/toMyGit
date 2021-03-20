<?php

namespace App\Http\Controllers\admin;


use App\Medecin;
use App\Secteur;
use App\Specialite;
use App\User;
use App\Ville;
use App\VisiteMedical;
use App\RapportMed;
use App\RapportPh;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:ADMIN|SUPADMIN');
    }

    public function home(){
        $villes_med = RapportMed::select('Zone_Ville')->distinct()->get();
        // exit;

        $secteurs = Secteur::orderBy('created_at')->get();
        $specs = Specialite::orderBy('code')->select('specialite_id','code')->get();
        $medecins = Medecin::select('nom','prenom','specialite_id','ville_id','created_by')->latest()->take('10')->get();
        $villes = Ville::whereHas('visitesMedicals')->select('ville_id','libelle')->get();


        return view('admin.dashboard',compact('secteurs','medecins','specs','villes'));
    }

    public function dashManager(Request $request){

        //initialize variable
        $name_secteur_selected='';
        $secteurs = Secteur::all();

        //check if the request is POST
        if ($request->method() == 'POST') {
            $SecteurReturned = $request->input("secteur");
            $selectedSecteur = Secteur::where('libelle',$SecteurReturned)->first();
            $name_secteur_selected = $selectedSecteur->libelle;
            $villes = Ville::where('secteur_id',$selectedSecteur->secteur_id)->orderBy('secteur_id')->get();
         }else{
            $name_secteur_selected = $secteurs->first()->libelle;
            $villes = Ville::where('secteur_id', $secteurs->first()->secteur_id)->get();
            // Ville::where('secteur_id',$selectedSecteur->secteur_id)->orderBy('secteur_id')->get();
        }
        
        // List des mois avec un trie croissant
        $ListMois = [
            0 => 'Tous les mois',
            1 => 'Janvier', 
            2 => 'Février', 
            3 => 'Mars', 
            4 => 'Avril', 
            5 => 'Mai', 
            6 => 'Juin', 
            7 => 'Juillet', 
            8 => 'Août', 
            9 => 'Septembre', 
            10 => 'Octobre', 
            11 => 'Novembre', 
            12 => 'Décembre'
        ];

        // Retrier la list des mois ça dépend le mois actuel
        $ListMois1[now()->month] = $ListMois[now()->month] . ' (actuel)';
        foreach ($ListMois as $index => $month) {
            if($index!=0 && $index!=now()->month) $ListMois1[$index] = $ListMois[$index];
        }
        $ListMois1[0] = $ListMois[0];

        // Reinitialiser la list des mois avec la nouvelle list
        $ListMois = $ListMois1;

        if(0){
            foreach (User::all() as $key => $User) {
                if($User->hasRole(['DSM', 'KAM', 'DM', 'DPH'])){
                    $List[] = 
                    [
                        'Nom_Prenom'=>$User->nom . ' ' . $User->prenom,

                        'Nombre_Visite_Medicales' => RapportMed::select('date_de_visite', DB::raw('COUNT(rapport_med_id) AS CountId'))
                            ->where('delegue_id', $User->user_id)->whereMonth('date_de_visite','>',now()->addMonth(-5))
                            ->groupBy('date_de_visite')->pluck('CountId')->avg(),
                        
                        'Nombre_Visite_Pharmacies' => RapportPh::select('date_de_visite', DB::raw('COUNT(rapport_ph_id) AS CountId'))
                            ->where('delegue_id', $User->user_id)->whereMonth('date_de_visite','>',now()->addMonth(-5))
                            ->groupBy('date_de_visite')->pluck('CountId')->avg(),
                    ];
                }
            }
            dd(collect($List)->SortByDesc('Nombre_Visite_Medicales'));
        }

        return view('admin.dashManager', compact('villes', 'secteurs', 'name_secteur_selected', 'ListMois'));
    }
    
    public function visitesDelegueByVille(Request $request)
    {
        if ($request->ajax()) {
            $ville = $request->input('q');
            $mois = $request->input('m');
            /*
            select DELEGUE, month(Date_de_visite) as mois, COUNT(*) as total
            from rapport_meds
            GROUP BY DELEGUE, mois
            */
            if ($mois == 0)
                $visites = DB::table('rapport_meds')
                ->select(
                    'delegue',
                    DB::raw('month(Date_de_visite) as mois'),
                    DB::raw('count(*) as total')
                )
                ->where('Zone_Ville', $ville)
                ->whereYear('Date_de_visite', now()->year)
                ->groupBy('delegue', 'mois')
                ->get();
            else
                $visites =  DB::table('rapport_meds')
                    ->select(
                        'delegue',
                        DB::raw('DAY(Date_de_visite) as mois'),
                        DB::raw('count(*) as total')
                    )
                    ->where('Zone_Ville', $ville)
                    ->whereMonth('Date_de_visite', $mois)
                    // ->whereMonth('Date_de_visite','<=', now()->month)
                    ->whereYear('Date_de_visite', now()->year)
                    ->groupBy('delegue', 'mois')
                    ->get();
            return response()->json($visites, 200);
        }
        return null;
    }

    public function visiteByDelegue(Request $request){
        if($request->ajax()) {
            $sect = $request->input('q');
            $beginDate =  Carbon::now()->subDays(30);
            $endDate = Carbon::now();

            $visites = Secteur::find($sect)->users()->select('nom','prenom')->withCount(['visiteMedicales' => function (Builder $query) use ($beginDate,$endDate) {
                $query->whereBetween('date_visite',[$beginDate,$endDate]);
            }])->get();

            return response()->json($visites,200);
        }
        return null;
    }

    public function visiteBySpec(Request $request){
      if($request->ajax()) {
            $sect = $request->input('sect');
            $spec = $request->input('spec');
            $beginDate =  Carbon::now()->subDays(90);
            $endDate = Carbon::now();

            $visites = Secteur::find($sect)->users()->select('nom','prenom')->withCount(['visiteMedicales' => function (Builder $query) use ($beginDate,$endDate,$spec) {
                $query->whereHas('medecin',function(Builder $bld) use ($spec) {
                          $bld->where('specialite_id','=',$spec);
                         })->where('etat','!=','plan')
                           ->whereBetween('date_visite',[$beginDate,$endDate]);
            }])->get();

            return response()->json($visites,200);

        }
        else{
            abort(403, 'Unauthorized action.');
        }

    }

    public function visiteByVilles(Request $request){
        if($request->ajax()) {
            $ville = $request->input('q');
            $beginDate =  Carbon::now()->subDays(90);
            $endDate = Carbon::now();

            $visites = Ville::find($ville)->users()->select('nom','prenom')->withCount(['visiteMedicales' => function (Builder $query) use ($beginDate,$endDate) {
                $query->where('etat','!=','plan')->whereBetween('date_visite',[$beginDate,$endDate]);
            }])->get();
            return response()->json($visites,200);
        }
    }

    public function Chart2(Request $request){
        if($request->ajax()){
            // $List[] = ['Nom_Prenom', 'Nombre_Visite_Medicales', 'Nombre_Visite_Pharmacies', 'null'];
            // $List[] = ['Nom Prenom', 'Moyenne Visite Medicales', 'Moyenne (Med/Phar)', 'Moyenne Visite Pharmacies'];
            $List[] = ['Nom Prenom', 'Moyenne Visite Medicales'];
            // $List[] = ['Nom Prenom', 'Moyenne Visite Medicales', 'Moyenne Visite Pharmacies'];
            // $List[] = ['Nom et Prenom de délégué', 'Moyenne Visite Medicales', ['role' => 'style' ]];
            $i = 0;
            foreach (User::all() as $User) {
                if($User->hasRole(['DSM', 'KAM', 'DM', 'DPH'])){

                    $Nombre_Visite_Medicales = RapportMed::select('date_de_visite', DB::raw('COUNT(rapport_med_id) AS CountId'))
                        ->where('delegue_id', $User->user_id)->where('date_de_visite','>',now()->addMonth(-3)->format('Y-m-d'))
                        ->groupBy('date_de_visite')->pluck('CountId')->avg();

                    $Nombre_Visite_Pharmacies = RapportPh::select('date_de_visite', DB::raw('COUNT(rapport_ph_id) AS CountId'))
                        ->where('delegue_id', $User->user_id)->where('date_de_visite','>',now()->addMonth(-3)->format('Y-m-d'))
                        ->groupBy('date_de_visite')->pluck('CountId')->avg();

                    if(false && $User->user_id==32)
                    dd(
                        $Nombre_Visite_Pharmacies = RapportPh::select('date_de_visite', DB::raw('COUNT(rapport_ph_id) AS CountId'))
                        ->where('delegue_id', $User->user_id)->where('date_de_visite','>',now()->addMonth(-3)->format('Y-m-d'))
                        ->groupBy('date_de_visite')->pluck('CountId'),
                        $Nombre_Visite_Medicales, 
                        $Nombre_Visite_Pharmacies,
                        now()->addMonth(-3)->format('Y-m-d'),
                        RapportMed::select('date_de_visite', DB::raw('COUNT(rapport_med_id) AS CountId'))
                        ->where('delegue_id', $User->user_id)
                        ->where('date_de_visite','>',now()->addMonth(-3)->format('Y-m-d'))
                        ->groupBy('date_de_visite')
                        ->get()
                        // ->pluck('CountId')
                        // ->avg()
                        ,
                        RapportPh::select('date_de_visite', DB::raw('COUNT(rapport_ph_id) AS CountId'))
                        ->where('delegue_id', $User->user_id)->whereMonth('date_de_visite','>',now()->addMonth(-3))
                        ->groupBy('date_de_visite')->pluck('CountId')
                    );

                    if($Nombre_Visite_Medicales==null & $Nombre_Visite_Pharmacies==null) continue;

                    $List[] =  array_values(
                        [
                            $User->nom . ' ' . $User->prenom . ' ('.$User->role->libelle.') : ',

                            Round($Nombre_Visite_Medicales, 1),
                            
                            // Round(($Nombre_Visite_Medicales + $Nombre_Visite_Pharmacies)/2, 1),
                            
                            // Round($Nombre_Visite_Pharmacies, 1),

                            // 'style' => 'stroke-color: #871B47; stroke-opacity: 0.6; stroke-width: 8; fill-color: #BC5679; fill-opacity: 0.2',
                        ]
                    );
                }
            }
            return $List;
            // return collect($List)->SortByDesc('Nombre_Visite_Medicales')->toArray();
            // dd
            // (
            //     array_values($List)
            // );
            //     // array_values(collect($List)->SortByDesc('Nombre_Visite_Medicales')),
            // return collect($List)->SortByDesc('Nombre_Visite_Medicales')->toArray();
        }
        else 
        {
            return "is not request ajax !!!";
        }
    }
}
