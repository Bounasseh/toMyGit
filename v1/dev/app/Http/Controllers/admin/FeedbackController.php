<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Feedback;
use Illuminate\Support\Facades\Validator;


class FeedbackController extends Controller
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
        $liste_feedbacks = Feedback::all();
        return view('admin.feedbacks.feedback_index', compact('liste_feedbacks'));

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

            'feedback_libelle'=>'required',

            ],
            [
            'feedback_libelle.*'=>'inséré le feedback a ajouter',
            ]
        );

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }
        $feedback = new Feedback();
        $feedback->libelle = $request->input('feedback_libelle');
        $feedback->save();
        return redirect()->back()->with('status','le feedback a été ajouté avec succès.');

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
        $feedback = Feedback::findOrfail($id);
        return view('admin.feedbacks.feedback_index', compact('$feedback'));

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
        //
        $validator = Validator::make($request->all(),[

            'feedback_libelle'=>'required',

            ],
            [
            'feedback_libelle.*'=>'inséré la feedback a modifier',
            ]
        );

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }

        $feedback          = Feedback::findOrfail($id);
        $feedback->libelle = $request->input('feedback_libelle');
        $feedback->save();
        return redirect()->back()->with('status','la feedback a été modifier avec succès.');

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
        $feedback = Feedback::findOrfail($id);
        try {
            $feedback->delete();
            return redirect()->back()->with('status','Ville supprimée avec succès.');
        }
        catch (QueryException $exception){
            return redirect()->route('admin.feedback_index')->withErrors(['Error' => 'vous ne pouvez pas supprimer cette car il est lié à certaines ressources']);
        }
        catch(\Exception $e){
            return redirect()->route('admin.feedback_index')->withErrors(['Error' => 'nous n\'avons pas pu terminer votre demande. s\'il vous plaît essayez à nouveau']);
        }
    }
}
