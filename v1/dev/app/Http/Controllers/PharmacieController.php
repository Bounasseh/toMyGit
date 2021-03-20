<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePharmaForm;
use App\Http\Requests\UpdatePharmaForm;
use App\Secteur;
use Illuminate\Http\Request;
use App\Ville;
use App\Pharmacie;
use App\Med_pharma_history;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PharmacieController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:DSM|KAM|DM|DPH');
    }

    public function search_pharma(Request $request){

        $secteur_id = session('secteur');
        $q = $request->input('q');

        $pharmacies = Secteur::find($secteur_id)->pharmacies()
                ->select('pharmacie_id','pharmacies.libelle','zone_ph','pharmacies.ville_id')
                ->where('pharmacies.libelle','LIKE',$q.'%')
                ->with(['ville:ville_id,libelle'])->get();

        $data = [];

        $all = $pharmacies->map(function ($item){
            $data['id'] = $item->pharmacie_id;
            $data['pharma_name'] = $item->libelle;
            $data['ville'] = $item->ville->libelle;
            $data['zone'] = $item->zone_ph;
            return $data;
        });

        return response()->json($all);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $PharmaciesColumns = [
            // 'pharmacie_id' => 'Medecin Identifient',
            'ville_id' => 'Ville',
            'libelle' => 'Libelle',
            'tel' => 'Telephone',
            // 'adresse' => 'Adresse',
            'zone_ph' => 'Zone',
            'potentiel' => 'Potentiel',
            // 'user_id' => 'User',
            // 'valid' => 'Valid',
            // 'created_by' => 'Created By',
            // 'created_at' => 'Created At',
            // 'updated_at' => 'Updated At',
        ];

        // if($request->ajax()){
        //     $secteur_id = session('secteur');

        //     $pharmacies = Secteur::find($secteur_id)->pharmacies()->with(['ville:ville_id,libelle'])->get();

        //     return datatables()->of($pharmacies)->toJson();
        // }
        // $list_pharmacies = $spec = DB::table('pharmacies')
        //                               ->join('villes', 'pharmacies.ville_id', '=', 'villes.ville_id')
        //                               ->select('pharmacie_id', 'pharmacies.libelle', 'villes.libelle', 'tel', 'zone_ph', 'potentiel', 'valid')
        //                               ->paginate(50);

        // $list_pharmacies = DB::table('pharmacies')
        //     ->join('villes', 'pharmacies.ville_id', '=', 'villes.ville_id')
        //     ->select('pharmacies.*')
        //     ->get();
        $list_pharmacies = Pharmacie::select('pharmacie_id', 'villes.libelle as ville_id', 'pharmacies.libelle', 'pharmacies.tel', 'pharmacies.zone_ph', 'pharmacies.potentiel')->join('villes', 'pharmacies.ville_id', '=', 'villes.ville_id')->paginate(20);
        // dd($list_pharmacies);

        return view('pharmacies.pharmacie_listes', compact(['PharmaciesColumns', 'list_pharmacies']));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $secteur_id = session('secteur');

        $villes =Secteur::findOrFail($secteur_id)->villes()->select('ville_id','libelle')->get();
        return view('pharmacies.pharmacie_add',compact('villes'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePharmaForm $request)
    {

        $pharmacie = new Pharmacie();
        $pharmacie->libelle = $request->input('pharmacie_nom');
        $pharmacie->ville_id =$request->input('pharmacie_ville');
        $pharmacie->tel = $request->input('pharmacie_tel');
        $pharmacie->adresse = $request->input('pharmacie_adress');
        $pharmacie->zone_ph = $request->input('pharmacie_zone');
        $pharmacie->potentiel =$request->input('pharmacie_potentiel');
        $pharmacie->user_id = Auth::user()->user_id;
        $pharmacie->created_by = Auth::user()->nom . ' ' .  Auth::user()->prenom;
        $pharmacie->valid = 0;

        $pharmacie->save();

        return redirect()->back()->with('status','la pharmacie a été ajoutée avec succès.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $secteur_id = session('secteur');
        $pharmacie = Secteur::find($secteur_id)->pharmacies()->findOrfail($id);

         $historiques = Med_pharma_history::where('model_id','=',$id)
         ->where('table','=','Pharmacie')
         ->orderBy('created_at', 'desc')
         ->take(10)
         ->get();

        return view('pharmacies.pharmacie_show',compact(['pharmacie','historiques']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $secteur_id = session('secteur');
        $pharmacie = Secteur::find($secteur_id)->pharmacies()->findOrfail($id);

        if($pharmacie->valid){
            return redirect()->back()->withErrors(['Error' => 'cette pharmacie est validée, vous ne pouvez pas la modifier']);
        }else{
            $villes = Secteur::find($secteur_id)->villes()->select('ville_id','libelle')->get();

            return view('pharmacies.pharmacie_edit',compact(['pharmacie','villes']));
        }


    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePharmaForm $request, $id)
    {
        $secteur_id = session('secteur');
        $pharmacie = Secteur::find($secteur_id)->pharmacies()->findOrfail($id);

        if($pharmacie->valid){
            return redirect()->back()->withErrors(['Error' => 'cette pharmacie est validée, vous ne pouvez pas la modifier']);
        }

        $pharmacie->libelle = $request->input('pharmacie_nom');
        $pharmacie->ville_id = $request->input('pharmacie_ville');
        $pharmacie->tel = $request->input('pharmacie_tel');
        $pharmacie->adresse = $request->input('pharmacie_adress');
        $pharmacie->zone_ph = $request->input('pharmacie_zone');
        $pharmacie->potentiel = $request->input('pharmacie_potentiel');

        $pharmacie->save();


        return redirect()->route('pharmacies.show',['pharmacie'=>$pharmacie->pharmacie_id])->with('status','modifications ont été effectuées avec succès .');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $secteur_id = session('secteur');
        $pharmacie = Secteur::find($secteur_id)->pharmacies()->findOrfail($id);

        try{
            if($pharmacie->valid){
                return redirect()->back()->withErrors(['Error' => 'Une erreur s\'est produite lors du traitement de votre demande.']);
            }
            elseif ($pharmacie->visites->count() > 0){
                return redirect()->back()->withErrors(['Error' => 'cette pharmacie a été visitée, vous ne pouvez pas la supprimer']);
            }
            $pharmacie->delete();
            return redirect()->route('pharmacies.index')->with('status','Pharmacie supprimée avec succès.');
        }
        catch(\Exception $e){
            return redirect()->back()->withErrors(['Error' => 'Une erreur s\'est produite lors du traitement de votre demande.']);
        }

    }
}
