<?php

namespace App\Http\Controllers\API;

use Excel;
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
                'total' => ceil(($data->price * $data->volume2_sms + $data->fees) * 1.05),
                'status' => $data->status,
                'libelle' => match((int)$data->status) {
                    0 => __('message.draft'),
                    1 => __('message.pending'),
                    2 => __('message.validated'),
                    3 => __('message.declined'),
                },
                'created_at' => $data->created_at->format('d/m/Y H:i'),
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
                'total' => ceil(($bills->price * $bills->volume2_sms + $bills->fees) * 1.05),
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
    *   description="Enregistrement d'un volume.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"volume2_sms", "price"},
    *         @OA\Property(property="volume2_sms", type="integer"),
    *         @OA\Property(property="price", type="number", format="float"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Volume enregisté avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function store(Request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'volume2_sms' => 'required|integer|min:1',
            'price' => 'required|numeric|exists:prices,amount',
        ]);
        // Error field
        if ($validator->fails()) {
            Log::warning("Bill::store - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        $nbr = 1;
        $bills = Bill::where('created_at', 'LIKE', date('Y-m') . '%')
        ->orderByDesc('created_at')
        ->first();
		if ($bills) $nbr += substr($bills->reference, -4);		    
		// Zerofill
		$nbr = sprintf('%04d', $nbr);
		// Référence
		$ref = 'SI-' . date('ym') . '-' . $nbr;
        // Data to save
        $set = [
            'reference' => $ref,
            'price' => $request->price,
            'user_id' => Auth::user()->id,
            'volume1_sms' => Auth::user()->volume_sms,
            'volume2_sms' => $request->volume2_sms,
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
    *   description="Modification d'un volume.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"volume2_sms", "price"},
    *         @OA\Property(property="volume2_sms", type="integer"),
    *         @OA\Property(property="price", type="number", format="float"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Voume modifié avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function update(request $request, string $uid): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'volume2_sms' => 'required|integer|min:1',
            'price' => 'required|numeric|exists:prices,amount',
        ]);
        // Error field
        if ($validator->fails()) {
            Log::warning("Bill::update - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Vérifier si l'ID est présent et valide
        $bills = Bill::where('uid', $uid)->first();
        if (!$bills) {
            Log::warning("Bill::update - Aucune facture trouvée pour l'UID : {$uid}");
            return $this->sendSuccess(__('message.nodata'));
        }
        // Data to save
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $bills->update([
                'price' => $request->price,
                'volume1_sms' => Auth::user()->volume_sms,
                'volume2_sms' => $request->volume2_sms,
            ]);
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
    *   path="/api/bills/{uid}",
    *   tags={"Bills"},
    *   operationId="deleteBill",
    *   description="Suppression d'une facture.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=201, description="Facture supprimée avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function destroy(string $uid): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Vérification si le Groupe est attribué à une demande
            $deleted = Bill::where('uid', $uid)->delete();
            if (!$deleted) {
                Log::warning("Bill::destroy - Tentative de suppression d'une facture inexistante : {$uid}");
                return $this->sendError(__('message.error'), [], 403);
            }
            return $this->sendSuccess(__('message.delbill'), [], 201);
        } catch(\Exception $e) {
            Log::warning("Bill::destroy - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    // Validation d'une facture
    /**
    * @OA\Post(
    *   path="/api/bills/validated",
    *   tags={"Bills"},
    *   operationId="validBill",
    *   description="Validation d'une facture.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"uid"},
    *         @OA\Property(property="uid", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Facture validée avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function validated(request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        //Validator
        $validator = Validator::make($request->all(), [
            'uid' => 'required|exists:bills,uid',
        ]);
        //Error field
        if ($validator->fails()) {
            Log::warning("Bill::validated - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Vérifier si l'UID est présent et valide
        $bills = Bill::where('uid', $request->uid)->first();
        // Data to save
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $bills->update([
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
            The user <b>{$username}</b> has placed an order of SS.</b><br />M
            Reference : <b>{$bills->reference }</b><br />
            Volume SMS : <b>{$bills->volume2_sms}</b><br /><br />
            <a href='" . env('URL_BACKOFFICE') . "'>Connection to Management Plateforme</a><br /><br />
            <hr style='color:#156082;'>"
            . __('message.bestregard')
            . env('MAIL_SIGNATURE')
            . "</div>";
            // Envoi de l'email
            $this->sendMail(env('MAIL_FROM_ADDRESS'), Auth::user()->email, $username, env('MAIL_CC'), $subject, $message);
            return $this->sendSuccess(__('message.editbill'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Bill::validated - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
	}
}
