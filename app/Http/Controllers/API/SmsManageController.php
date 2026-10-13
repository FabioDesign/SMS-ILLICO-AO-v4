<?php

namespace App\Http\Controllers\API;

use App\Services\SmsCounter;
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
            'contacts'   => ['required', 'array', 'min:1'],
            'contacts.*' => ['required', 'distinct', 'digits_between:8,15'],
            'message'    => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            Log::warning("SmsManage::sendsms - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }

        $data = $counter->volume(
            $request->input('message'),
            $request->input('contacts')
        );

        return $this->sendSuccess(__('message.success'), $data);
    }
}
