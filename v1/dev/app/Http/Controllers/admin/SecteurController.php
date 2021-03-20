<?php

namespace App\Http\Controllers\admin;

use App\Secteur;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;



class SecteurController extends Controller
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
        $list_secteur=Secteur::all();
        //
        return view('admin.secteurs.liste_secteurs',compact('list_secteur'));

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
        //
        $validator = Validator::make($request->all(),[
            'libelle'=>'required|min:3',

        ],
            [
                'libelle.*'=>'libelle secteur invalide.',

            ]
        );

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }

        $secteur = new Secteur();

        $secteur->libelle = $request->input('libelle');


        $secteur->save();

        return redirect()->back()->with('status','Secteur crée avec succès.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Secteur  $secteur
     * @return \Illuminate\Http\Response
     */
    public function show(Secteur $secteur)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Secteur  $secteur
     * @return \Illuminate\Http\Response
     */
    public function edit(Secteur $secteur)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Secteur  $secteur
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,  $id)
    {
        //
        $secteur = Secteur::findOrfail($id);

        $validator = Validator::make($request->all(),[
            'up_libelle'=>'required|min:3',
        ],
            [
                'up_libelle.*'=>'libelle secteur invalide.',
            ]
        );

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }

        $secteur->libelle = $request->input('up_libelle');

        $secteur->save();

        return redirect()->back()->with('status','Vos modifications sont enregistrées.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Secteur  $secteur
     * @return \Illuminate\Http\Response
     */
    public function destroy( $id)
    {
        //

        $secteur = Secteur::findOrfail($id);
        try {
            $secteur->delete();
            return redirect()->back()->with('status','Secteur supprimé avec succès.');
        }
        catch (QueryException $exception){
            return redirect()->route('admin.secteurs.index')->withErrors(['Error' => 'vous ne pouvez pas supprimer ce secteur car il est lié à certaines villes']);
        }
        catch(\Exception $e){
            return redirect()->route('admin.secteurs.index')->withErrors(['Error' => 'nous n\'avons pas pu terminer votre demande. s\'il vous plaît essayez à nouveau']);
        }
    }
}
