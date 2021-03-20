<?php

namespace App\Http\Controllers\admin;

use App\Gamme;
use App\Produit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class ProduitController extends Controller
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
        $produits = Produit::all();
        $gammes   = Gamme::select('gamme_id', 'libelle')->orderBy('libelle')->get();

        return view('admin.products.product_list', compact(['produits', 'gammes']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $gammes   = Gamme::select('gamme_id', 'libelle')->orderBy('libelle')->get();
        return view('admin.products.product_add', compact(['gammes']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request);
        $validator = Validator::make(
            $request->all(),
            [
                'code' => 'required|min:3',
                'libelle' => 'required|min:3',
                'price' => 'required|numeric|between:1.00,9999.99',
                'gamme' => 'required|exists:gammes,gamme_id'
            ],
            [
                'code.*' => 'Code produit invalid.',
                'libelle.*' => 'libelle produit invalid.',
                'price.*' => 'prix incorrect',
                'gamme.*' => 'gamme invalide ou n\'existe pas',
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $produit = new Produit();
        $produit->code_produit = $request->input('code');
        $produit->libelle = $request->input('libelle');
        $produit->prix = $request->input('price');

        $produit->save();
        $produit->gammes()->attach($request->input('gamme'));

        return redirect()->back()->with('status', 'Produit crée avec succès.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $produit = Produit::findOrfail($id);
        $produit_gammes =  $produit->gammes->pluck('gamme_id')->toArray();
        $gammes   = Gamme::select('gamme_id', 'libelle')->orderBy('libelle')->get();
        return view('admin.products.product_edit', compact(['produit', 'produit_gammes', 'gammes']));
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
        $produit = Produit::findOrfail($id);
        $validator = Validator::make(
            $request->all(),
            [
                'code'   => 'required|min:3',
                'libelle' => 'required|min:3',
                'price' => 'required|numeric|between:1.00,9999.99',
                'produit_gamme' => 'required|numeric|exists:gammes,gamme_id',
            ],
            [
                'code*'    => 'Code Produit invalid',
                'libelle.*' => 'libelle produit invalid.',
                'price.*'  => 'prix incorrect',
                'produit_gamme.*'  => 'gamme invalide ou n\'existe pas',
            ]
        );
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }
        $produit->code_produit = $request->input('code');
        $produit->libelle = $request->input('libelle');
        $produit->prix = $request->input('price');
        $produit->gammes()->sync($request->input('produit_gamme'));
        $produit->save();
        return redirect()->route('admin.products.index')->with('status', 'Vos modifications sont enregistrées.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $produit = Produit::findOrfail($id);
        try {
            $produit->delete();
            return redirect()->back()->with('status', 'Produit supprimé avec succès.');
        } catch (QueryException $exception) {
            return redirect()->route('admin.products.index')->withErrors(['Error' => 'vous ne pouvez pas supprimer ce produit car il est lié à certaines visites']);
        } catch (\Exception $e) {
            return redirect()->route('admin.products.index')->withErrors(['Error' => 'nous n\'avons pas pu terminer votre demande. s\'il vous plaît essayez à nouveau']);
        }
    }
}
