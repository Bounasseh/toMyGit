<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\HomeController;
use \App\User;
use Illuminate\Http\Request;

//routes pour l'export des données en excel

Route::get('/', function(){
    return redirect()->route(session('dashboardUrl'));
})->middleware('auth')->name('home');

Route::name('admin.')->group(function(){

    Route::get('admin/dashboard', 'admin\DashboardController@dashManager')->name('dash');
    Route::get('admin/dashManager', 'admin\DashboardController@dashManager')->name('dashManager');

    Route::get('admin/visites/medicals', 'admin\VisiteController@index_med')->name('visitemed');
    Route::get('admin/visites/medicals/show/{id}', 'admin\VisiteController@show_visite_med')->name('visitemed_show');
    Route::get('admin/visites/medicals/results', 'admin\VisiteController@visitesMed')->name('visitemed_results');

    Route::get('admin/visites/pharma', 'admin\VisiteController@index_ph')->name('visiteph');
    Route::get('admin/visites/pharma/show/{id}', 'admin\VisiteController@show_visite_ph')->name('visiteph_show');
    Route::get('admin/visites/pharma/results', 'admin\VisiteController@visitesPh')->name('visiteph_results');
    Route::resource('admin/medecins','admin\MedecinController');
    Route::resource('admin/pharmacies','admin\PharmacieController');
    Route::resource('admin/bc', 'admin\BcController');

    Route::resource('admin/users', 'UserController');
    Route::put('admin/users/{user_id}/password', 'UserController@changePassword')->name('change-password');
    Route::resource('admin/products', 'admin\ProduitController');
    Route::resource('admin/gammes', 'admin\GammeController');
    Route::resource('admin/villes', 'admin\VilleController');
    Route::resource('admin/secteurs', 'admin\SecteurController');
    Route::resource('admin/specialites', 'admin\SpecialiteController');
    Route::resource('admin/feedbacks', 'admin\FeedbackController');

    /** Product Specialist Dash **/

    Route::get('admin/dash/visites/delegues','admin\DashboardController@visiteByDelegue');
    Route::get('admin/dash/visites/villes','admin\DashboardController@visiteByVilles');
    Route::get('admin/dash/visites/specialities','admin\DashboardController@visiteBySpec');

    /**   Dash Manager  **/
    Route::get('admin/dash/visites/delegueVille','admin\DashboardController@visitesDelegueByVille');
    // Route::resource('refreshSecteur', 'DashboardController')->name('*', 'refreshSecteur');

    Route::resource('admin/total_visites', 'admin\Total_vsController');

    /* Export Excel File Route */
});

Route::get('dash/visites/delegueVille','HomeController@visiteByMed');

Route::prefix('admin')->group(function () {
    Route::get('Exporte_Synthese_Journalier', 'admin\Export_File@View_Exporte_Synthese_Journalier')->name('Exporte_Synthese_Journalier');
    Route::post('Exporte_Synthese_Journalier', 'admin\Export_File@export')->name('Exporte_Synthese_Journalier');
    Route::get('Exporte_Synthese_Hebdomadaire_DM', 'admin\Export_File@View_Exporte_Synthese_Hebdomadaire_DM')->name('Exporte_Synthese_Hebdomadaire_DM');
    // Route::post('Exporte_Synthese_Hebdomadaire_DM', 'admin\Export_File@export')->name('Exporte_Synthese_Hebdomadaire_DM');
    Route::post('Exporte_Synthese_Hebdomadaire_DM', function(Request $request){
        dd($request);
    })->name('Exporte_Synthese_Hebdomadaire_DM');
    Route::get('Exporte_Synthese_Hebdomadaire_DPH', 'admin\Export_File@View_Exporte_Synthese_Hebdomadaire_DPH')->name('Exporte_Synthese_Hebdomadaire_DPH');
    Route::post('Exporte_Synthese_Hebdomadaire_DPH', 'admin\Export_File@export')->name('Exporte_Synthese_Hebdomadaire_DPH');
    Route::get('Import_Business_Case', 'admin\Business_Case@index')->name('Import_Business_Case');
    Route::post('Import_Business_Case', 'admin\Business_Case@import')->name('Import_Business_Case');
    Route::post('refreshSecteur', 'admin\DashboardController@dashManager')->name('refreshSecteur');
    Route::get('Chart2', 'admin\DashboardController@Chart2')->name('Chart2');
});

/* Product specialist routes */
Route::get('/dashboard', 'HomeController@home')->name('ps.dash');

Route::resource('bcs', 'BcController');
Route::get('/bcs', 'BcController@page')->name('bcs.page');

Route::resource('medecins','MedecinController');
Route::get('/medecins', 'MedecinController@page')->name('medecins.page');
Route::post('/medecins/add', 'MedecinController@store')->name('addStore');
Route::get('/searchmedecins','MedecinController@search_medecin')->name('searchmed');

Route::resource('phvisites','VisitePharmacieController');
// Route::get('phvisites/visites/{id}', 'VisitePharmacieController@show')->name('phvisites.visites.show');
Route::get('/searchpharma','PharmacieController@search_pharma')->name('searchph');
Route::resource('pharmacies','PharmacieController');
// Route::get('phvisites/add', 'VisitePharmacieController@create')->name('phvisites.store');

Route::resource('medvisites','VisiteMedicalController');
Route::get('medecins/visites/validation','VisiteMedicalController@validation_index')->name('medvisites.validation');
Route::get('medecins/visites/{id}', 'VisiteMedicalController@show')->name('medecins.visites.show');
Route::post('medecins/visites/validation/{id}', 'VisiteMedicalController@validation_update')->name('medvisites.validation.update');
// Route::get('medvisites/add', 'VisiteMedicalController@create')->name('medvisites.store');


/*************************************************/

/*  Authentification Route */
Auth::routes();

/** Product Specialist Dash **/

Route::get('dash/visites/specialites','HomeController@visiteBySpecialite');

Route::get('dash/visites/ville','HomeController@visiteByVille');

/*  Import Excel File Route Rapport Med */

Route::post('/import_rapportMed','RapportMedController@import')->name('import_rapportMed');
Route::get('/file_import_rapportMed','RapportMedController@index')->name('file_import_rapportMed');
Route::get('/dataRapportMed', 'RapportMedController@getRapportMed')->name('dataRapportMed');
Route::get('/export_rapport_med', 'RapportMedController@export')->name('export_rapport_med');

Route::get('/RapportMedecines','RapportMedController@show')->name('RapportMedecines');
// Route::get('/RapportMedecine', 'RapportMedController@list')->name('RapportMedecines.list');
Route::get('/RapportMedecines/{id}', 'RapportMedController@edit')->name('RapportMedecines.edit');

// Route::get('/show_rapport_med/export/', 'RapportMedController@export')->name('export_rapport_med');

/*  Import Excel File Route Rapport Ph */

Route::post('/import_rapportPh','RapportPhController@import')->name('import_rapportPh');
Route::get('/file_import_rapportPh','RapportPhController@file')->name('file_import_rapportPh');
Route::get('/show_rapport_ph','RapportPhController@index')->name('show_rapport_ph');
Route::get('/show_rapport_ph/{id}','RapportPhController@show')->name('show_rapport_ph.show');
Route::get('/dataRapportPh', 'RapportPhController@getRapportPh')->name('dataRapportPh');
Route::get('/export_rapport_ph', 'RapportPhController@export')->name('export_rapport_ph');


/* Import liste med national */

Route::resource('/Liste_Med_National', 'ListeMedController');
Route::post('/import_liste_med_national','ListeMedController@import')->name('import_liste_med');
Route::get('/data_list_med','ListeMedController@get_Liste_med')->name('get_Liste_med');
Route::get('/show_list_med','ListeMedController@show')->name('show_list_med');

//Route::get('/liste_visites', 'ListeVisitesMedController@index')->name('liste_visites');
//Route::post('/import_liste_visites', 'ListeVisitesMedController@import')->name('import_liste_visites');

Route::get('/liste_visites', 'RapportMedController@index')->name('liste_visites');
// Route::post('/import_liste_visites', 'RapportMedController@import')->name('import_liste_visites');

Route::get('/liste_visitespb', 'RapportPhController@file')->name('liste_visitesph');
Route::get('/RapportPhController/{id}','RapportPhController@show1')->name('RapportPhController.Show1');
Route::post('/medecins','MedecinController@index')->name('medecins.index');


//Route::get('/dashboard', 'HomeController@listeDm')->name('home');

// Route::post('refreshVisite', 'HomeController@listdelegue')->name('refreshVisite');
