<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{App, Log};
use App\Models\{AccountType, Town};
use App\Http\Controllers\API\BaseController as BaseController;

class ListsController extends BaseController
{
    // Liste de Villes
    /**
    * @OA\Get(
    *   path="/api/towns/list/{lg}",
    *   tags={"Lists"},
    *   operationId="towns",
    *   description="Liste de Villes.",
    *   @OA\Response(response=200, description="Liste de Villes."),
    *   @OA\Response(response=400, description="Bad Request."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function towns($lg): JsonResponse {
		App::setLocale($lg);
        try {
            $towns = Town::select('id', 'label')->get();
            // Vérifier si les données existent
            if ($towns->isEmpty()) {
                Log::warning("List::town - Aucune nationalité trouvée.");
                return $this->sendSuccess(__('message.nodata'));
            }
            return $this->sendSuccess(__('message.townlist'), $towns);
        } catch (\Exception $e) {
            Log::warning("List::town - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.townlisterr'));
        }
    }
    // Liste de Type de Compte
    /**
    * @OA\Get(
    *   path="/api/accountyp/list/{lg}",
    *   tags={"Lists"},
    *   operationId="accountyp",
    *   description="Liste de Type de Compte.",
    *   @OA\Response(response=200, description="Liste de Type de Compte."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function accountyp($lg): JsonResponse {
		App::setLocale($lg);
        try {
            $accountyps = AccountType::select('id', "$lg as label")->get();
            // Vérifier si les données existent
            if ($accountyps->isEmpty()) {
                Log::warning("List::AccountType - Aucune nationalité trouvée.");
                return $this->sendSuccess(__('message.nodata'));
            }
            return $this->sendSuccess(__('message.accountlist'), $accountyps);
        } catch (\Exception $e) {
            Log::warning("List::AccountType - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.accountlisterr'));
        }
    }
}