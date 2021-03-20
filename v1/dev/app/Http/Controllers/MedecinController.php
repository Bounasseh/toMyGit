<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedecinForm;
use App\Http\Requests\UpdateMedecinForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Med_pharma_history;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Medecin;
use App\RapportMed;
use App\Secteur;
use App\Specialite;
use App\Ville;


class MedecinController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:DSM|KAM|DM');
    }

    public function search_medecin(Request $request)
    {
        $q = $request->input('q');
        $secteur_id = session('secteur');
        $medecins = Secteur::findOrFail($secteur_id)->medecins()
                                          ->where(function (Builder $query) use ($q) {
                                              return $query->where(DB::raw("CONCAT(nom,' ',prenom)"),'LIKE',$q.'%');
                                          })->get();

        $data = [];
        $all = $medecins->map(function ($item){
            $data['id'] = $item->medecin_id;
            $data['mednom'] = $item->nom;
            $data['medprenom'] = $item->prenom;
            $data['medspec'] = $item->specialite->code;
            $data['ville'] = $item->ville->libelle;
            $data['etat'] = $item->etablissement;
            $data['zone'] = $item->zone_med;
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
        /*
        if($request){
        $secteur_id = session('secteur');
        $medecins = Secteur::findOrFail($secteur_id)->medecins()->with(['specialite:specialite_id,code','ville:ville_id,libelle'])->get();
        return datatables()->of($medecins)->toJson();
        }*/

        if($request->ajax())
        {
            $secteur_id = session('secteur');
            
            $medecins = Secteur::findOrFail($secteur_id)->medecins()->with(['specialite:specialite_id,code','ville:ville_id,libelle'])->get();
    
            return datatables()->of($medecins)->toJson();
        }
    
       return view('medecins.medecins_index');
    }

    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function page(Request $request)
    {
        $MedecinsColumns = [
            // 'medecin_id' => 'Medecin Identifient',
            'nom' => 'Nom Prenom',
            'prenom' => 'Prenom',
            'adresse' => 'Adresse',
            'tel' => 'Telephone',
            'etablissement' => 'Etablissement',
            'potentiel' => 'Potentiel',
            'zone_med' => 'Zone',
            // 'ville_id' => 'Ville',
            // 'user_id' => 'User Identifient',
            // 'specialite_id' => 'Specialite',
            // 'valid' => 'Valid',
            // 'created_by' => 'Created By',
            // 'created_at' => 'Created At',
            // 'updated_at' => 'Updated At',
        ];
        // dd($MedecinsColumns);
       //$rapport_med =(array) json_decode(DB::table('rapport_meds')->get());
       $list_medecins = Medecin::select()->where('user_id', Auth::user()->user_id)->paginate(20);
       //dd($list_medecins);
       return view('medecins.medecin_index', compact(['MedecinsColumns', 'list_medecins']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $secteur_id = session('secteur');

        $villes = Secteur::findOrFail($secteur_id)->villes()->select('ville_id','libelle')->orderBy('libelle')->get();

        $specialites = Specialite::orderBy('code')->select('specialite_id','code')->get();

        return view('medecins.medecin_add',compact(['villes','specialites']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMedecinForm $request)
    {
        $medecin = new Medecin();

        $medecin->nom = $request->input('nom');
        $medecin->prenom =$request->input('prenom');
        $medecin->specialite_id =$request->input('spec');
        $medecin->adresse = $request->input('adress');
        $medecin->tel = $request->input('tel');
        $medecin->etablissement =$request->input('etab');
        $medecin->potentiel =$request->input('potentiel');
        $medecin->zone_med =$request->input('zone');
        $medecin->ville_id =$request->input('ville');
        $medecin->user_id = Auth::user()->user_id;
        $medecin->created_by = Auth::user()->nom . ' ' .  Auth::user()->prenom;
        $medecin->valid = 0;
        $medecin->save();

        return redirect()->back()->with('status','Médecin ajouté avec succès.');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
        // $secteur_id = session('secteur');
        
        // $medecin    = Secteur::find($secteur_id)->medecins()->findOrfail($id);

        // $historiques = Med_pharma_history::where('model_id','=',$id)
        // ->where('table','=','Medecin')
        // ->orderBy('created_at', 'desc')
        // ->take(10)
        // ->get();
        // dd($historiques);


         // $medecin    = Secteur::find($secteur_id)->medecins()->findOrfail($id);
        //  return view('medecins.medecin_show',compact(['medecin','historiques']));


         $medecin = Medecin::find($id);
        //  dd($medecin);

        return view('medecins.medecin_show',compact(['medecin']));

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
        $medecin = Secteur::find($secteur_id)->medecins()->findOrfail($id);
        // dd($medecin);
        if($medecin->valid){
            return redirect()->back()->withErrors(['Error' => 'ce médecin est validé, vous ne pouvez pas le modifier']);
        }else{
            $villes = Secteur::find($secteur_id)->villes()->select('ville_id','libelle')->get();

            $specialites = Specialite::select('specialite_id','code')->get();

            return view('medecins.medecin_edit',compact(['medecin','villes','specialites']));

        }



    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMedecinForm $request, $id)
    {
        $secteur_id = session('secteur');
        $medecin    = Secteur::find($secteur_id)->medecins()->findOrfail($id);

        if($medecin->valid){
            return redirect()->back()->withErrors(['Error' => 'ce médecin est validé, vous ne pouvez pas le modifier']);
        }

        $medecin->nom = $request->input('nom');
        $medecin->prenom =$request->input('prenom');
        $medecin->specialite_id =$request->input('spec');
        $medecin->adresse = $request->input('adress');
        $medecin->tel = $request->input('tel');
        $medecin->etablissement =$request->input('etab');
        $medecin->potentiel =$request->input('potentiel');
        $medecin->zone_med =$request->input('zone');
        $medecin->ville_id =$request->input('ville');
        $medecin->user_id = Auth::user()->user_id;
        $medecin->valid = 0;

        $medecin->save();

        return redirect()->route('medecins.show',['medecin'=>$medecin->medecin_id])->with('status','Vos modifications sont enregistrées.');
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
        $medecin    = Secteur::find($secteur_id)->medecins()->findOrfail($id);

        try{
            if($medecin->valid){
                return redirect()->back()->withErrors(['Error' => 'ce médecin est validé, vous ne pouvez pas le supprimer.']);
            }
            elseif ($medecin->visites->count() > 0){
                return redirect()->back()->withErrors(['Error' => 'ce médecin a été visité, vous ne pouvez pas le supprimer']);
            }

            $medecin->delete();
            return redirect()->route('medecins.index')->with('status','Médecin supprimé avec succès.');
        }
        catch(\Exception $e){
            return redirect()->back()->withErrors(['Error' => 'Une erreur s\'est produite lors du traitement de votre demande.']);
        }

    }
}
