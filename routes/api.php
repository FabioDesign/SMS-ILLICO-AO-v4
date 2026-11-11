<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\{
  BillController,
  GroupController,
  ListsController,
  ModelController,
  PasswordController,
  PhonebookController,
  PublipostageController,
  SenderController,
  SmsManageController,
  StatsController,
  UserController,
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//404
Route::fallback(function() {
  $response = [
    'status' => 404,
    'message' => "Page introuvable.",
    'data' => [],
  ];
  return response()->json($response, 404);
});
// Route pour la connexion
Route::controller(UserController::class)
  ->prefix('users')
  ->group(function () {
  Route::post('auth', 'login');
  Route::post('register', 'store');
});
// Routes pour les mots de passe oubliés
Route::controller(PasswordController::class)
  ->prefix('password')
  ->group(function () {
    Route::post('verifemail', 'verifemail');
    Route::post('verifotp', 'verifotp');
    Route::put('addpass', 'addpass');
});
// Route pour les listes
Route::controller(ListsController::class)->group(function () {
  Route::get('towns/list/{lg}', 'towns');
  Route::get('accountyp/list/{lg}', 'accountyp');
});

Route::middleware(['auth:api'])->group(function () {
  // Route pour les bills
  Route::controller(BillController::class)
    ->prefix('bills')
    ->group(function () {
    Route::post('validated', 'validated');
  });
  // Route pour la modification du profil utilisateur
  Route::controller(UserController::class)
    ->prefix('users')
    ->group(function () {
      Route::get('profiles', 'profiles');
      Route::post('avatars', 'avatars');
      Route::post('logout', 'logout')->name('logout');
  });
  // Route pour les mots de passe
  Route::controller(PasswordController::class)
    ->prefix('password')
    ->group(function () {
    Route::put('editpass', 'editpass');
  });
  // Route pour les contacts
  Route::controller(PhonebookController::class)
    ->prefix('phonebooks')
    ->group(function () {
    // Route pour importation
    Route::get('blacklist', 'contacts');
    Route::post('imports', 'imports');
    Route::post('blacklist', 'blacklist');
  });
  // Route pour les groupes
  Route::controller(GroupController::class)
    ->prefix('groups')
    ->group(function () {
    Route::post('imports/{uuid}', 'imports');
    Route::get('contacts/{uuid}', 'contacts');
    Route::post('blacklist/{uuid}', 'blacklist');
    Route::post('addcontact/{uuid}', 'addcontact');
    Route::post('delcontact/{uuid}', 'delcontact');
    Route::get('contactlist/{uuid}', 'contactlist');
  });
  // Route pour les Publipostages
  Route::controller(PublipostageController::class)
    ->prefix('publipostage')
    ->group(function () {
    // Route pour importation
    Route::post('imports', 'imports');
  });
  // Route pour les sms
  Route::controller(SmsManageController::class)
    ->prefix('sms')
    ->group(function () {
    Route::post('send', 'store');
    Route::get('history', 'index');
  });
  // Route pour les senders
  Route::controller(SenderController::class)
    ->prefix('senders')
    ->group(function () {
    Route::get('list', 'list');
    Route::post('validated', 'validated');
  });
  // Route pour les statistiques
  Route::controller(StatsController::class)
    ->prefix('stats')
    ->group(function () {
    Route::get('sms', 'sms');
    Route::get('users', 'users');
  });
  // Route pour les models
  Route::resources([
    'bills' => BillController::class,
    'users' => UserController::class,
    'groups' => GroupController::class,
    'models' => ModelController::class,
    'senders' => SenderController::class,
    'phonebooks' => PhonebookController::class,
    'publipostage' => PublipostageController::class,
  ]);
});
