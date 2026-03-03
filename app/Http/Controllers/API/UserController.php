<?php
namespace App\Http\Controllers\API; 

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rules\Password;
use App\Models\{AccountType, Town, User};
use Illuminate\Support\Facades\{App, Auth, DB, Hash, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class UserController extends BaseController
{
    // Liste des Utilisateurs
    /**
    * @OA\Get(
    *   path="/api/users",
    *   tags={"Users"},
    *   operationId="listUser",
    *   description="Liste des Utilisateurs",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Liste des Utilisateurs."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function index(Request $request): JsonResponse {
        // User
        $authUser = Auth::user();
        App::setLocale($authUser->lg);
        try {
            // Récupérer les données
            $users = User::select('uid', 'lastname', 'firstname', 'number', 'email', 'company', 'status', 'created_at')
            ->where('id', '!=', $authUser->id)
            ->orderByDesc('created_at')
            ->get();
            // Vérifier si les données existent
            if ($users->isEmpty()) {
                Log::warning("User::index - Aucun utilisateur trouvé");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Transformer les données
            $data = $users->map(fn($data) => [
                'uid' => $data->uid,
                'lastname' => $data->lastname,
                'firstname' => $data->firstname,
                'number' => $data->number,
                'email' => $data->email,
                'company' => $data->company,
                'status' => match((int)$data->status) {
                    0 => 'Inactif',
                    1 => 'Actif',
                    2 => 'Bloqué'
                },
                'created_at' => $data->created_at->format('d/m/Y H:i'),
            ]);
            return $this->sendSuccess(__('message.listuser'), $data);
        } catch(\Exception $e) {
            Log::warning("User::index - Erreur : ".$e->getMessage());
            return $this->sendError(__('message.error'));
        }
    }
    // Détail d'Utilisateur
    /**
    * @OA\Get(
    *   path="/api/users/{uid}",
    *   tags={"Users"},
    *   operationId="showUser",
    *   description="Détail d'Utilisateur",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Détail d'Utilisateur."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function show(string $uid): JsonResponse
    {
        // User
        $authUser = Auth::user();
        App::setLocale($authUser->lg);

        try {
            // Eager Loading (1 seule requête optimisée)
            $user = User::with(['town', 'accountType'])
                ->where('uid', $uid)
                ->first();

            if (!$user) {
                Log::warning("User::show - Aucun utilisateur trouvé pour l'UID : " . $uid);
                return $this->sendSuccess(__('message.nodata'));
            }

            $data = [
                'lastname'  => $authUser->lastname,
                'firstname' => $authUser->firstname,
                'number'    => $authUser->number,
                'email'     => $authUser->email,
                'company'   => $authUser->company,
                'nif'       => $authUser->nif,
                'address'   => $authUser->address,
                'website'   => $authUser->website,
                'volume'    => $authUser->volume,

                'towns' => $authUser->town ? [
                    'id'    => $authUser->town->id,
                    'label' => $authUser->town->label,
                ] : null,

                'account_type' => $authUser->accountType ? [
                    'id'    => $authUser->accountType->id,
                    'label' => $authUser->lg === 'en' ? $authUser->accountType->en : $authUser->accountType->fr,
                ] : null,

                'status' => match((int) $authUser->status) {
                    0 => 'Inactif',
                    1 => 'Actif',
                    2 => 'Bloqué',
                    default => 'Inconnu'
                },

                'created_at' => $authUser->created_at->format('d/m/Y H:i'),

                'avatar' => asset('assets/avatars/' . ($authUser->avatar ?? 'avatar.jpg')),
            ];
            return $this->sendSuccess(__('message.detailuser'), $data);
        } catch (\Exception $e) {
            Log::warning("User::show - Erreur : " . $e->getMessage());
            return $this->sendError(__('message.error'));
        }
    }
    //Authentification
    /**
    * @OA\Post(
    *   path="/api/users/auth",
    *   tags={"Users"},
    *   operationId="login",
    *   description="Authenticate Platform and Generate JWT",
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"lg", "login", "password", "g_recaptcha_response"},
    *         @OA\Property(property="lg", type="string"),
    *         @OA\Property(property="login", type="string"),
    *         @OA\Property(property="password", type="string"),
    *         @OA\Property(property="g_recaptcha_response", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=200, description="Authentification éffectuée avec succès."),
    *   @OA\Response(response=401, description="Echec d'authentification."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function login(Request $request): JsonResponse
    {
        //Validator
        $validator = Validator::make($request->all(), [
          'lg' => 'required',
          'login' => 'required',
          'password' => 'required',
          'g_recaptcha_response' => 'required',
        ]);
		App::setLocale($request->lg);
        //Error field
        if ($validator->fails()) {
            Log::warning("User::login - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
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
                $credentialNum = [
                    'number' => $request->login,
                    'password' => $request->password,
                    'status' => 1,
                ];
                $credentialEml = [
                    'email' => $request->login,
                    'password' => $request->password,
                    'status' => 1,
                ];
                if ((Auth::attempt($credentialNum))||(Auth::attempt($credentialEml))) {
                    try {
                        $authUser = Auth::user();
                        // Test si la photo est vide
                        if ($authUser->avatar != '')
                            $avatar = $authUser->avatar;
                        else
                            $avatar = 'avatar.jpg';
                        // Ajouter les informations de l'utilisateur et du profil dans la réponse
                        $data = [
                            'access_token' =>  $authUser->createToken('MyApp')->accessToken,
                            'infos' => [
                                'uid' => $authUser->uid,
                                'lastname' => $authUser->lastname,
                                'firstname' => $authUser->firstname,
                                'number' => $authUser->number,
                                'email' => $authUser->email,
                                'avatar' => env('APP_URL') . '/assets/avatars/' . $avatar,
                            ]
                        ];
                        User::findOrFail($authUser->id)->update([
                            'login_at' => now(),
                            'lg' => $request->lg,
                        ]);
                        // Logs::createLog('Connexion', $authUser->id, 1);
                        return $this->sendSuccess(__('message.authsucc'), $data);
                    } catch (\Exception $e) {
                        Log::warning("User::login - Echec de connexion : " . $e->getMessage());
                        return $this->sendError(__('message.error'));
                    }
                } else {
                    Log::warning("User::login - Authentication : " . json_encode($request->all()));
                    return $this->sendError(__('message.autherr'), [], 401);
                }
            } else {
                Log::warning("User::login - Recaptcha : " . json_encode($resultJson));
                return $this->sendError(__('message.recaptcha'));
            }
        } catch (\Exception $e) {
            Log::warning("User::login - Recaptcha : " . $e->getMessage() . "  " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    //Account creation
    /**
    * @OA\Post(
    *   path="/api/users/register",
    *   tags={"Users"},
    *   operationId="registerUser",
    *   description="Account creation",
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *             required={"lg", "lastname", "firstname", "number", "email", "town_id", "accountyp_id", "g_recaptcha_response"},
    *             @OA\Property(property="lg", type="string"),
    *             @OA\Property(property="lastname", type="string"),
    *             @OA\Property(property="firstname", type="string"),
    *             @OA\Property(property="number", type="string"),
    *             @OA\Property(property="email", type="string"),
    *             @OA\Property(property="town_id", type="integer"),
    *             @OA\Property(property="accountyp_id", type="integer"),
    *             @OA\Property(property="company", type="string"),
    *             @OA\Property(property="nif", type="string"),
    *             @OA\Property(property="address", type="string"),
    *             @OA\Property(property="website", type="string"),
    *             @OA\Property(property="g_recaptcha_response", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Création de compte éffectuée avec succès."),
    *   @OA\Response(response=401, description="Echec de Création de compte."),
    *   @OA\Response(response=404, description="Page introuvable."),
    * )
    */
    public function store(Request $request): JsonResponse
    {
        Log::notice("User::store : " . json_encode($request->all()));
        //Validator
        $validator = Validator::make($request->all(), [
            'lg' => 'required|in:en,pt',
            'lastname' => 'required',
            'firstname' => 'required',
            'number' => 'required|unique:users,number',
            'email' => 'required|email|unique:users,email',
            'town_id' => 'required|integer|min:1',
            'accountyp_id' => 'required|integer|min:1',
            'g_recaptcha_response' => 'required',
        ]);
		App::setLocale($request->lg);
        //Error field
        if ($validator->fails()) {
            Log::warning("User::store - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
            return $this->sendSuccess(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Test sur DID
        if ($request->accountyp_id != 1) {
            // Validator
            $validator = Validator::make($request->all(), [
                'company' => 'required',
                'nif' => 'required',
                'address' => 'required',
                'website' => 'required',
            ]);
            // Error field
            if ($validator->fails()) {
                Log::warning("User::store - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
                return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
            }
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
                // Formatage du nom et prénoms
                $email = Str::lower($request->email);
                $set = [
                    'lg' => $request->lg,
                    'lastname' => $request->lastname,
                    'firstname' => $request->firstname,
                    'number' => $request->number,
                    'email' => $email,
                    'town_id' => $request->town_id,
                    'accountyp_id' => $request->accountyp_id,
                    'company' => $request->company ?? '',
                    'nif' => $request->nif ?? '',
                    'address' => $request->address ?? '',
                    'website' => $request->website ?? '',
                    'password_at' => now(),
                    'password' => Hash::make("Azerty@123"),
                ];
                DB::beginTransaction(); // Démarrer une transaction
                try {
                    // Création de l'utilisateur
                    User::create($set);
                    DB::commit(); // Valider la transaction
                    // Username
                    $username = $request->firstname . " " . $request->lastname;
                    // Subject
                    $subject = __('message.creataccount');
                    // Send SMS to LogicMind
                    $message = "<div style='color:#156082;font-size:11pt;line-height:1.5em;font-family:Century Gothic'>
                    Dear Mr.,<br /><br />
                    Confirmation mail of registration of <b>" . $username . "</b><br />
                    Contact : <b>" . $request->number . "</b><br />
                    Email : <b>" . $email . "</b><br />
                    Business Name : <b>" . $request->company . "</b><br />";
                    $message .=  "<br />
                    <hr style='color:#156082;'>"
                    . __('message.bestregard')
                    . env('MAIL_SIGNATURE')
                    . "<hr style='color:#156082;'></div>";
                    // Envoi de l'email
                    $this->sendMail(env('MAIL_FROM_ADDRESS'), $email, $username, env('MAIL_CC'), $subject, $message);

                    //send SMS to Customer
                    if ($request->lg == 'en') {
                        $content = "Dear M./Mrs. " . $username . "<br /><br />
                        Thank you for your registration on SMS illico, our platform of sending SMS through the web.<br />
                        Your registration has been taken into account and will be validated within 48 hours maximum after verification of provided information.<br />
                        You will receive an SMS and a mail after the activation of your account.<br /><br />";
                    } else {
                        $content = "Prezado(a) Sr.(a) " . $username . "<br /><br />
                        Obrigado pelo seu registo no SMS illico, a nossa plataforma de envio de SMS através da web.<br />
                        O seu registo foi registado e será validado no prazo máximo de 48 horas após a verificação das informações fornecidas.<br />
                        Receberá um SMS e um e-mail após a ativação da sua conta.<br /><br />";
                    }
                    $message = "<div style='color:#156082;font-size:11pt;line-height:1.5em;font-family:Century Gothic'>
                    " . $content
                    . "<hr style='color:#156082;'>"
                    . __('message.bestregard')
                    . env('MAIL_SIGNATURE')
                    . "<hr style='color:#156082;'></div>";
                    // Envoi de l'email
                    $this->sendMail($email, env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'), env('MAIL_CC'), $subject, $message);
                    // Retourner les données de l'utilisateur
                    $data = [
                        'lastname' => $request->lastname,
                        'firstname' => $request->firstname,
                        'number' => $request->number,
                        'email' => $email,
                    ];
                    return $this->sendSuccess(__('message.usersucc'), $data, 201);
                } catch (\Exception $e) {
                    DB::rollBack(); // Annuler la transaction en cas d'erreur
                    Log::warning("User::store - Erreur : " . $e->getMessage() . " " . json_encode($set));
                    return $this->sendError(__('message.error'));
                }
            } else {
                Log::warning("User::store - Recaptcha : " . json_encode($resultJson));
                return $this->sendError(__('message.recaptcha'));
            }
        } catch (\Exception $e) {
            Log::warning("User::store - Recaptcha : " . $e->getMessage() . "  " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    //Modification
    /**
    * @OA\Post(
    *   path="/api/users/profiles",
    *   tags={"Users"},
    *   operationId="profilUser",
    *   description="Modification du profil utilisateur",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *             required={"lastname", "firstname", "number", "email", "town_id", "accountyp_id"},
    *             @OA\Property(property="lastname", type="string"),
    *             @OA\Property(property="firstname", type="string"),
    *             @OA\Property(property="number", type="string"),
    *             @OA\Property(property="email", type="string"),
    *             @OA\Property(property="town_id", type="integer"),
    *             @OA\Property(property="accountyp_id", type="integer"),
    *             @OA\Property(property="company", type="string"),
    *             @OA\Property(property="nif", type="string"),
    *             @OA\Property(property="address", type="string"),
    *             @OA\Property(property="website", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=200, description="Profil utilisateur modifié avec succès."),
    *   @OA\Response(response=400, description="Bad Request."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function profiles(Request $request): JsonResponse {
        // User
        $authUser = Auth::user();
        App::setLocale($authUser->lg);
        // Data
        Log::notice("User::profiles - ID User : {$authUser->id} - Requête : " . json_encode($request->all()));
        // Validator
        $validator = Validator::make($request->all(), [
            'lastname' => 'required',
            'firstname' => 'required',
            'number' => 'required|unique:users,number,' . $authUser->id . ',id',
            'email'  => 'required|email|unique:users,email,' . $authUser->id . ',id',
            'town_id' => 'required|integer|min:1',
            'accountyp_id' => 'required|integer|min:1',
        ]);
        //Error field
        if ($validator->fails()) {
            Log::warning("User::profiles - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
            return $this->sendSuccess(__('message.fielderr'), $validator->errors(), 422);
        }
        // Test sur DID
        if ($request->accountyp_id != 1) {
            // Validator
            $validator = Validator::make($request->all(), [
                'company' => 'required',
                'nif' => 'required',
                'address' => 'required',
                'website' => 'required',
            ]);
            // Error field
            if ($validator->fails()) {
                Log::warning("User::profiles - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
                return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
            }
        }
        // Formatage du nom et prénoms
        $email = Str::lower($request->email);
        // Formatage des données
        $set = [
            'lastname' => $request->lastname,
            'firstname' => $request->firstname,
            'number' => $request->number,
            'email' => $email,
            'town_id' => $request->town_id,
            'accountyp_id' => $request->accountyp_id,
            'company' => $request->company ?? '',
            'nif' => $request->nif ?? '',
            'address' => $request->address ?? '',
            'website' => $request->website ?? '',
        ];
        try {
            DB::beginTransaction(); // Démarrer une transaction
            // Création de l'utilisateur
            User::findOrFail($authUser->id)->update($set);
            DB::commit(); // Valider la transaction
            return $this->sendSuccess(__('message.profilsucc'), $set, 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("User::profiles - Erreur : " . $e->getMessage() . " " . json_encode($set));
            return $this->sendError(__('message.error'));
        }
	}
    //Photo de profil
    /**
     * @OA\Put(
     *   path="/api/users/avatars",
     *   tags={"Users"},
     *   operationId="avatars",
     *   description="Modification de la photo de profil",
     *   security={{"bearer":{}}},
     *   @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *             required={"avatar"},
     *             @OA\Property(property="avatar", type="string", format="binary"),
     *          )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Photo de profil modifiée avec succès."),
     *   @OA\Response(response=401, description="Non autorisé."),
     *   @OA\Response(response=404, description="Page introuvable."),
     * )
     */
    public function avatars(Request $request)
    {
        // User
        $authUser = Auth::user();
        App::setLocale($authUser->lg);
        // Validator
        $validator = Validator::make($request->all(), [
			'avatar' => 'required|file|mimes:png,jpeg,jpg|max:2048',
        ]);
        // Error field
        if ($validator->fails()) {
            Log::warning("User::avatars - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
            return $this->sendSuccess(__('message.fielderr'), $validator->errors(), 422);
        }
        // Upload photo
        $dir = 'assets/avatars';
        $image = $request->file('avatar');
        $ext = $image->getClientOriginalExtension();
        $avatar = User::filenameUnique($ext);
        if (!($image->move($dir, $avatar))) {
            Log::warning("User::avatars - Erreur : " . $e->getMessage());
            return $this->sendError(__('message.photodown'));
        }
        try {
            $set = [
                'avatar' => $avatar,
                'avatar_at' => now(),
            ];
            User::findOrFail($authUser->id)->update($set);
            $data = [
                'avatar' => env('APP_URL') . '/assets/avatars/' . $avatar,
            ];
            return $this->sendSuccess(__('message.photosucc'), $data, 201);
        } catch(\Exception $e) {
            Log::warning("User::avatars - Erreur : " . $e->getMessage());
            return $this->sendError(__('message.error'));
        }
    }
    //Déconnexion
    /**
    * @OA\Post(
    *   path="/api/users/logout",
    *   tags={"Users"},
    *   operationId="logout",
    *   description="Deconnecte l'utilisateur en supprimant son token d'accès",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Déconnexion éffectuée avec succès."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function logout(Request $request)
    {
        try {
            $request->user()->token()->revoke();
            return $this->sendSuccess(__('message.logoutsucc'));
        } catch (\Exception $e) {
            Log::error("Logout error: " . $e->getMessage());
            return $this->sendError(__('message.logouterr'));
        }
    }
}