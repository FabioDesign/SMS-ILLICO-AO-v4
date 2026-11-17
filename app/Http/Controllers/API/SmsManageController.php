<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Services\SmsCounter;
use Illuminate\Http\{Request, JsonResponse};
use App\Models\{BodySms, NumberSms, Prefix, User};
use Illuminate\Support\Facades\{App, Auth, DB, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class SmsManageController extends BaseController
{
    // Constantes
    const DATE_FORMAT = 'd/m/Y H:i:s';
    //Historique des SMS de brouillon
    /**
    * @OA\Get(
    *   path="/api/sms",
    *   tags={"SMS"},
    *   operationId="draftSMS",
    *   description="Historique des SMS de brouillon.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Historique des SMS de brouillon."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function index(Request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Code to list SMS draft
            $sms = BodySms::select(
                'uuid',
                'chars',
                'pages',
                'volume',
                'sender',
                'message',
                'created_at'
            )
            ->withCount('numbers')
            ->where('smstype_id', 1)
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
            // Vérifier si les données existent
            if ($sms->isEmpty()) {
                Log::warning("SmsManage::index - Aucun SMS trouvé.");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Transformer les données
            $data = $sms->map(fn($data) => [
                'uuid' => $data->uuid,
                'chars' => $data->chars,
                'pages' => $data->pages,
                'sender' => $data->sender,
                'volume' => $data->volume,
                'message' => $data->message,
                'contacts' => $data->numbers_count,
                'created_at' => Carbon::parse($data->created_at)->format(self::DATE_FORMAT),
            ]);
            return $this->sendSuccess(__('message.listsms'), $data);
        } catch (\Exception $e) {
            Log::warning("SmsManage::index - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    //Historique des SMS
    /**
    * @OA\Get(
    *   path="/api/sms/history?page=1&limit=10&typesms=&start_date=Y-m-d&end_date=Y-m-d",
    *   tags={"SMS"},
    *   operationId="historySMS",
    *   description="Historique des SMS.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Historique des SMS."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function history(Request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validation des paramètres
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1',
            'typesms' => 'nullable|integer|in:2,3,4',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);
        // Error field
        if ($validator->fails()) {
            Log::warning("SmsManage::index - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        try {
            $page = $request->page ?? 1;
            $limit = $request->limit ?? 10;
            $typesms = $request->typesms ?? '';
            $start_date = $request->start_date ?? '';
            $end_date = $request->end_date ?? '';
            // Code to list SMS
            $sms = BodySms::select(
                'uuid',
                'volume',
                'sender',
                'message',
                'smstype_id',
                'sending_at',
                'created_at'
            )
            ->with(['smstype'])
            ->withCount('numbers')
            ->when(
                $typesms != '',
                fn($q) => $q->where('smstype_id', $typesms),
                fn($q) => $q->where('smstype_id', '!=', 1)
            )
            ->when($start_date != '', fn($q) => $q->where('sending_at', '>=', $start_date . ' 00:00:00'))
            ->when($end_date != '', fn($q) => $q->where('sending_at', '<=', $end_date . ' 23:59:59'))
            ->where('user_id', Auth::id())
            ->orderBy('sending_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
            // Vérifier si les données existent
            if ($sms->isEmpty()) {
                Log::warning("SmsManage::index - Aucun SMS trouvé.");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Transformer les données
            $data = $sms->map(fn($data) => [
                'uuid' => $data->uuid,
                'smstype' => $data->smstype->label,
                'message' => $data->message,
                'sender' => $data->sender,
                'volume' => $data->volume,
                'contacts' => $data->numbers_count,
                'sending_at' => Carbon::parse($data->sending_at)->format(self::DATE_FORMAT),
                'created_at' => Carbon::parse($data->created_at)->format(self::DATE_FORMAT),
            ]);
            return $this->sendSuccess(__('message.listsms'), [
                'lists' => $data,
                'current_page' => $sms->currentPage(),
                'last_page' => $sms->lastPage(),
                'total'  => $sms->total(),
            ]);
        } catch (\Exception $e) {
            Log::warning("SmsManage::index - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    // Détail d'un SMS
    /**
    * @OA\Get(
    *   path="/api/sms/{uuid}",
    *   tags={"SMS"},
    *   operationId="showSMS",
    *   description="Afficher un SMS.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Afficher un SMS."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function show(string $uuid): JsonResponse
    {
        App::setLocale(Auth::user()->lg);

        try {
            $sms = BodySms::where('uuid', $uuid)->first();
            if (!$sms) {
                Log::warning("SmsManage::show - Aucun SMS trouvé pour l'UUID : {$uuid}");
                return $this->sendSuccess(__('message.nodata'));
            }

            $numbers = NumberSms::select('number')
                ->where('bodysms_id', $sms->id)
                ->get();
            // Transformer les données
            $data = [
                'message' => $sms->message,
                'sender' => $sms->sender,
                'chars' => $sms->chars,
                'pages' => $sms->pages,
                'volume' => $sms->volume,
                'contacts' => $numbers->pluck('number'),
            ];
            return $this->sendSuccess(__('message.detsms'), $data);
        } catch (\Exception $e) {
            Log::warning("SmsManage::show - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    // Détail d'un SMS
    /**
    * @OA\Get(
    *   path="/api/sms/detail/{uuid}",
    *   tags={"SMS"},
    *   operationId="detailSMS",
    *   description="Détail d'un SMS.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Détail d'un SMS."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function detail(string $uuid): JsonResponse
    {
        App::setLocale(Auth::user()->lg);

        try {
            $smsId = BodySms::where('uuid', $uuid)->value('id');

            if (!$smsId) {
                Log::warning("SmsManage::detail - Aucun SMS trouvé pour l'UUID : {$uuid}");
                return $this->sendSuccess(__('message.nodata'));
            }

            // Table de correspondance préfixe => réseau (7 lignes, 1 seule requête)
            // Ex : ['91' => 'Movicel', '99' => 'Movicel', '92' => 'Unitel', ...]
            $networks = Prefix::join('networks', 'networks.id', '=', 'prefix.network_id')
                ->pluck('networks.label', 'prefix.label');

            $numbers = NumberSms::select('number', 'volume', 'sending_at')
                ->where('bodysms_id', $smsId)
                ->where('status', '!=', 0)
                ->get();

            $data = $numbers->map(fn ($row) => [
                'network'    => $networks[substr((string) $row->number, 0, 2)] ?? '-',
                'number'     => $row->number,
                'volume'     => $row->volume,
                'sending_at' => $row->sending_at
                    ? Carbon::parse($row->sending_at)->format(self::DATE_FORMAT)
                    : null,
            ]);

            return $this->sendSuccess(__('message.detsms'), $data);
        } catch (\Exception $e) {
            Log::warning("SmsManage::detail - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    // Enregistrement d'un SMS
    /**
    * @OA\Post(
    *   path="/api/sms",
    *   tags={"SMS"},
    *   operationId="storeSMS",
    *   description="Enregistrement d'un SMS.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"smstype", "sender", "message", "contacts", "sending_at"},
    *         @OA\Property(property="smstype", type="integer"),
    *         @OA\Property(property="sender", type="string"),
    *         @OA\Property(property="message", type="string"),
    *         @OA\Property(property="sending_at", type="string", format="date-time"),
    *         @OA\Property(property="contacts", type="array", @OA\Items(type="string")),
    *      )
    *   ),
    *   @OA\Response(response=200, description="SMS enregisté avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function store(Request $request, SmsCounter $counter): JsonResponse
    {
        // Language
        App::setLocale(Auth::user()->lg);
        $validator = Validator::make($request->all(), [
            'smstype' => ['required', 'integer', 'exists:sms_type,id'],
            'sender'     => ['required', 'string', 'max:11'],
            'message'    => ['required', 'string'],
            'sending_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'contacts'   => ['required', 'array', 'min:1'],
            'contacts.*' => ['required', 'distinct', 'digits:9', 'numeric'],
        ]);

        if ($validator->fails()) {
            Log::warning("SmsManage::sendsms - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Vérifier préfixe
        foreach ($request->contacts as $contact) {
            $prefix = substr($contact, 0, 2);
            if (Prefix::where('label', $prefix)->doesntExist()) {
                Log::warning("SmsManage::store - Validator number : " . json_encode($request->all()));
                return $this->sendError(__('message.numbernot'), [], 422);
            }
        }
        // Data from request
        $sender = $request->input('sender');
        $smstype = $request->input('smstype');
        $message = $request->input('message');
        $contacts = $request->input('contacts');
        $data = $counter->volume($message, $contacts);

        if ($smstype != 1) {
            $volume = Auth::user()->volume - $data['total_sms'];
            if ($volume < 0) {
                Log::warning("SmsManage::sendsms - Volume restant : {$volume} - " . json_encode($request->all()));
                return $this->sendError(__('message.limitsms'), ['volume' => $volume], 400);
            }
        }
        // Data to save
        $set = [
            'sender' => $sender,
            'message' => $message,
            'user_id' => Auth::id(),
            'smstype_id' => $smstype,
            'pages' => $data['segments'],
            'volume' => $data['total_sms'],
            'chars' => $data['characters'],
            'sending_at' => $request->input('sending_at'),
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            if ($smstype != 1) {
                User::findOrFail(Auth::id())->update([
                    'volume' => $volume,
                ]);
            }
            $bodysms = BodySms::create($set);
            foreach ($contacts as $contact) :
                $set = [
                    'number' => $contact,
                    'bodysms_id' => $bodysms->id,
                    'volume' => $data['segments'],
                ];
                NumberSms::create($set);
            endforeach;
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.addsms'));
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("SmsManage::sendsms - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    // Modification d'un SMS
    /**
    * @OA\Put(
    *   path="/api/sms/{uuid}",
    *   tags={"SMS"},
    *   operationId="updateSMS",
    *   description="Modification d'un SMS.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"sender", "message", "contacts"},
    *         @OA\Property(property="sender", type="string"),
    *         @OA\Property(property="message", type="string"),
    *         @OA\Property(property="contacts", type="array", @OA\Items(type="string")),
    *      )
    *   ),
    *   @OA\Response(response=200, description="SMS modifié avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function update(Request $request, string $uuid, SmsCounter $counter): JsonResponse
    {
        // Language
        App::setLocale(Auth::user()->lg);
        $sms = BodySms::where('uuid', $uuid)->first();
        if (!$sms) {
            Log::warning("SmsManage::update - Aucun SMS trouvé pour l'UUID : {$uuid}");
            return $this->sendError(__('message.nodata'));
        }
        $validator = Validator::make($request->all(), [
            'sender'     => ['required', 'string', 'max:11'],
            'message'    => ['required', 'string'],
            'contacts'   => ['required', 'array', 'min:1'],
            'contacts.*' => ['required', 'distinct', 'digits:9', 'numeric'],
        ]);

        if ($validator->fails()) {
            Log::warning("SmsManage::sendsms - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Vérifier préfixe
        foreach ($request->contacts as $contact) {
            $prefix = substr($contact, 0, 2);
            if (Prefix::where('label', $prefix)->doesntExist()) {
                Log::warning("SmsManage::store - Validator number : " . json_encode($request->all()));
                return $this->sendError(__('message.numbernot'), [], 422);
            }
        }
        // Data from request
        $sender = $request->input('sender');
        $message = $request->input('message');
        $contacts = $request->input('contacts');
        $data = $counter->volume($message, $contacts);

        // Data to save
        $set = [
            'sender' => $sender,
            'message' => $message,
            'pages' => $data['segments'],
            'volume' => $data['total_sms'],
            'chars' => $data['characters'],
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $sms->update($set);
            NumberSms::where('bodysms_id', $sms->id)->delete();
            foreach ($contacts as $contact) :
                $set = [
                    'number' => $contact,
                    'bodysms_id' => $sms->id,
                    'volume' => $data['segments'],
                ];
                NumberSms::create($set);
            endforeach;
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.updatesms'));
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("SmsManage::update - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    // Suppression d'un SMS
    /**
    *   @OA\Delete(
    *   path="/api/sms/{uuid}",
    *   tags={"SMS"},
    *   operationId="destroySMS",
    *   description="Suppression d'un SMS.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=201, description="SMS supprimé avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function destroy(string $uuid): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Vérification si le Groupe est attribué à une demande
            $sms = BodySms::where('uuid', $uuid)->first();
            // Suppression
            $deleted = BodySms::destroy($sms->id);
            if (!$deleted) {
                Log::warning("SmsManage::destroy - Tentative de suppression d'un SMS inexistant : {$uuid}");
                return $this->sendError(__('message.error'), [], 403);
            }
            $find = NumberSms::where('bodysms_id', $sms->id)->first();
            if ($find) {
                NumberSms::where('bodysms_id', $sms->id)->delete();
            }
            return $this->sendSuccess(__('message.delsms'));
        } catch(\Exception $e) {
            Log::warning("SmsManage::destroy - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
}
