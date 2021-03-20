<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVisitePharma;
use App\Http\Requests\UpdateVisitePharma;
use App\Pharmacie;
use App\VisitePharmacie;
use Illuminate\Http\Request;
use App\Produit;
use App\RapportPh;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\User;
use App\Feedback;

class VisitePharmacieController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:DSM|KAM|DM|DPH');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /*
        $visites = null;
        $user = Auth::user();

        if($request->has('type')){

            $validator = Validator::make($request->all(),[
                'type' => 'required|in:search',
                'date_debut' => 'required|date_format:d/m/Y',
                'date_fin' => 'required|date_format:d/m/Y|after_or_equal:date_debut',
                'etat' => 'nullable|in:Plan,Réalisé,Réalisé hors plan,Reporté',
            ],[
                'date_debut.*' => 'required|date_format:d/m/Y',
                'date_fin.*' => 'La date de fin doit être supérieure ou égale à la date de début',
                'etat.*' => 'Etat selectionné invalide',
            ]);

            if($validator->fails()){
                return redirect()->back()->withErrors($validator);
            }
            else{
                $date_debut = Carbon::createFromFormat('d/m/Y',$request->input('date_debut'))->format('Y-m-d');
                $date_fin =  Carbon::createFromFormat('d/m/Y',$request->input('date_fin'))->format('Y-m-d');
                $etat = $request->input('etat') ;
                $visites =  $user->visitePharmacies()->whereBetween('date_visite',[$date_debut,$date_fin]);

                if($etat != null) {
                    $visites = $visites->whereRaw('LOWER(etat) = LOWER(?)',[$etat]);
                }

                $visites = $visites->orderBy('date_visite','desc')->paginate(20);
                $visites = $visites->appends(['type' => 'search',
                        'date_debut' => $request->input('date_debut'),
                        'date_fin' => $request->input('date_fin'),
                        'etat' => $etat]
                );
            }

        }
        else{
            $visites = $user->visitePharmacies()->orderBy('date_visite','desc')->paginate(20);
        }

        return view('visites.phvisites.phvisite_index',compact('visites'));
        */

        $user = Auth::user();

        $RapportPhsColumns_ = [
            'Date_de_visite' => 'Date de visite',
            'pharmacie_zone' => 'Pharmacie',
            'Potentiel' => 'Potentiel',
            'Plan/Réalisé' => 'Plan/Réalisé',
        ];

        $RapportPhsColumns =
        [
            'Date_de_visite' => 'Date de visite',
            'pharmacie_zone' => 'PHARMACIE-ZONE',
            'Potentiel' => 'Potentiel',
            // 'P1_présenté' => 'P1 présenté',
            // 'P1_nombre_boites' => 'P1 Nombre de boites',
            // 'P2_présenté' => 'P2 présenté',
            // 'P2_nombre_boites' => 'P2 Nombre de boites',
            // 'P3_présenté' => 'P3 présenté',
            // 'P3_nombre_boites' => 'P3 Nombre de boites',
            // 'P4_présenté' => 'P4 présenté',
            // 'P4_nombre_boites' => 'P4 Nombre de boites',
            // 'P5_présenté' => 'P5 présenté',
            // 'P5_nombre_boites' => 'P5 Nombre de boites',
            'Plan/Réalisé' => 'Plan/Réalisé'
        ];

        // $RapportPhsDelegue = RapportPh::wherein('delegue', [$user->prenom, $user->nom])->whereYear('date_de_visite', now()->year)->paginate(50);
        $RapportPhsDelegue = RapportPh::wherein('delegue', [$user->prenom, $user->nom])->whereYear('date_de_visite', '2020')->paginate(50);
        // dd($RapportPhsDelegue);
        return view('visites.phvisites.phvisite_index', compact(['RapportPhsColumns', 'RapportPhsDelegue']));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //$gammes = Auth::user()->gammes->pluck('gamme_id');

        //$drop_pharma = ['Ahmed', 'Ali', 'Younes', 'TEST'];
        $pharmacies = Pharmacie::orderBy('pharmacie_id')->select('pharmacie_id','libelle')->get();
        //dd($pharmacies);
        //$produits = Produit::ofGammes($gammes)->get();
        $produits = Produit::orderBy('produit_id')->select('produit_id','code_produit')->get();
        return view('visites.phvisites.create_phvisite',compact('produits', 'pharmacies'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreVisitePharma $request)
    {
        //dd($request);
        // $visite = new VisitePharmacie();
        // $visite->date_visite = $request->input('date_v');
        // $visite->pharmacie_id = $request->input('pharma');
        // $visite->etat = mb_strtolower($request->input('etat'));
        // $visite->created_by = Auth::user()->nom . " " .  Auth::user()->prenom;
        // $visite->note = $request->input('potentiel');
        // $visite->user_id = Auth::id();
        // $visite->save();
        //dd($request);
        $rapportph = new RapportPh();
        $rapportph->Date_de_visite = $request->input('date_v');
        $rapportph->pharmacie_zone = $request->input('pharma');
        $rapportph['Plan/Réalisé'] = mb_strtolower($request->input('etat'));
        $rapportph->DELEGUE = Auth::user()->nom . " " .  Auth::user()->prenom;
        $rapportph->Potentiel = $request->input('potentiel');
        $rapportph->DELEGUE_id = Auth::id();
       
        if($request->input('etat') != "Plan"){
            for($i = 0;$i < count($request->input('product')); $i++){
                $j = $i + 1;
                //dd($request->input('product.'.$i));
                $rapportph['P'.$j.'_présenté'] = $request->input('product.'.$i);
                $rapportph['P'.$j.'_nombre_boites'] = $request->input('nbr_b.'.$i);    
            }
        }
        //dd($rapportph);
        
        $rapportph->save();

        // if($request->has('product') && count($request->input('product')) > 0){
        //     for($i = 0;$i < count($request->input('product')); $i++){
        //         $visite->products()->attach($request->input('product.'.$i),
        //             [
        //                 'nb_boites' => $request->input('nbr_b.'.$i),
        //             ]
        //         );
        //     }
        // }

        return redirect()->back()->with('status','Visite pharmacie créé avec succès.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Visite = RapportPH::find($id);
        //return view('visites.medvisites.medvisite_show', compact('Visite'));

        // $visite = Auth::user()->visitePharmacies()->findOrfail($id);
        // dd($visite);
        return view('visites.phvisites.phvisite_show',compact('Visite'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $visite = Auth::user()->visitePharmacies()->findOrfail($id);

        if(empty($visite)){
            return redirect()->back()->withErrors(['Error' => 'Une erreur s\'est produite lors du traitement de votre demande.']);
        }elseif ($visite->etat != "plan"){
            return redirect()->action('VisitePharmacieController@show',$visite->visitephar_id)->withErrors(['Error' => 'Vous avez le droit de modifier seulement les visites planifiées.']);
        }

        $gammes = Auth::user()->gammes->pluck('gamme_id');

        $produits = Produit::ofGammes($gammes)->get();

        return view('visites.phvisites.phvisite_edit',compact(['visite','produits']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVisitePharma $request, $id)
    {

        $visite = Auth::user()->visitePharmacies()->findOrfail($id);

        if(empty($visite)){
            return redirect()->back()->withErrors(['Error' => 'Une erreur s\'est produite lors du traitement de votre demande.']);
        }elseif ($visite->etat != "plan"){
            return redirect()->action('VisitePharmacieController@show',$visite->visitephar_id)->withErrors(['Error' => 'Vous avez le droit de modifier seulement les visites planifiées.']);
        }

        $visite->etat = mb_strtolower($request->input('new_etat'));
        $visite->note = $request->input('note');
        $visite->save();

        if($request->has('product') && count($request->input('product')) > 0){
            for($i = 0;$i < count($request->input('product')); $i++){
                $visite->products()->attach($request->input('product.'.$i),
                    [
                        'nb_boites' => $request->input('nbr_b.'.$i),
                    ]
                );
            }
        }

        return redirect()->action('VisitePharmacieController@show',$visite->visitephar_id)->with('status','Visite modifiée avec succés.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $visite = Auth::user()->visitePharmacies()->findOrfail($id);

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
}
