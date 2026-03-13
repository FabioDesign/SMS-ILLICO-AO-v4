<?php

namespace App\Http\Controllers\API;

use \Carbon\Carbon;
use App\Models\Sender;
use Illuminate\Validation\Rule;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{App, Auth, DB, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class SenderController extends BaseController
{
    //Liste des expéditeurs
    /**
    * @OA\Get(
    *   path="/api/senders",
    *   tags={"Senders"},
    *   operationId="listSender",
    *   description="Liste des expéditeurs.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Liste des expéditeurs."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function index(): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Code to list expéditeurs
            $senders = Sender::select('uid', 'label')
            ->where('user_id', Auth::user()->id)
            ->orderBy('label')
            ->get();
            // Vérifier si les données existent
            if ($senders->isEmpty()) {
                Log::warning("Sender::index - Aucun expéditeur trouvé.");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Transformer les données
            $data = $senders->map(fn($data) => [
                'uid' => $data->uid,
                'label' => $data->label,
                'status' => $data->status,
            ]);
            return $this->sendSuccess(__('message.listsender'), $data);
        } catch (\Exception $e) {
            Log::warning("Sender::index - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    // Détail d'un expéditeur
    /**
    * @OA\Get(
    *   path="/api/senders/{uid}",
    *   tags={"Senders"},
    *   operationId="showSender",
    *   description="Détail d'un expéditeur.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Détail d'un expéditeur."),
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
            $senders = Sender::where('uid', $uid)->first();
            if (!$senders) {
                Log::warning("Sender::show - Aucun expéditeur trouvé pour l'UID : {$uid}");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Data to save
            $data = [
                'label' => $senders->label,
            ];
            return $this->sendSuccess(__('message.detsender'), $data);
        } catch (\Exception $e) {
            Log::warning("Sender::show - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    //Enregistrement
    /**
    * @OA\Post(
    *   path="/api/senders",
    *   tags={"Senders"},
    *   operationId="storeSender",
    *   description="Enregistrement d'un expéditeur.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"label"},
    *         @OA\Property(property="label", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Expéditeur enregisté avec succès."),
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
            'label' => [
                'required',
                'digits:11',
                Rule::unique('senders')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
        ]);
        //Error field
        if ($validator->fails()) {
            Log::warning("Sender::store - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Data to save
        $set = [
            'label' => $request->label,
            'user_id' => Auth::user()->id,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            Sender::create($set);
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.addsender'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Sender::store - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    // Modification
    /**
    * @OA\Put(
    *   path="/api/senders/{uid}",
    *   tags={"Senders"},
    *   operationId="editSender",
    *   description="Modification d'un expéditeur.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"label"},
    *         @OA\Property(property="label", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Expéditeur modifié avec succès."),
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
            'label' => [
                'required',
                'digits:11',
                Rule::unique('senders')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
        ]);
        //Error field
        if($validator->fails()){
            Log::warning("Sender::update - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        // Vérifier si l'ID est présent et valide
        $senders = Sender::where('uid', $uid)->first();
        if (!$senders) {
            Log::warning("Sender::update - Aucun expéditeur trouvé pour l'ID : {$uid}");
            return $this->sendSuccess(__('message.nodata'));
        }
        // Data to save
        $set = [
            'label' => $request->label,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $senders->update($set);
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.editsender'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Sender::update - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
	}
    // Suppression d'un expéditeur
    /**
    *   @OA\Delete(
    *   path="/api/senders/{uid}",
    *   tags={"Senders"},
    *   operationId="deleteSender",
    *   description="Suppression d'un expéditeur.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=201, description="Expéditeur supprimé avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function destroy(string $uid): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Vérification si le Groupe est attribué à une demande
            $senders = Sender::where('uid', $uid)->first();
            // Suppression
            $deleted = Sender::destroy($senders->id);
            if (!$deleted) {
                Log::warning("Sender::destroy - Tentative de suppression d'un expéditeur inexistante : {$uid}");
                return $this->sendError(__('message.error'), [], 403);
            }
            return $this->sendSuccess(__('message.delsender'), [], 201);
        } catch(\Exception $e) {
            Log::warning("Sender::destroy - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
}
