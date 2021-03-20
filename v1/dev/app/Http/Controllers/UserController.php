<?php

namespace App\Http\Controllers;
use App\Http\Requests\StoreNewUser;
use App\Http\Requests\UpdateUser;
use Illuminate\Support\Facades\Hash;
use App\Gamme;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use App\Ville;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class UserController extends Controller
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
        // User::hasRoles(['DM', 'DSM', 'DPH', 'KAM']);
        // dd(User::hasRoles(['DM', 'DSM', 'DPH', 'KAM'])->orderBy('manager_id','asc')->paginate(10));
        // dd(User::hasRoles(['Supprimer'])->get());
        // $liste_user = User::hasRoles(['KAM'])->get()->orderBy('manager_id','asc')->paginate(10);
        $liste_user = User::hasRoles(['DM', 'DSM', 'DPH', 'KAM'])->orderBy('manager_id','asc')->paginate(60);
        return view('users.user_listes', compact('liste_user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $villes = Ville::select('ville_id','libelle')->orderBy('libelle')->get();
        $managers = User::select('user_id','nom','prenom')->whereNotIn('role_id',[1,2,5,6])->orderBy('nom')->get();
        $gammes = Gamme::select('gamme_id','libelle')->orderBy('libelle')->get();
        $roles = Role::select('role_id','libelle')->whereNotIn('role_id',[1])->orderBy('libelle')->get();
        return view('users.user_add',compact(['villes','managers','gammes','roles']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreNewUser $req){
        $user = new User();
        $user->nom        = $req->input('user_nom');
        $user->prenom     = $req->input('user_prenom');
        $user->title      = $req->input('user_title');
        $user->email      = $req->input('user_email');
        $user->password   = Hash::make($req->input('password'));
        $user->tel        = $req->input('user_tel');
        $user->ville_id   = $req->input('user_ville');
        $user->role_id    = $req->input('user_role');
        $user->manager_id = $req->input('user_manager');
        $user->save();
        $user->gammes()->attach($req->input('user_gamme'));
        return redirect()->back()->with('status','Utilisateur a été ajouté avec succès.');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return view('users.user_show', ['user' => User::find($id)]);
    }

    public function changePassword(Request $req,$user_id){
        $validator = Validator::make($req->all(),[
            'password'=>'required|confirmed|min:6|max:40',
            'password_confirmation'=>'required',
        ],[
            'password.*'=>'Mot de passe invalid',
            'password_confirmation.*'=>'les mots de passe ne correspondent pas',
        ]);
        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }
        $u = User::findOrfail($user_id);
        $u->password = Hash::make($req->input('password'));
        return redirect()->back()->with('status','Mot de passe a été changé avec succès.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::findOrfail($id);
        $user_gammes =  $user->gammes->pluck('gamme_id')->toArray();
        $villes   = Ville::select('ville_id','libelle')->orderBy('libelle')->get();
        $managers = User::select('user_id','nom','prenom')->whereNotIn('role_id',[1,2,5,6])->orderBy('nom')->get();
        $gammes   = Gamme::select('gamme_id','libelle')->orderBy('libelle')->get();
        $roles    = Role::select('role_id','libelle')->whereNotIn('role_id',[1])->orderBy('libelle')->get();
        return view('users.user_edit',compact(['user','villes','managers','gammes','roles','user_gammes']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUser $req, $id)
    {
        $user = User::findOrfail($id);
        $user->nom        = $req->input('user_nom');
        $user->prenom     = $req->input('user_prenom');
        $user->title      = $req->input('user_title');
        $user->email      = $req->input('user_email');
        $user->tel        = $req->input('user_tel');
        $user->ville_id   = $req->input('user_ville');
        $user->role_id    = $req->input('user_role');
        $user->manager_id = $req->input('user_manager');
        $user->gammes()->sync($req->input('user_gamme'));
        $user->save();
        return redirect()->route('admin.users.index')->with('status','Vos modifications sont enregistrées.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request,$id)
    {
        try{
            $user = User::findOrfail($id);
            $user->role_id = Role::where('libelle', 'Supprimer')->first()->role_id;
            $user->save();
            // $user->delete();
            return redirect()->back()->with('status','Utilisateur supprimé avec succès');
        }
        catch (QueryException $exception){
            return redirect()->route('admin.users.index')->withErrors(['Error' => 'vous ne pouvez pas supprimer ce secteur car il est lié à certaines villes']);
        }
        catch(\Exception $e){
            return redirect()->route('admin.users.index')->withErrors(['Error' => 'nous n\'avons pas pu terminer votre demande. s\'il vous plaît essayez à nouveau']);
        }
    }
}