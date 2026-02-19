<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\{
    ListsController,
    PasswordController,
    PhonebookController,
    RegisterController,
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
Route::post('users/auth', [UserController::class, 'login']);
// Route pour l'inscription
Route::post('users/register', [UserController::class, 'store']);
// Routes pour les mots de passe oubliés
Route::controller(PasswordController::class)->group(function () {
  Route::post('password/verifemail', 'verifemail');
  Route::post('password/verifotp', 'verifotp');
  Route::post('password/addpass', 'addpass');
});
// Route pour les listes
Route::controller(ListsController::class)->group(function () {
  Route::get('towns/list/{lg}', 'towns');
  Route::get('accountyp/list/{lg}', 'accountyp');
});

Route::middleware(['auth:api'])->group(function () {
  // Route pour la modification du profil utilisateur
  Route::controller(UserController::class)->group(function () {
    Route::post('users/profile', 'profile');
    // Route pour la photo de profil
    Route::post('users/photo', 'photo');
    // Route pour la deconnexion
    Route::post('users/logout', 'logout');
  });
  // Route pour les mots de passe
  Route::post('password/editpass', [PasswordController::class, 'editpass']);
  Route::resources([
    'phonebook' => PhonebookController::class,
  ]);
});
