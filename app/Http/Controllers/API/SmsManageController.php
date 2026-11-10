<?php

namespace App\Http\Controllers\API;

use App\Services\SmsCounter;
use Illuminate\Http\{Request, JsonResponse};
use App\Models\{BodySms, NumberSms, Prefix, StatisticSms, User};
use Illuminate\Support\Facades\{App, Auth, DB, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class SmsManageController extends BaseController
{
    //Historique des SMS
    /**
    * @OA\Get(
    *   path="/api/sms/history?page=1&limit=10&search=''",
    *   tags={"SMS"},
    *   operationId="historySMS",
    *   description="Historique des SMS.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Historique des SMS."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function index(Request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            $page = $request->page ?? 1;
            $limit = $request->limit ?? 10;
            $search = $request->search ?? '';
            // Code to list contacts
            $sms = BodySms::select(
                'id',
                'uuid',
                'sender',
                'message',
                'volume',
                'sending_at',
                'created_at'
            )
            ->with(['smstype'])
            ->withCount('numbers')
            ->when($search != '', fn($q) => $q->where('message', 'LIKE', "%{$search}%"))
            ->where('status', 1)
            ->where('smstype_id', '!=', 1)
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
                'sending_at' => $data->sending_at != null ? Carbon::parse($data->sending_at)->format('d/m/Y H:i:s'):'',
                'created_at' => $data->created_at != null ? Carbon::parse($data->created_at)->format('d/m/Y H:i:s'):'',
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
    // Enregistrement d'un SMS
    /**
    * @OA\Post(
    *   path="/api/sms/send",
    *   tags={"SMS"},
    *   operationId="sendSMS",
    *   description="Enregistrement d'un SMS.",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"smstype_id", "sender", "message", "contacts", "sending_at"},
    *         @OA\Property(property="smstype_id", type="integer"),
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
            'smstype_id' => ['required', 'integer', 'exists:sms_type,id'],
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
        $message = $request->input('message');
        $contacts = $request->input('contacts');
        $smstype_id = $request->input('smstype_id');
        $data = $counter->volume($message, $contacts);

        $volume = Auth::user()->volume - $data['total_sms'];
        if ($volume < 0) {
            Log::warning("SmsManage::sendsms - Volume restant : {$volume} - " . json_encode($request->all()));
            return $this->sendError(__('message.limitsms'), ['volume' => $volume], 400);
        }
        // Data to save
        $set = [
            'status' => 1,
            'sender' => $sender,
            'message' => $message,
            'user_id' => Auth::id(),
            'smstype_id' => $smstype_id,
            'pages' => $data['segments'],
            'volume' => $data['total_sms'],
            'chars' => $data['characters'],
            'sending_at' => $request->input('sending_at'),
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            if ($smstype_id != 1) {
                User::findOrFail(Auth::id())->update([
                    'volume' => $volume,
                ]);
                // Statistiques
                $statistic = StatisticSms::firstOrNew([
                    'year' => now()->year,
                    'month' => now()->month,
                    'user_id' => Auth::id(),
                ]);
                $statistic->volume = ($statistic->exists ? $statistic->volume : 0) + $data['total_sms'];
                $statistic->save();
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
}
