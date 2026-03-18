<?php

namespace App\Http\Controllers\API;

use Excel;
use \Carbon\Carbon;
use App\Models\Bill;
use App\Imports\ContactImport;
use Illuminate\Validation\Rule;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{App, Auth, DB, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class BillController extends BaseController
{
    //Liste des factures
    /**
    * @OA\Get(
    *   path="/api/bills?num=1&limit=10",
    *   tags={"Bills"},
    *   operationId="listBill",
    *   description="Liste des factures.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Liste des factures."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function index(Request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            $num = isset($request->num) ? (int) $request->num:1;
            $limit = isset($request->limit) ? (int) $request->limit:10;
            // Code to list factures
            $bills = Bill::select('uid', 'reference', 'volume1_sms', 'volume2_sms', 'price', 'fees', 'status', 'created_at')
            ->where('user_id', Auth::user()->id)
            ->orderByDesc('created_at')
            ->paginate($limit, ['*'], 'page', $num);
            // Vérifier si les données existent
            if ($bills->isEmpty()) {
                Log::warning("Bill::index - Aucune facture trouvée.");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Transformer les données
            $data = $bills->map(fn($data) => [
                'uid' => $data->uid,
                'reference' => $data->reference,
                'volume1_sms' => $data->volume1_sms,
                'volume2_sms' => $data->volume2_sms,
                'price' => $data->price,
                'fees' => $data->fees,
                'total' => $data->price * $data->fees,
                'created_at' => Carbon::parse($data->created_at)->format('d/m/Y H:i'),
            ]);
            return $this->sendSuccess(__('message.listbill'), [
                'lists' => $data,
                'current_page' => $bills->currentPage(),
                'last_page' => $bills->lastPage(),
                'total'  => $bills->total(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Bill::index - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    // Détail d'une facture
    /**
    * @OA\Get(
    *   path="/api/bills/{uid}",
    *   tags={"Bills"},
    *   operationId="showBill",
    *   description="Détail d'une facture.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Détail d'une facture."),
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
            $bills = Bill::where('uid', $uid)->first();
            if (!$bills) {
                Log::warning("Bill::show - Aucune facture trouvée pour l'UID : {$uid}");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Data to save
            $data = [
                'reference' => $bills->reference,
                'volume1_sms' => $bills->volume1_sms,
                'volume2_sms' => $bills->volume2_sms,
                'price' => $bills->price,
                'fees' => $bills->fees,
                'total' => $bills->price * $bills->fees,
            ];
            return $this->sendSuccess(__('message.detbill'), $data);
        } catch (\Exception $e) {
            Log::warning("Bill::show - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    //Enregistrement
    /**
    * @OA\Post(
    *   path="/api/bills",
    *   tags={"Bills"},
    *   operationId="storeBill",
    *   description="Enregistrement d'une facture.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"volume2_sms", "number", "number"},
    *         @OA\Property(property="volume2_sms", type="string"),
    *         @OA\Property(property="number", type="number"),
    *         @OA\Property(property="gender", type="string"),
    *         @OA\Property(property="date_at", type="date"),
    *         @OA\Property(property="field1", type="string"),
    *         @OA\Property(property="field2", type="string"),
    *         @OA\Property(property="field3", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Contact enregisté avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function store(Request $request): JsonResponse {
        // Language
        $user = Auth::user();
        App::setLocale($user->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'volume2_sms' => 'required',
            'number' => [
                'required',
                'digits:9',
                'numeric',
                Rule::unique('contacts')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id)->where('publipostage', 0);
                }),
            ],
            'gender' => 'present',
            'date_at' => 'nullable|date|date_format:Y-m-d',
            'field1' => 'present',
            'field2' => 'present',
            'field3' => 'present',
        ]);
        // Error field
        if ($validator->fails()) {
            Log::warning("Bill::store - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Data to save
        $set = [
            'user_id' => Auth::user()->id,
            'volume1_sms' => Auth::user()->volume,
            'volume2_sms' => $request->volume2_sms,
            'gender' => $request->gender,
            'date_at' => $request->date_at,
            'field1' => $request->field1,
            'field2' => $request->field2,
            'field3' => $request->field3,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            Bill::create($set);
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.addbill'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Bill::store - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    // Modification
    /**
    * @OA\Put(
    *   path="/api/bills/{uid}",
    *   tags={"Bills"},
    *   operationId="editBill",
    *   description="Modification d'une facture.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"label", "number"},
    *         @OA\Property(property="label", type="string"),
    *         @OA\Property(property="number", type="number"),
    *         @OA\Property(property="gender", type="string"),
    *         @OA\Property(property="date_at", type="date"),
    *         @OA\Property(property="field1", type="string"),
    *         @OA\Property(property="field2", type="string"),
    *         @OA\Property(property="field3", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Contact modifié avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function update(request $request, string $uid): JsonResponse {
        // Language
        $user = Auth::user();
        App::setLocale($user->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'label' => 'required',
            'number' => [
                'required',
                'digits:9',
                'numeric',
                Rule::unique('contacts')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id)->where('publipostage', 0);
                }),
            ],
            'gender' => 'present',
            'date_at' => 'nullable|date|date_format:Y-m-d',
            'field1' => 'present',
            'field2' => 'present',
            'field3' => 'present',
        ]);
        // Error field
        if ($validator->fails()) {
            Log::warning("Bill::update - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        // Vérifier préfixe
        $prefix = substr($request->number, 0, 2);
        if (!Prefix::where('label', $prefix)->exists()) {
            Log::warning("Bill::update - Validator number : " . json_encode($request->all()));
            return $this->sendError(__('message.numbernot'), [], 422);
        }
        // Vérifier si l'ID est présent et valide
        $contact = Bill::where('uid', $uid)->first();
        if (!$contact) {
            Log::warning("Bill::update - Aucune facture trouvée pour l'ID : {$uid}");
            return $this->sendSuccess(__('message.nodata'));
        }
        // Data to save
        $set = [
            'label' => $request->label,
            'number' => $request->number,
            'gender' => $request->gender,
            'date_at' => $request->date_at,
            'field1' => $request->field1,
            'field2' => $request->field2,
            'field3' => $request->field3,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $contact->update($set);
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.editbill'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Bill::update - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
	}
    // Suppression d'une facture
    /**
    *   @OA\Delete(
    *   path="/api/bills/delete",
    *   tags={"Bills"},
    *   operationId="deleteBill",
    *   description="Suppression d'un ou de plusieurs factures.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"contacts"},
    *         @OA\Property(property="contacts", type="array", @OA\Items(
    *               example="['335d5855-31b1-44f7-81fd-56e7e7c82a07', 'e504f670-a605-4827-a148-77746018e83f', 'd77b6ee9-8121-4d84-9678-290f7988248d']"
    *           )
    *         ),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Contacts supprimés avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function destroy(Request $request): JsonResponse
    {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator        
        $validator = Validator::make($request->all(), [
            'contacts' => 'required|array',
            'contacts.*' => 'required|uuid',
        ]);
        if ($validator->fails()) {
            Log::warning("Bill::destroy - Validator : {$validator->errors()->first()} - " . json_encode($request->all())
            );
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        try {
            DB::beginTransaction();
            // Récupérer tous les factures en une seule requête
            $contactIds = Bill::whereIn('uid', $request->contacts)
                ->where('user_id', Auth::user()->id)
                ->where('publipostage', 0)
                ->pluck('id');
            if ($contactIds->isEmpty()) {
                DB::rollBack();
                Log::warning("Bill::destroy - Aucune factures trouveés : " . json_encode($request->contacts));
                return $this->sendSuccess(__('message.nodata'));
            }
            // Supprimer tous les pivots liés
            GroupBill::whereIn('contact_id', $contactIds)->delete();
            // Supprimer tous les factures en masse
            Bill::whereIn('id', $contactIds)->delete();
            DB::commit();
            return $this->sendSuccess(__('message.delbill'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::warning("Bill::destroy - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
}
