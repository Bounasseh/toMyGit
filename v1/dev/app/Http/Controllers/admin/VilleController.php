<?php

namespace App\Http\Controllers\admin;

use App\Secteur;
use App\Ville;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class VilleController extends Controller
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
        $villes = Ville::orderBy('libelle')->get();
        $secteurs = Secteur::select('secteur_id','libelle')->get();
        return view('admin.villes.villes',compact(['villes','secteurs']));
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'libelle'=>'required|min:3',
            'sect' => 'required|exists:secteurs,secteur_id' 
        ],
            [
                'libelle.*'=>'libelle ville invalide.',
                'sect.*'=>'secteur invalid ou n\'exist pas',
            ]
        );

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }

        $ville = new Ville();

        $ville->libelle = $request->input('libelle');
        $ville->secteur_id = $request->input('sect');

        $ville->save();

        return redirect()->back()->with('status','Ville crée avec succès.');
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
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $ville = Ville::findOrfail($id);

        $validator = Validator::make($request->all(),[
            'up_libelle'=>'required|min:3',
            'up_sect' => 'required|exists:secteurs,secteur_id'
        ],
            [
                'up_libelle.*'=>'libelle ville invalide.',
                'up_sect.*'=>'secteur invalid ou n\'exist pas',
            ]
        );

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }

        $ville->libelle = $request->input('up_libelle');
        $ville->secteur_id = $request->input('up_sect');

        $ville->save();

        return redirect()->back()->with('status','Vos modifications sont enregistrées.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ville = Ville::findOrfail($id);
        try {
            $ville->delete();
            return redirect()->back()->with('status','Ville supprimée avec succès.');
        }
        catch (QueryException $exception){
            return redirect()->route('admin.villes.index')->withErrors(['Error' => 'vous ne pouvez pas supprimer cette car il est lié à certaines ressources']);
        }
        catch(\Exception $e){
            return redirect()->route('admin.villes.index')->withErrors(['Error' => 'nous n\'avons pas pu terminer votre demande. s\'il vous plaît essayez à nouveau']);
        }
    }
}
