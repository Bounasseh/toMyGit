<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Gamme;
use Illuminate\Support\Facades\Validator;


class GammeController extends Controller
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
        $liste_gammes = Gamme::all();
        return view('admin.gammes.gammes', compact('liste_gammes'));

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
        $validator = Validator::make($req->all(),[

            'gamme_libelle'=>'required',

            ],
            [
            'gamme_libelle.*'=>'inséré la gamme a ajouter',
            ]
        );

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }
        $gamme = new Gamme();
        $gamme->libelle = $req->input('gamme_libelle');
        $gamme->save();
        return redirect()->back()->with('status','la Gamme a été ajouté avec succès.');
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
        $gamme = Gamme::findOrfail($id);
        return view('admin.gammes.gammes', compact('$gamme'));

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
        $validator = Validator::make($request->all(),[
                'gamme_libelle'=>'required',
            ],
            [
                'gamme_libelle.*'=>'inséré la gamme a modifier',
            ]
        );

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }

        $gamme = Gamme::findOrfail($id);
        $gamme->libelle = $request->input('gamme_libelle');
        $gamme->save();
        return redirect()->back()->with('status','la Gamme a été modifier avec succès.');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $gamme = Gamme::findOrfail($id);
        try {
            $gamme->delete();
            return redirect()->back()->with('status','Ville supprimée avec succès.');
        }
        catch (QueryException $exception){
            return redirect()->route('admin.gammes.index')->withErrors(['Error' => 'vous ne pouvez pas supprimer cette car il est lié à certaines ressources']);
        }
        catch(\Exception $e){
            return redirect()->route('admin.gammes.index')->withErrors(['Error' => 'nous n\'avons pas pu terminer votre demande. s\'il vous plaît essayez à nouveau']);
        }

    }
}
