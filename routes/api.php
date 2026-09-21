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
Route::prefix('users')
  ->controller(UserController::class)
  ->group(function () {
    Route::post('auth', 'login');
    Route::post('register', 'store');
});
// Routes pour les mots de passe oubliés
Route::prefix('password')
  ->controller(PasswordController::class)
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
  // Route pour la modification du profil utilisateur
  Route::prefix('users')
    ->controller(UserController::class)
    ->group(function () {
      Route::get('profiles', 'profiles');
      Route::post('avatars', 'avatars');
      Route::post('logout', 'logout')->name('logout');
  });
  // Route pour les mots de passe
  Route::put('password/editpass', [PasswordController::class, 'editpass']);
  // Route pour les contacts
  Route::controller(PhonebookController::class)->group(function () {
    // Route pour importation
    Route::get('contacts/blacklist', 'contacts');
    Route::post('phonebooks/imports', 'imports');
    Route::post('phonebooks/blacklist', 'blacklist');
  });
  // Route pour les groupes
  Route::prefix('groups')
    ->controller(GroupController::class)
    ->group(function () {
      Route::post('contact/{uid}', 'contact');
      Route::post('imports/{uid}', 'imports');
      Route::post('contacts/{uid}', 'contacts');
      Route::post('blacklist/{uid}', 'blacklist');
      Route::post('contactlist/{uid}', 'contactlist');
  });
  // Route pour les Publipostages
  Route::controller(PublipostageController::class)->group(function () {
    // Route pour importation
    Route::post('publipostage/imports', 'imports');
  });
  // Route pour les senders
  Route::post('senders/validated', [SenderController::class, 'validated']);
  // Route pour les bills
  Route::post('bills/validated', [BillController::class, 'validated']);
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
