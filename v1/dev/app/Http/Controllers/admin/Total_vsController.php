<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\RapportMed;
use App\Total_vs;
use App\Secteur;

use Illuminate\Support\Facades\DB;

class Total_vsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $current_year = new carbon();

        $datas = array();

        $dm_list = RapportMed::select('DELEGUE as  delegue')->distinct()->get();

        foreach ($dm_list as  $dm) {

            $ville = RapportMed::select('ville_id')->where('DELEGUE',$dm->delegue)->whereNotNull('ville_id')->orderby('ville_id','asc')->first();

            $dates   = RapportMed::select('Date_de_visite as date_visite')->where('DELEGUE',$dm->delegue)->whereYear('Date_de_visite',$current_year->year)->distinct()->orderby('Date_de_visite','desc')->get();

            foreach ($dates as $date ) {

                $Total_visites_Med = RapportMed::select('Plan/Réalisé as total_visite_med')->where([['Date_de_visite',$date->date_visite],['DELEGUE',$dm->delegue]])->count();

                // insert(['Date_de_visite' =>$date,'DELEGUE' =>$dm,'Total_visites_Med',$Total_visites_Med]);
                // echo $Total_visites_Med;
                $row = Total_vs::where([ ['Date_de_visite','=',$date->date_visite],['Delegue','=',$dm->delegue ]])->first();

                if( $row === null){

                    Total_vs::create(['Date_de_visite' => Carbon::parse($date->date_visite)->toDateTimeString(),'Delegue' => $dm->delegue,'ville_id'=> $ville->ville_id,'Total_vs' => $Total_visites_Med]);
                }

            }

        }
        // return response()->json($datas,200);
        $total_vs_week = Total_vs::orderby('Date_de_visite','asc')->get();

        $secteurs = Secteur::select('secteur_id','libelle')->get();
        return view('admin.total_vs.VPJ', compact('total_vs_week','secteurs'));

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
        //
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
    }
}
