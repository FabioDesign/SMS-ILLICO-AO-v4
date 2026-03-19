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
            $senders = Sender::select('uid', 'label', 'bydefault', 'status', 'validated_at', 'created_at')
            ->where('user_id', Auth::user()->id)
            ->orderByDesc('created_at')
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
                'bydefault' => $data->bydefault,
                'status' => $data->status,
                'libelle' => match((int)$data->status) {
                    0 => __('message.draft'),
                    1 => __('message.pending'),
                    2 => __('message.validated'),
                    3 => __('message.declined'),
                },
                'created_at' => Carbon::parse($data->created_at)->format('d/m/Y H:i'),
                'validated_at' => $data->validated_at != null ? Carbon::parse($data->validated_at)->format('d/m/Y H:i') : '',
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
                'bydefault' => $senders->bydefault,
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
    *         required={"label", "bydefault"},
    *         @OA\Property(property="label", type="string"),
    *         @OA\Property(property="bydefault", type="integer", enum={0,1}),
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
                'regex:/^(?=.*[A-Za-z])[A-Za-z0-9]{3,11}$/',
                Rule::unique('senders')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'bydefault' => 'required|integer|in:0,1',
        ]);
        //Error field
        if ($validator->fails()) {
            Log::warning("Sender::store - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Vérifier si l'expéditeur par défaut existe déjà
        if ($request->bydefault == 1) {
            Sender::where('user_id', $user->id)->update(['bydefault' => 0]);
        }
        // Data to save
        $set = [
            'label' => $request->label,
            'user_id' => Auth::user()->id,
            'bydefault' => $request->bydefault,
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
    *         required={"label", "bydefault"},
    *         @OA\Property(property="label", type="string"),
    *         @OA\Property(property="bydefault", type="integer", enum={0,1}),
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
        // Vérifier si l'ID est présent et valide
        $senders = Sender::where('uid', $uid)->first();
        if (!$senders) {
            Log::warning("Sender::update - Aucun expéditeur trouvé pour l'UID : {$uid}");
            return $this->sendSuccess(__('message.nodata'));
        }
        //Validator
        $validator = Validator::make($request->all(), [
            'label' => [
                'required',
                'regex:/^(?=.*[A-Za-z])[A-Za-z0-9]{3,11}$/',
                Rule::unique('senders')->where(function ($query) use ($user, $uid) {
                    return $query->where('user_id', $user->id)
                    ->where('uid', '!=', $uid);
                }),
            ],
            'bydefault' => 'required|integer|in:0,1',
        ]);
        //Error field
        if ($validator->fails()) {
            Log::warning("Sender::update - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Vérifier si l'expéditeur par défaut existe déjà
        if ($request->bydefault == 1) {
            Sender::where('user_id', $user->id)->update(['bydefault' => 0]);
        }
        // Data to save
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $senders->update([
                'label' => $request->label,
                'bydefault' => $request->bydefault,
            ]);
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
            $deleted = Sender::where('uid', $uid)->delete();
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
    // Validation d'un expéditeur
    /**
    * @OA\Post(
    *   path="/api/senders/validated",
    *   tags={"Senders"},
    *   operationId="validSender",
    *   description="Validation d'un expéditeur.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"uid"},
    *         @OA\Property(property="uid", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Expéditeur validé avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function validated(Request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'uid' => 'required|exists:senders,uid',
        ]);
        // Error field
        if ($validator->fails()) {
            Log::warning("Sender::validated - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Vérifier si l'UID est présent et valide
        $senders = Sender::where('uid', $request->uid)->first();
        // Data to save
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $senders->update([
                'status' => 1,
            ]);
            // Valider la transaction
            DB::commit();
            // Username
            $username = Auth::user()->firstname . " " . Auth::user()->lastname;            
            // Subject
            $subject = "Sender name";            
            // Send SMS to LogicMind
            $message = "<div style='color:#156082;font-size:11pt;line-height:1.5em;font-family:Century Gothic'>
            Dear Sir,<br /><br />
            The user <b>{$username}</b> has submitted a new sender name for approval.</b><br />
            Sender name : <b>{$senders->label}</b><br /><br />
            <a href='" . env('URL_BACKOFFICE') . "'>Connection to Management Plateforme</a><br /><br />
            <hr style='color:#156082;'>"
            . __('message.bestregard')
            . env('MAIL_SIGNATURE')
            . "</div>";
            // Envoi de l'email
            $this->sendMail(env('MAIL_FROM_ADDRESS'), Auth::user()->email, $username, env('MAIL_CC'), $subject, $message);
            return $this->sendSuccess(__('message.sendsender'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Sender::validated - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
}
