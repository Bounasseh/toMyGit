<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Specialite;
use App\Gamme;

use Illuminate\Support\Facades\Validator;

class SpecialiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:ADMIN|SUPADMIN');

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //

        $liste_specialites = Specialite::all();
        $liste_gammes = Gamme::all();
        return view('admin.specialites.specialite_index', compact('liste_specialites','liste_gammes'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $req)
    {
        //
        $validator = Validator::make($req->all(),[

            'specialite_code'=>'required',
            'specialite_libelle'=>'required',
            'gamme_libelle' => 'required|exists:gammes,gamme_id'

            ],
            [
            'specialite_code.*'=>'inséré la spécialite a ajouter',
            'specialite_libelle.*'=>'inséré  libelle du spécialite a ajouter',
            'gamme_libelle.*'=>'inséré libelle du gamme a ajouter',
            ]
        );
        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }
        $specialite = new Specialite();
        $specialite->code    = $req->input('specialite_code');
        $specialite->libelle = $req->input('specialite_libelle');
        $specialite->gamme_id = $req->input('gamme_libelle');
        $specialite->save();
        return redirect()->back()->with('status','la Spécialite a été ajouté avec succès.');
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $specialte = Specialte::findOrfail($id);
        return view('admin.specialtes.specialte_index', compact('$specialte'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $req, $id)
    {
        $validator = Validator::make($req->all(),[
            'specialite_code'=>'required',
            'specialite_libelle'=>'required',
            'gamme_libelle' => 'required|exists:gammes,gamme_id'

            ],
            [
            'specialite_code.*'=>'inséré la spécialite a ajouter',
            'specialite_libelle.*'=>'inséré  libelle du spécialite a ajouter',
            'gamme_libelle.*'=>'inséré libelle du gamme a ajouter',
            ]
        );
        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }

        $specialite = Specialite::findOrfail($id);
        $specialite->code = $req->input('specialite_code');
        $specialite->libelle = $req->input('specialite_libelle');
        $specialite->gamme_id = $req->input('gamme_libelle');
        $specialite->save();
        return redirect()->back()->with('status','la Spécialite a été modifier avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //

        $specialte = Specialite::findOrfail($id);
        try {
            $specialte->delete();
            return redirect()->back()->with('status','Spécialite supprimée avec succès.');
        }
        catch (QueryException $exception){
            return redirect()->route('admin.specialtes.index')->withErrors(['Error' => 'vous ne pouvez pas supprimer cette car il est lié à certaines ressources']);
        }
        catch(\Exception $e){
            return redirect()->route('admin.specialtes.index')->withErrors(['Error' => 'nous n\'avons pas pu terminer votre demande. s\'il vous plaît essayez à nouveau']);
        }

    }
}
