<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\{Request, JsonResponse};
use App\Models\{StatisticSms, StatisticUser};
use Illuminate\Support\Facades\{App, Auth, DB, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class StatsController extends BaseController
{
    //Statistiques des SMS
    /**
    * @OA\Get(
    *   path="/api/stats/sms?year=2026",
    *   tags={"Statistic"},
    *   operationId="statSMS",
    *   description="Statistiques des SMS.",
    *   security={{"bearer":{}}},
    *   @OA\Parameter(
    *      name="year",
    *      in="query",
    *      required=true,
    *      @OA\Schema(type="integer")
    *   ),
    *   @OA\Response(response=200, description="Statistiques des SMS."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function sms(Request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        $validator = Validator::make($request->all(), [
            'year' => ['required', 'date_format:Y', 'max:' . now()->year],
        ]);
        if ($validator->fails()) {
            Log::warning("Stats::sms - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        try {
            // Code to list statistics
            $statistics = StatisticSms::select('month', 'volume')
            ->where('user_id', Auth::id())
            ->where('year', $request->year)
            ->orderBy('month')
            ->get();
            // Vérifier si les données existent
            if ($statistics->isEmpty()) {
                Log::warning("Stats::sms - Aucun statistique trouvé.");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Transformer les données
            $data = $statistics->map(fn($data) => [
                $data->month => $data->volume,
            ]);
            return $this->sendSuccess(__('message.statistics'), $data);
        } catch (\Exception $e) {
            Log::warning("Stats::sms - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    //Statistiques des Utilisateurs
    /**
    * @OA\Get(
    *   path="/api/stats/users",
    *   tags={"Statistic"},
    *   operationId="statUsers",
    *   description="Statistiques des Utilisateurs.",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Statistiques des Utilisateurs."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function users(Request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Code to list statistics
            $statistics = StatisticUser::select('contacts', 'groups', 'volume')
            ->where('user_id', Auth::id())            ->orderBy('month')
            ->first();
            // Vérifier si les données existent
            if (!$statistics) {
                Log::warning("Stats::users - Aucun statistique trouvé.");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Transformer les données
            $data = [
                'contacts' => $statistics->contacts,
                'groups' => $statistics->groups,
                'volume' => $statistics->volume,
            ];
            return $this->sendSuccess(__('message.statistics'), $data);
        } catch (\Exception $e) {
            Log::warning("Stats::users - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
}
