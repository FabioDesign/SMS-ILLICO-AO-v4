<?php

namespace App\Http\Controllers\API;


use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{App, Auth, Hash, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class PasswordController extends BaseController
{
    //Vérification de l'email
    /**
    * @OA\Post(
    *   path="/api/password/verifemail",
    *   tags={"Password"},
    *   operationId="verifemail",
    *   description="Vérification de l'email",
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"lg", "email", "g_recaptcha_response"},
    *         @OA\Property(property="lg", type="string"),
    *         @OA\Property(property="email", type="string", example="fabio@yopmail.com"),
    *         @OA\Property(property="g_recaptcha_response", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=200, description="Vérification de l'email."),
    *   @OA\Response(response=400, description="Bad Request."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function verifemail(Request $request): JsonResponse {
        //Validator
        $validator = Validator::make($request->all(), [
            'lg' => 'required|in:en,pt',
            'email' => 'required|email|exists:users,email',
            'g_recaptcha_response' => 'required',
        ]);
		App::setLocale($request->lg);
        //Error field
        if ($validator->fails()) {
            Log::warning("Password::verifemail - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
            return $this->sendSuccess(__('message.fielderr'), $validator->errors(), 422);
        }
        try {
            // Paramètre de Recapcha
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = [
                'remoteip' => $request->ip(),
                'secret' => env('RECAPTCHAV3_SECRET'),
                'response' => $request->input('g_recaptcha_response'),
            ];
            // Initialiser cURL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);

            $result = curl_exec($curl);

            // Vérifier les erreurs cURL
            if (curl_error($curl)) {
                Log::warning("User::store - cURL Error : " . curl_error($curl));
                return $this->sendError(__('message.error'));
            }
            curl_close($curl);

            $resultJson = json_decode($result);
            if ($resultJson->success == true) {
                try {
                    // Récupérer les données
                    $user = User::where('email', $request->email)->first();
                    // Générer l'OTP sécurisé
                    $otp = random_int(100, 999) . ' ' . random_int(100, 999);
                    //subject
                    $subject = __('message.forgotpwd');
                    $message = "<div style='color:#156082;font-size:11pt;line-height:1.5em;font-family:Century Gothic'>"
                    . __('message.dear') . " " . $user->lastname . ",<br><br>"
                    . __('message.otp') . " : <b>" . $otp . "</b><br><br>
                    <hr style='color:#156082;'>"
                    . __('message.bestregard') . " !<br>"
                    . env('MAIL_SIGNATURE')
                    . "</div>";
                    try {
                        // Envoi de l'email
                        $this->sendMail($request->email, env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'), env('MAIL_CC'), $subject, $message);
                        // Mettre à jour l'utilisateur avec l'OTP et l'horodatage
                        $user->update([
                            'otp' => str_replace(' ', '', $otp),
                            'otp_at' => now(),
                        ]);
                        return $this->sendSuccess(__('message.sendmailsucc'), [], 201);
                    } catch(\Exception $e) {
                        Log::warning("Password::verifemail - Erreur d'envoi de mail : " . $e->getMessage());
                        return $this->sendError(__('message.sendmailerr'));
                    }
                } catch(\Exception $e) {
                    Log::warning("Password::verifemail - Erreur de récupération de l'utilisateur : " . $e->getMessage());
                    return $this->sendError(__('message.error'));
                }
            } else {
                Log::warning("Password::verifemail - Recaptcha : " . json_encode($resultJson));
                return $this->sendError(__('message.recaptcha'));
            }
        } catch (\Exception $e) {
            Log::warning("Password::verifemail - Recaptcha : " . $e->getMessage() . "  " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    //Vérification du Code OTP
    /**
    * @OA\Post(
    *   path="/api/password/verifotp",
    *   tags={"Password"},
    *   operationId="verifotp",
    *   description="Vérification du Code OTP",
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"lg", "email", "otp"},
    *         @OA\Property(property="lg", type="string"),
    *         @OA\Property(property="email", type="string"),
    *         @OA\Property(property="otp", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=200, description="Vérification du Code OTP."),
    *   @OA\Response(response=400, description="Bad Request."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function verifotp(Request $request): JsonResponse {
        //Validator
        $validator = Validator::make($request->all(), [
            'lg' => 'required|in:en,pt',
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);
		App::setLocale($request->lg);
        //Error field
        if ($validator->fails()) {
            Log::warning("Password::verifotp - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
            return $this->sendSuccess(__('message.fielderr'), $validator->errors(), 422);
        }
        try {
            // Récupérer les données
            $user = User::where([
                ['otp', $request->otp],
                ['email', $request->email],
            ])
            ->first();
            // Vérifier si les données existent
            if (!$user) {
                Log::warning("Password::verifotp - Email ou Code OTP erroné : " . json_encode($request->all()));
                return $this->sendError(__('message.otperr'), [], 404);
            }
            // Vérifier si l'OTP a expiré
            if (!($user->otp_at >= now()->subMinutes(5))) {
                Log::warning("Password::verifotp - Code OTP a expiré : " . json_encode($request->all()));
                return $this->sendError(__('message.otpexp'), [], 404);
            }
            return $this->sendSuccess(__('message.otpsucc'));
        } catch(\Exception $e) {
            Log::warning("Password::verifotp - Erreur : " . $e->getMessage() . " " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    //Réinitialisation de Mot de passe
    /**
    * @OA\Put(
    *   path="/api/password/addpass",
    *   tags={"Password"},
    *   operationId="addpass",
    *   description="Ajout de Mot de passe",
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"lg", "email", "otp", "password", "password_confirmation"},
    *         @OA\Property(property="lg", type="string"),
    *         @OA\Property(property="email", type="string"),
    *         @OA\Property(property="otp", type="string"),
    *         @OA\Property(property="password", type="string", format="password"),
    *         @OA\Property(property="password_confirmation", type="string", format="password"),
    *      )
    *   ),
    *   @OA\Response(response=200, description="Mot de passe modifié avec succès."),
    *   @OA\Response(response=400, description="Bad Request."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function addpass(Request $request){
        //Validator
        $validator = Validator::make($request->all(), [
            'lg' => 'required|in:en,pt',
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
            'password' => [
                'required', 'confirmed',
                Password::min(8)
                    ->mixedCase() // Majuscules + minuscules
                    ->letters()   // Doit contenir des lettres
                    ->numbers()   // Doit contenir des chiffres
                    ->symbols()   // Doit contenir des caractères spéciaux
            ],
        ]);
		App::setLocale($request->lg);
        //Error field
        if ($validator->fails()) {
            Log::warning("Password::addpass - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
            return $this->sendSuccess(__('message.fielderr'), $validator->errors(), 422);
        }
        // Récupérer les données
        $user = User::where([
            ['otp', $request->otp],
            ['email', $request->email],
        ])
        ->first();
        // Vérifier si les données existent
        if (!$user) {
            Log::warning("Password::addpass - Email ou Code OTP erroné : " . $request->email);
            return $this->sendError(__('message.emailerr'), [], 404);
        }
        try {
            // Mettre à jour du password
            $user->update([
                'password_at' => now(),
                'password' => Hash::make($request->password),
            ]);
            return $this->sendSuccess(__('message.passwordsucc'), [], 201);
        } catch(\Exception $e) {
            Log::warning("Password::addpass - Erreur : " . $e->getMessage());
            return $this->sendError(__('message.error') . " " . json_encode($request->all()));
        }
    }
    //Modification de Mot de passe
    /**
    * @OA\Put(
    *   path="/api/password/editpass",
    *   tags={"Password"},
    *   operationId="editpass",
    *   description="Modification de Mot de passe",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"oldpass", "password", "password_confirmation"},
    *         @OA\Property(property="oldpass", type="string", format="password"),
    *         @OA\Property(property="password", type="string", format="password"),
    *         @OA\Property(property="password_confirmation", type="string", format="password")
    *      )
    *   ),
    *   @OA\Response(response=200, description="Mot de passe modifié avec succès."),
    *   @OA\Response(response=400, description="Bad Request."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function editpass(Request $request){
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'oldpass' => 'required|min:8',
            'password' => [
                'required', 'confirmed', 'different:oldpass',
                Password::min(8)
                    ->mixedCase() // Majuscules + minuscules
                    ->letters()   // Doit contenir des lettres
                    ->numbers()   // Doit contenir des chiffres
                    ->symbols()   // Doit contenir des caractères spéciaux
            ],
        ]);
        //Error field
        if ($validator->fails()) {
            Log::warning("Password::editpass - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
            return $this->sendSuccess(__('message.fielderr'), $validator->errors(), 422);
        }
        // Vérification de l'ancien mot de passe
        if (!Hash::check($request->oldpass, Auth::user()->password)) {
            Log::warning("Password::editpass - Ancien mot de passe incorrect pour l'utilisateur ID : {Auth::user()->id}");
            return $this->sendError(__('message.passworderr'));
        }
        try {
            // Mettre à jour du password
            User::findOrFail(Auth::user()->id)->update([
                'password_at' => now(),
                'password' => Hash::make($request->password),
            ]);
            return $this->sendSuccess(__('message.passwordsucc'), [], 201);
        } catch(\Exception $e) {
            Log::warning("Password::editpass - Erreur : " . $e->getMessage() . " " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
}
