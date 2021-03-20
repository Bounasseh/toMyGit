<?php

namespace App\Http\Controllers;

use App\Feedback;
use App\Http\Requests\StoreVisiteMedical;
use App\Http\Requests\UpdateVisiteMedical;
use App\Produit;
use App\RapportMed;
use App\Role;
use App\Secteur;
use App\User;
use App\VisiteMedical;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Medecin;

class VisiteMedicalController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:DSM|KAM|DM');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $visites = null;
        $user = Auth::user();
        // if($request->has('type')){

        //     $validator = Validator::make($request->all(),[
        //         'type' => 'required|in:search',
        //         'date_debut' => 'required|date_format:d/m/Y',
        //         'date_fin' => 'required|date_format:d/m/Y|after_or_equal:date_debut',
        //         'etat' => 'nullable|in:Plan,Réalisé,Réalisé hors plan,Reporté',
        //     ],[
        //         'date_debut.*' => 'required|date_format:d/m/Y',
        //         'date_fin.*' => 'La date de fin doit être supérieure ou égale à la date de début',
        //         'etat.*' => 'Etat selectionné invalide',
        //     ]);

        //     if($validator->fails()){
        //        return redirect()->back()->withErrors($validator);
        //     }

        //     else{
        //         $date_debut = Carbon::createFromFormat('d/m/Y',$request->input('date_debut'))->format('Y-m-d');
        //         $date_fin =  Carbon::createFromFormat('d/m/Y',$request->input('date_fin'))->format('Y-m-d');
        //         $etat = $request->input('etat');
        //         $visites =  $user->visiteMedicales()->whereBetween('date_visite',[$date_debut,$date_fin]);

        //         if($etat != null) {
        //             $visites = $visites->whereRaw('LOWER(etat) = LOWER(?)',[$etat]);
        //         }

        //         $visites = $visites->orderBy('date_visite','desc')->paginate(20);
        //         $visites = $visites->appends(['type' => 'search',
        //             'date_debut' => $request->input('date_debut'),
        //             'date_fin' => $request->input('date_fin'),
        //             'etat' => $etat]
        //         );
        //     }
        // }
        // else{
        //    $visites = $user->visiteMedicales()->orderBy('date_visite','desc')->paginate(20);
        // }
        // dd($visites);
        // $visites = RapportMed::wherein('delegue', [$user->prenom, $user->nom])->whereYear('date_de_visite', now()->year)->whereMonth('date_de_visite', now()->month);

        $RapportMedColumns = [
            // 'rapport_med_id' => 'Rapport Med Identifient',
            'Date_de_visite' => 'Date de visite',
            'Nom_Prenom' => 'Nom Prenom',
            'Specialité' => 'Specialité',
            'Etablissement' => 'Etablissement',
            'Potentiel' => 'Potentiel',
            // 'Montant_Inv_Précédents' => 'Montant Inv Précédents',
            // 'Zone_Ville' => 'Zone-Ville',
            // 'P1_présenté' => 'P1 présenté',
            // 'P1_Feedback' => 'P1 Feedback',
            // 'P1_Ech' => 'P1 Ech',
            // 'P2_présenté' => 'P2 présenté',
            // 'P2_Feedback' => 'P2 Feedback',
            // 'P2_Ech' => 'P2 Ech',
            // 'P3_présenté' => 'P3 présenté',
            // 'P3_Feedback' => 'P3 Feedback',
            // 'P3_Ech' => 'P3 Ech',
            // 'P4_présenté' => 'P4 présenté',
            // 'P4_Feedback' => 'P4 Feedback',
            // 'P4_Ech' => 'P4 Ech',
            // 'P5_présenté' => 'P5 présenté',
            // 'P5_Feedback' => 'P5 Feedback',
            // 'P5_Ech' => 'P5 Ech',
            // 'Materiel_Promotion' => 'Materiel Promotion',
            // 'Invitation_promise' => 'Invitation promise',
            'Plan/Réalisé' => 'Plan/Réalisé',
            // 'Visite Individuelle/Double' => 'Visite Individuelle/Double'
        ];

        // $RapportMedDelegue = RapportMed::wherein('delegue', [$user->prenom, $user->nom])->whereYear('date_de_visite', now()->year)->whereMonth('date_de_visite', now()->month)->paginate(20);
        $RapportMedDelegue = RapportMed::wherein('delegue', [$user->prenom, $user->nom])->whereYear('date_de_visite', '2020')->whereMonth('date_de_visite', now()->month)->paginate(20);
        // dd($RapportMedDelegue);
        return view('visites.medvisites.medvisite_index', compact(['RapportMedColumns', 'RapportMedDelegue']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //dd($request);
        // $gammes = Auth::user()->gammes->pluck('gamme_id');
        // $produits = Produit::ofGammes($gammes)->get();
        // $feedback = Feedback::select('feedback_id','libelle')->orderBy('libelle')->get();
        // return view('visites.medvisites.create_medvisite',compact(['produits','feedback']));

        $gammes = Auth::user()->gammes->pluck('gamme_id');

        $produits = Produit::ofGammes($gammes)->get();
        //dd($gammes);
        $feedback = Feedback::select('feedback_id','libelle')->orderBy('libelle')->get();

        //remplir dropdownlist medecin
        //$drop_medecin = ['Ahmed', 'Ali', 'Younes'];
        $medecins = Medecin::orderBy('medecin_id')->select('medecin_id','nom', 'prenom')->get();

        //$produits = Produit::orderBy('produit_id')->select('produit_id','nom', 'prenom')->get();
        $produits = Produit::orderBy('produit_id')->select('produit_id','code_produit')->get();
        //dd($produits);

        return view('visites.medvisites.create_medvisite_test',compact(['produits', 'feedback', 'medecins']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreVisiteMedical $request)
    {

        /*$visite = new VisiteMedical();
        $visite->date_visite = Carbon::createFromFormat('d/m/Y',$request->input('date_v'))->toDateString();
        $visite->medecin_id = $request->input('med');
        $visite->etat = mb_strtolower($request->input('etat'));
        $visite->created_by = Auth::user()->nom . " " .  Auth::user()->prenom;
        $visite->valid = null;
        $visite->note = $request->input('note');
        $visite->user_id = Auth::id();

        $visite->save();

        if($request->has('product') && count($request->input('product')) > 0){
            for($i = 0;$i < count($request->input('product')); $i++){
                $visite->products()->attach($request->input('product.'.$i),
                    [
                        'feedback_id' => $request->input('feedback.'.$i),
                        'nbr_ech' => $request->input('ech.'.$i),
                    ]
                );
            }
        }
        return redirect()->back()->with('status','Visite médicale créé avec succès.');*/
        //return dd($request);
        
        $count_date = 0;
        $count_produit = 0;
        $err_product = 0;

        //verify if there is any error
        //dd($request->input('med')[0]);
        //dd($request->get('date_v'));
        foreach ($request as $key => $date) {
            $errors=array();
            //dd($request);
            //if ($date != null && $request->input('med')[$key] != null && $request->input('etat')[$key] != null) {
            if($request->input('etat') != "Plan") {
                $count_date++;
                for ($i=1; $i <= 5; $i++) {
                    if ($request->input('product_'.$i)[$key] != null) {
                        $count_produit++;
                    }
                }
            }
            //dd($request->input('etat'));
            /*if ($count_produit == 0) {
                $err_product++;
            }*/
            //$count_produit = 0;
        }

        /*
            TRUNCATE feedbacks;
            TRUNCATE vmed_produits;
            TRUNCATE visite_medicals;
        */
        //dd($err_product);
        //store error text
        if ($count_date == 0 || $err_product != 0) {
            $errors[] = "Saisir au moins une date de visites avec un medecin et l'etat";
            $errors[] = "Saisir au moins un produit dans une visite";
        }
        
        //if (count($errors)) {
            //$request->flash();
            //show errors if exists
            //->withInput()
          //  return redirect()->back()->withErrors($errors);
        //}
        //$vartest = implode("|",$request->input('med'));
        //dd($vartest);
        //"SQLSTATE[HY000]: General error: 1366 Incorrect integer value: 'Younes' for column 'medecin_id' at row 1 (SQL: 
           // insert into `visite_medicals` (`date_visite`, `medecin_id`, `etat`, `created_by`, `valid`, `note`, `user_id`, `updated_at`, `created_at`) values (2020-12-01, Younes, plan, BELARABI FIRDAOUSSE, ?, azerty, 21, 2020-12-18 08:49:17, 2020-12-18 08:49:17)) ◀"
        if($request->input('etat') == "Plan"){
            //dd($request);
            // $visite = new VisiteMedical();
            // $visite->date_visite = $request->input('date_v');
            // $visite->medecin_id = 1;
            // $visite->etat = mb_strtolower($request->input('etat'));
            // $visite->created_by = Auth::user()->nom . " " .  Auth::user()->prenom;
            // $visite->valid = null;
            // $visite->note = $request->input('potentiel');
            // $visite->user_id = Auth::id();

            //  $visite->save();
            $rapportMed = new RapportMed();
            $rapportMed->Date_de_visite = $request->input('date_v');
            $rapportMed['Plan/Réalisé'] = mb_strtolower($request->input('etat'));
            $rapportMed->DELEGUE = Auth::user()->nom . " " .  Auth::user()->prenom;
            $rapportMed->Nom_Prenom = $request->input('med');
            $rapportMed->Potentiel = $request->input('potentiel');
            $rapportMed->DELEGUE_id = Auth::id();

             $rapportMed->save();

             return redirect()->back()->with('status','Visite médicale créé avec succès...');
            /*
            $visite = new VisiteMedical();
            $visite->date_visite = $request->input('date_v');
            $visite->medecin_id = implode("|",$request->input('med'));
            $visite->etat = mb_strtolower($request->input('etat'));
            $visite->created_by = Auth::user()->nom . " " .  Auth::user()->prenom;
            $visite->user_id = Auth::id();
            $visite->save();
            return redirect()->back()->with('status','Visite(s) créé avec succès.');*/
            //dd($request->input('etat'));
        }
        else{
            //if every things okay add lines to database
            foreach ($request as $key => $date) {

                $errors=array();
               // if ($date != null && $request->input('med')[$key] != null && $request->input('etat')[$key] != null) {
            
                    $count_date++;
                    //$visite = new VisiteMedical();
                    $rapportMed = new RapportMed();
                    $rapportMed->Date_de_visite = $request->input('date_v');
                    $rapportMed['Plan/Réalisé'] = mb_strtolower($request->input('etat'));
                    $rapportMed->DELEGUE = Auth::user()->nom . " " .  Auth::user()->prenom;
                    $rapportMed->Nom_Prenom = $request->input('med');
                    $rapportMed->Potentiel = $request->input('potentiel');
                    $rapportMed->DELEGUE_id = Auth::id();
                    
                    // for ($i=1; $i <= 5; $i++) {
                    //     if ($request->input('product_'.$i)[$key] != null) {
                    //         $count_produit++;
                    //         $visite->save();
                    //         if ($request->input('feedback_'.$i)[$key] != null) {
                    //             $feedback = new Feedback();
                    //             $feedback->libelle = $request->input('feedback_'.$i)[$key];
                    //             $feedback->save();

                    //             $visite->products()->attach($request->input('product_'.$i)[$key],
                    //             [
                    //             'feedback_id' => $feedback->feedback_id,
                    //             'nbr_ech' => $request->input('ech_'.$i)[$key],
                    //             ]
                    //             );
                    //         }else{
                    //             $visite->products()->attach($request->input('product_'.$i)[$key],
                    //             [
                    //             'nbr_ech' => $request->input('ech_'.$i)[$key],
                    //             ]
                    //         );
                    //         }

                    //     }
                    //     /*else{
                    //         print_r('Line n° '.$key.' product '.$i.' empty');
                    //     }*/
                    // }

               // }
                //else{
                    //var_dump("date empty");
                    //dd($request->input('etat'));
                //}
                //dd($request->input('rowValue'));
                for ($i=0; $i < 5; $i++) {
                    //$rapportph['P'.$j.'_présenté'] = $request->input('product.'.$i);
                    if (isset($request->input('product')[$i])) {
                        $j = $i + 1;
                        $rapportMed['P'.$j.'_présenté'] =  $request->input('product')[$i];
                        $rapportMed['P'.$j.'_FeedBack'] = $request->input('feedback_')[$i];
                        $rapportMed['P'.$j.'_Ech'] = $request->input('nbr_b')[$i];
                    }
                }
                
            }
            //dd($visite);
            $rapportMed->save();
            return redirect()->back()->with('status','Visite(s) créé avec succès.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Visite = RapportMed::find($id);
        return view('visites.medvisites.medvisite_show', compact('Visite'));
        // $visite = Auth::user()->visiteMedicales()->findOrfail($id);
        // return view('visites.medvisites.medvisite_show',compact('visite'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $visite = Auth::user()->visiteMedicales()->findOrfail($id);

        if(empty($visite)){
            return redirect()->back()->withErrors(['Error' => 'Une erreur s\'est produite lors du traitement de votre demande.']);
        }elseif ($visite->etat != "plan"){
            return redirect()->action('VisiteMedicalController@show',$visite->visitemed_id)->withErrors(['Error' => 'Vous avez le droit de modifier seulement les visites planifiées.']);
        }

        $gammes = Auth::user()->gammes->pluck('gamme_id');
        $produits = Produit::ofGammes($gammes)->get();

        $feedback = Feedback::select('feedback_id','libelle')->get();

        return view('visites.medvisites.medvisite_edit',compact(['visite','produits','feedback']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVisiteMedical $request, $id)
    {
        $visite = Auth::user()->visiteMedicales()->findOrfail($id);

        if(empty($visite)){
            return redirect()->back()->withErrors(['Error' => 'Une erreur s\'est produite lors du traitement de votre demande.']);
        }elseif ($visite->etat != "plan"){
            return redirect()->action('VisiteMedicalController@show',$visite->visitemed_id)->withErrors(['Error' => 'Vous avez le droit de modifier seulement les visites planifiées.']);
        }

        $visite->etat = mb_strtolower($request->input('new_etat'));
        $visite->note = $request->input('note');

        $visite->save();

        if($request->has('product') && count($request->input('product')) > 0){
            for($i = 0;$i < count($request->input('product')); $i++){
                $visite->products()->attach($request->input('product.'.$i),
                    [
                        'feedback_id' => $request->input('feedback.'.$i),
                        'nbr_ech' => $request->input('ech.'.$i),
                    ]
                );
            }
        }

        return redirect()->action('VisiteMedicalController@show',$visite->visitemed_id)->with('status','Visite modifiée avec succés.');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $visite = Auth::user()->visiteMedicales()->findOrfail($id);

            if(empty($visite)){
                return redirect()->back()->withErrors(['Error' => 'Visite non supprimée, Une erreur s\'est produite lors du traitement de votre demande.']);
            }
            elseif ($visite->etat != "plan"){
                return redirect()->back()->withErrors(['Error' => 'Vous avez le droit de supprimer seulement les visites planifiées.']);
            }

            $visite->products()->detach();
            $visite->delete();

            return redirect()->back()->with('status','Visite Supprimée');
        }

       catch(\Exception $exception){
            return redirect()->back()->withErrors(['Error' => 'Une erreur s\'est produite lors du traitement de votre demande.']);
        }

    }

    public function validation_index(Request $request){
        $users = null;
        $visites = null;

        if(Auth::user()->isDistrictManager()){
            $users = Secteur::find(session('secteur'))->users()->where('user_id','<>',Auth::id())->select('user_id','nom','prenom')->get();
        } else {
            $users = Auth::user()->collaborateurs()->pluck('user_id','nom','prenom');
        }


        if ($request->filled('query')) {

            $validator = Validator::make($request->all(),[
                'query' =>'required|in:search',
                'date_d'=>'required|date_format:d/m/Y',
                'date_f' => 'required|date_format:d/m/Y|after_or_equal:date_d',
                'dm'=> 'required|in:'.$users->implode('user_id',','),
            ],
                [
                    'query' => 'Une erreur s\'est produite lors du traitement de votre demande.',
                    'date_d.*'=>'Date début invalide',
                    'date_f.*'=>'Date fin invalide',
                    'dm.*'=>'délégue choisi invalid.',
                ]
            );

            if($validator->fails()){
                return redirect()->route('medvisites.validation')->withErrors($validator);
            }

            $date_debut = Carbon::createFromFormat('d/m/Y',$request->input('date_d'))->format('Y-m-d');
            $date_fin =  Carbon::createFromFormat('d/m/Y',$request->input('date_f'))->format('Y-m-d');
            $user = $request->input('dm');

            $visites = VisiteMedical::when($date_debut,function($query) use ($date_debut){
                    $query->where('date_visite','>=',$date_debut);
                })->when($date_fin,function($query) use ($date_fin){
                    $query->where('date_visite','<=',$date_fin);
                })->when($users,function($query) use ($user){
                    $query->where('user_id',$user);
                })->where('etat','<>','plan')
                ->orderBy('date_visite','desc')->get();
            }

        return view('visites.medvisites.validate_visites',compact('users','visites'));
    }

    public function validation_update(Request $request,$id){
         try{
             $v = VisiteMedical::findOrfail($id);
             $v->valid = $request->input('validation_type');
             $v->validated_by = Auth::user()->nom . " " .  Auth::user()->prenom;
             $v->validation_note = $request->input('validation_note');
             $v->save();
             return redirect()->back()->with('status','Visite validée');
         }
         catch(\Exception $exception){
             return redirect()->back()->withErrors(['Error' => 'Une erreur s\'est produite lors du traitement de votre demande.']);
         }

    }

}
