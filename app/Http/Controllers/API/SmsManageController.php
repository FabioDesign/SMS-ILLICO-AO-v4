<?php

namespace App\Http\Controllers\API;

use App\Services\SmsCounter;
use App\Models\{BodySms, NumberSms, User};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{App, Auth, DB, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class SmsManageController extends BaseController
{
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
    *         required={"contacts", "message"},
    *         @OA\Property(property="contacts", type="array", @OA\Items(type="string")),
    *         @OA\Property(property="message", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="SMS enregisté avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function sendsms(Request $request, SmsCounter $counter): JsonResponse
    {
        // Language
        $user = Auth::user();
        App::setLocale($user->lg);
        $validator = Validator::make($request->all(), [
            'sender'     => ['required', 'string', 'max:11'],
            'message'    => ['required', 'string'],
            'contacts'   => ['required', 'array', 'min:1'],
            'contacts.*' => ['required', 'distinct', 'digits_between:8,15'],
        ]);

        if ($validator->fails()) {
            Log::warning("SmsManage::sendsms - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Data from request
        $sender = $request->input('sender');
        $message = $request->input('message');
        $contacts = $request->input('contacts');
        $data = $counter->volume($message, $contacts);

        $volume = $user->volume_sms - $data['total_sms'];
        if ($volume < 0) {
            Log::warning("SmsManage::sendsms - Volume restant : {$volume} - " . json_encode($request->all()));
            return $this->sendError(__('message.limitsms'), ['volume' => $volume], 400);
        }
        // Data to save
        $set = [
            'status' => 1,
            'smstype_id' => 2,
            'sender' => $sender,
            'message' => $message,
            'sending_at' => now(),
            'user_id' => Auth::id(),
            'pages' => $data['segments'],
            'volume' => $data['total_sms'],
            'chars' => $data['characters'],
        ];
        // dd($set, $volume, $contacts);
        DB::beginTransaction(); // Démarrer une transaction
        try {
            User::findOrFail(Auth::id())->update([
                'volume_sms' => $volume,
            ]);
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
