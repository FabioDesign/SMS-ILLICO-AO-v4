<?php

namespace App\Http\Controllers\API;

use \Carbon\Carbon;
use App\Models\Models;
use Illuminate\Validation\Rule;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{App, Auth, DB, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class ModelController extends BaseController
{
    //Liste des modèles
    /**
    * @OA\Get(
    *   path="/api/models",
    *   tags={"Models"},
    *   operationId="listModel",
    *   description="Liste des modèles.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Liste des modèles."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function index(): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Code to list modèles
            $models = Models::select('uid', 'title', 'message')
            ->where('user_id', Auth::user()->id)
            ->orderBy('title')
            ->get();
            // Vérifier si les données existent
            if ($models->isEmpty()) {
                Log::warning("Models::index - Aucun modèle trouvé.");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Transformer les données
            $data = $models->map(fn($data) => [
                'uid' => $data->uid,
                'title' => $data->title,
                'message' => $data->message,
            ]);
            return $this->sendSuccess(__('message.listmodel'), $data);
        } catch (\Exception $e) {
            Log::warning("Models::index - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    // Détail d'un modèle
    /**
    * @OA\Get(
    *   path="/api/models/{uid}",
    *   tags={"Models"},
    *   operationId="showModel",
    *   description="Détail d'un modèle.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Détail d'un modèle."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function show(string $uid): JsonResponse
    {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Eager Loading (1 seule requête optimisée)
            $models = Models::where('uid', $uid)->first();
            if (!$models) {
                Log::warning("Models::show - Aucun modèle trouvé pour l'UID : {$uid}");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Data to save
            $data = [
                'title' => $models->title,
                'message' => $models->message,
            ];
            return $this->sendSuccess(__('message.detmodel'), $data);
        } catch (\Exception $e) {
            Log::warning("Models::show - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    //Enregistrement
    /**
    * @OA\Post(
    *   path="/api/models",
    *   tags={"Models"},
    *   operationId="storeModel",
    *   description="Enregistrement d'un modèle.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"title", "message"},
    *         @OA\Property(property="title", type="string"),
    *         @OA\Property(property="message", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="modèle enregisté avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function store(Request $request): JsonResponse {
        // Language
        $user = Auth::user();
        App::setLocale($user->lg);
        //Validator
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                Rule::unique('models')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'message' => 'required',
        ]);
        //Error field
        if ($validator->fails()) {
            Log::warning("Models::store - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Data to save
        $set = [
            'title' => $request->title,
            'message' => $request->message,
            'user_id' => Auth::user()->id,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            Models::create($set);
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.addmodel'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Models::store - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    // Modification
    /**
    * @OA\Put(
    *   path="/api/models/{uid}",
    *   tags={"Models"},
    *   operationId="editModel",
    *   description="Modification d'un modèle.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"title", "message"},
    *         @OA\Property(property="title", type="string"),
    *         @OA\Property(property="message", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="modèle modifié avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function update(request $request, string $uid): JsonResponse {
        // Language
        $user = Auth::user();
        App::setLocale($user->lg);
        //Validator
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                Rule::unique('models')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
        ]);
        //Error field
        if($validator->fails()){
            Log::warning("Models::update - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        // Vérifier si l'ID est présent et valide
        $models = Models::where('uid', $uid)->first();
        if (!$models) {
            Log::warning("Models::update - Aucun modèle trouvé pour l'ID : {$uid}");
            return $this->sendSuccess(__('message.nodata'));
        }
        // Data to save
        $set = [
            'title' => $request->title,
            'message' => $request->message,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $models->update($set);
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.editmodel'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Models::update - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
	}
    // Suppression d'un modèle
    /**
    *   @OA\Delete(
    *   path="/api/models/{uid}",
    *   tags={"Models"},
    *   operationId="deleteModel",
    *   description="Suppression d'un modèle.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=201, description="modèle supprimé avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function destroy(string $uid): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Vérification si le Groupe est attribué à une demande
            $models = Models::where('uid', $uid)->first();
            // Suppression
            $deleted = Models::destroy($models->id);
            if (!$deleted) {
                Log::warning("Models::destroy - Tentative de suppression d'un modèle inexistante : {$uid}");
                return $this->sendError(__('message.error'), [], 403);
            }
            return $this->sendSuccess(__('message.delmodel'), [], 201);
        } catch(\Exception $e) {
            Log::warning("Models::destroy - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
}
