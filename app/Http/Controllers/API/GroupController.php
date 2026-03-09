<?php

namespace App\Http\Controllers\API;

use Excel;
use \Carbon\Carbon;
use App\Imports\ContactImport;
use Illuminate\Validation\Rule;
use Illuminate\Http\{Request, JsonResponse};
use App\Models\{Contact, Group, GroupContact, Prefix};
use Illuminate\Support\Facades\{App, Auth, DB, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class GroupController extends BaseController
{
    //Liste des groupes
    /**
    * @OA\Get(
    *   path="/api/groups",
    *   tags={"Groups"},
    *   operationId="listGroup",
    *   description="Liste des groupes",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Liste des groupes."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function index(): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Code to list groupes
            $groupes = Group::select('id', 'uid', 'label')
            ->where('user_id', Auth::user()->id)
            ->orderBy('label')
            ->get();
            // Vérifier si les données existent
            if ($groupes->isEmpty()) {
                Log::warning("Group::index - Aucun Groupe trouvé.");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Transformer les données
            $data = $groupes->map(fn($data) => [
                'uid' => $data->uid,
                'label' => $data->label,
                'total' => GroupContact::where('group_id', $data->id)->where('blacklist', 0)->count(),
            ]);
            return $this->sendSuccess(__('message.listgroup'), $data);
        } catch (\Exception $e) {
            Log::warning("Group::index - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    // Détail d'un Groupe
    /**
    * @OA\Get(
    *   path="/api/groups/{uid}",
    *   tags={"Groups"},
    *   operationId="showGroup",
    *   description="Détail d'un Groupe",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Détail d'un Groupe."),
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
            $groups = Group::where('uid', $uid)->first();
            if (!$groups) {
                Log::warning("Group::show - Aucun contact trouvé pour l'UID : {$uid}");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Data to save
            $data = [
                'label' => $groups->label,
            ];
            return $this->sendSuccess(__('message.detgroup'), $data);
        } catch (\Exception $e) {
            Log::warning("Group::show - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    //Enregistrement
    /**
    * @OA\Post(
    *   path="/api/groups",
    *   tags={"Groups"},
    *   operationId="storeGroup",
    *   description="Enregistrement d'un Group",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"label"},
    *         @OA\Property(property="label", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Groupe enregisté avec succès."),
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
                Rule::unique('groups')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
        ]);
        //Error field
        if ($validator->fails()) {
            Log::warning("Group::store - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Data to save
        $set = [
            'label' => $request->label,
            'user_id' => Auth::user()->id,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            Group::create($set);
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.addgroup'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Group::store - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    // Modification
    /**
    * @OA\Put(
    *   path="/api/groups/{uid}",
    *   tags={"Groups"},
    *   operationId="editGroup",
    *   description="Modification d'un Group",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"label"},
    *         @OA\Property(property="label", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Groupe modifié avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function update(request $request, string $uid): JsonResponse {
        // Language
        $user = Auth::user();
        App::setLocale($user->lg);
        //Validator
        $validator = Validator::make($request->all(), [
            'label' => [
                'required',
                Rule::unique('groups')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
        ]);
        //Error field
        if($validator->fails()){
            Log::warning("Group::update - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        // Vérifier si l'ID est présent et valide
        $group = Group::where('uid', $uid)->first();
        if (!$group) {
            Log::warning("Group::update - Aucun Groupe trouvé pour l'ID : {$uid}");
            return $this->sendSuccess(__('message.nodata'));
        }
        // Data to save
        $set = [
            'label' => $request->label,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $group->update($set);
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.editgroup'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Group::update - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
	}
    // Suppression d'un Groupe
    /**
    *   @OA\Delete(
    *   path="/api/groups/{uid}",
    *   tags={"Groups"},
    *   operationId="deleteGroup",
    *   description="Suppression d'un Group",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=201, description="Groupe supprimé avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function destroy(string $uid): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Vérification si le Groupe est attribué à une demande
            $group = Group::where('uid', $uid)->first();
            // Suppression
            $deleted = Group::destroy($group->id);
            if (!$deleted) {
                Log::warning("Group::destroy - Tentative de suppression d'un Groupe inexistante : {$uid}");
                return $this->sendError(__('message.error'), [], 403);
            }
            $find = GroupContact::where('group_id', $group->id)->first();
            if ($find) {
                GroupContact::where('group_id', $group->id)->delete();
            }
            return $this->sendSuccess(__('message.delgroup'), [], 201);
        } catch(\Exception $e) {
            Log::warning("Group::destroy - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    // Liste des contacts d'un groupe
    /**
    * @OA\Get(
    *   path="/api/groups/contactlist/{uid}?num=1&limit=10&search=''",
    *   tags={"Groups"},
    *   operationId="listContactGroup",
    *   description="Liste des contacts d'un groupe",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Liste des contacts d'un groupe."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function contactlist(Request $request, string $uid): JsonResponse
    {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            $num = isset($request->num) ? (int) $request->num:1;
            $limit = isset($request->limit) ? (int) $request->limit:10;
            $search = isset($request->search) ? (int) $request->search:'';
            $groupContact = GroupContact::select('contacts.uid', 'contacts.label', 'number', 'gender', 'date_at', 'field1', 'field2', 'field3')
            ->join('contacts', 'group_contact.contact_id', '=', 'contacts.id')
            ->join('groups', 'group_contact.group_id', '=', 'groups.id')
            ->when(($search != ''), fn($q) => $q->where('label', 'LIKE', '%' . $search . '%'))
            ->where('groups.user_id', Auth::user()->id)
            ->where('group_contact.blacklist', 0)
            ->where('contacts.blacklist', 0)
            ->where('groups.uid', $uid)
            ->where('publipostage', 0)
            ->orderBy('label')
            ->paginate($limit, ['*'], 'page', $num);

            if ($groupContact->isEmpty()) {
                Log::warning("Group::show - Aucun contact trouvé pour l'UID : {$uid}");
                return $this->sendSuccess(__('message.nodata'));
            };
            // Transformer les données
            $data = $groupContact->map(fn($data) => [
                'uid' => $data->uid,
                'label' => $data->label,
                'number' => $data->number,
                'gender' => $data->gender ?? '',
                'date_at' => $data->date_at != null ? Carbon::parse($data->date_at)->format('d/m/Y'):'',
                'field1' => $data->field1 ?? '',
                'field2' => $data->field2 ?? '',
                'field3' => $data->field3 ?? '',
            ]);
            return $this->sendSuccess(__('message.listcontact'), [
                'lists' => $data,
                'current_page' => $groupContact->currentPage(),
                'last_page' => $groupContact->lastPage(),
                'total'  => $groupContact->total(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Group::show - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    // Ajout d'un Contact dans un Groupe
    /**
    *   @OA\Post(
    *   path="/api/groups/contact/{uid}",
    *   tags={"Groups"},
    *   operationId="ContactGroup",
    *   description="Ajout d'un Contact dans un Groupe",
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
    *   @OA\Response(response=201, description="Ajout d'un Contact dans un Groupe."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function contact(Request $request, string $uid): JsonResponse
    {
        $user = Auth::user();
        App::setLocale($user->lg);
        // Validation
        $validator = Validator::make($request->all(), [
            'label' => 'required',
            'number' => [
                'required',
                'digits:9',
                'numeric',
                Rule::unique('contacts')->where(fn($query) =>
                    $query->where('user_id', $user->id)
                        ->where('publipostage', 0)
                ),
            ],
            'gender' => 'present',
            'date_at' => 'nullable|date_format:Y-m-d',
            'field1' => 'present',
            'field2' => 'present',
            'field3' => 'present',
        ]);
        // Error field
        if($validator->fails()){
            Log::warning(
                "Group::contact - Validator : {$validator->errors()->first()} - " .
                json_encode($request->all())
            );
            return $this->sendError( __('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Vérifier groupe
        $group = Group::where('uid', $uid)->first();
        if (!$group) {
            Log::warning("Group::contact - Aucun Groupe trouvé pour l'ID : {$uid}");
            return $this->sendSuccess(__('message.nodata'));
        }
        // Vérifier préfixe
        $prefix = substr($request->number, 0, 2);
        if (!Prefix::where('label', $prefix)->exists()) {
            Log::warning("Group::contact - Validator number : " . json_encode($request->all()));
            return $this->sendError(__('message.numbernot'), [], 422);
        }
        // Data to save
        $data = [
            'user_id' => $user->id,
            'label'   => $request->label,
            'number'  => $request->number,
            'gender'  => $request->gender,
            'date_at' => $request->date_at,
            'field1'  => $request->field1,
            'field2'  => $request->field2,
            'field3'  => $request->field3,
        ];
        try {
            DB::transaction(function () use ($data, $group) {
                $contact = Contact::create($data);
                GroupContact::insertOrIgnore([
                    'group_id'   => $group->id,
                    'contact_id' => $contact->id,
                ]);
            });
            return $this->sendSuccess(__('message.addcontact'), [], 201);
        } catch (\Throwable $e) {
            Log::warning("Group::contact - Erreur : {$e->getMessage()} " . json_encode($data));
            return $this->sendError(__('message.error'));
        }
    }
    // Ajout/Suppression de Contact dans un Groupe
    /**
    *   @OA\Post(
    *   path="/api/groups/contacts/{uid}",
    *   tags={"Groups"},
    *   operationId="addGroup",
    *   description="Ajout/Suppression de un ou de plusieurs Contacts dans un Groupe",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"status", "contacts"},
    *         @OA\Property(property="status", type="integer"),
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
    public function contacts(Request $request, string $uid): JsonResponse
    {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'status'     => 'required|in:0,1',
            'contacts' => 'required|array',
            'contacts.*' => 'required|uuid',
        ]);
        // Error field
        if($validator->fails()){
            Log::warning("Group::contacts - Validator : {$validator->errors()->first()} - " . json_encode($request->all())
            );
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        $group = Group::where('uid', $uid)->first();
        if (!$group) {
            Log::warning("Group::contacts - Aucun Groupe trouvé pour l'ID : {$uid}");
            return $this->sendSuccess(__('message.nodata'));
        }
        try {
            DB::beginTransaction();
            // Récupérer tous les contacts en une seule requête
            $contacts = Contact::whereIn('uid', $request->contacts)
                ->where('user_id', Auth::user()->id)
                ->where('publipostage', 0)
                ->pluck('id');
            if ($contacts->isEmpty()) {
                DB::rollBack();
                Log::warning("Group::contacts - Aucun Contacts trouvés : " . json_encode($request->contacts));
                return $this->sendSuccess(__('message.nodata'));
            }
            if ($request->status == 1) {
                $msg = __('message.addcontact');
                // Préparer les insertions en masse
                $data = $contacts->map(function ($contactId) use ($group) {
                    return [
                        'group_id'   => $group->id,
                        'contact_id' => $contactId,
                    ];
                })->toArray();
                GroupContact::insertOrIgnore($data);
                DB::commit();
            } else {
                $msg = __('message.delcontact');
                GroupContact::where('group_id', $group->id)
                    ->whereIn('contact_id', $contacts)
                    ->delete();
                DB::commit();
            }
            return $this->sendSuccess($msg, [], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::warning("Group::contacts - Erreur : {$e->getMessage()} " . json_encode($request->all())
            );
            return $this->sendError(__('message.error'));
        }
    }
    //Importation
    /**
    * @OA\Post(
    *   path="/api/groups/imports/{uid}",
    *   tags={"Groups"},
    *   operationId="importGroup",
    *   description="Importation d'un Contact",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\MediaType(
    *          mediaType="multipart/form-data",
    *          @OA\Schema(
    *             required={"files"},
    *             @OA\Property(property="files", type="string", format="binary"),
    *          )
    *      )
    *   ),
    *   @OA\Response(response=201, description="Contact enregisté avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function imports(Request $request, string $uid): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'files' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);
        // Error field
        if ($validator->fails()) {
            Log::warning("Group::imports - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Vérifier si l'ID est présent et valide
        $group = Group::where('uid', $uid)->first();
        if (!$group) {
            Log::warning("Group::imports - Aucun Groupe trouvé pour l'ID : {$uid}");
            return $this->sendSuccess(__('message.nodata'));
        }
        $import = new ContactImport(Auth::user(), 0);
        try {
            Excel::import($import, $request->file('files'));
            // Récupérer les IDs des contacts importés
            $contactIds = $import->getContactIds();
            // Test de sécurité pour éviter les insertions massives non filtrées
            if (!empty($contactIds)) {
                $data = collect($contactIds)->map(function ($contact_id) use ($group) {
                    return [
                        'group_id'   => $group->id,
                        'contact_id' => $contact_id,
                    ];
                })->toArray();
                GroupContact::insertOrIgnore($data);
            }
            return $this->sendSuccess(__('message.impcontact'), [
                'imported' => $import->getImportedCount(),
                'total' => $import->getTotalRows(),
                'errors' => $import->getErrors(),
            ], 201);
        } catch (\Exception $e) {
            Log::warning("Group::imports - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.fielderr'), [], 400);
        }
    }
    // Ajout/Exclusion de Contact dans un Groupe
    /**
    *   @OA\Post(
    *   path="/api/groups/blacklist/{uid}",
    *   tags={"Groups"},
    *   operationId="addExclu",
    *   description="Ajout/Exclusion de ou de plusieurs Contacts dans un Groupe",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"status", "contacts"},
    *         @OA\Property(property="status", type="integer"),
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
    public function blacklist(request $request, string $uid): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'status'     => 'required|in:0,1',
            'contacts'   => 'required|array',
            'contacts.*' => 'required|string'
        ]);
        // Error field
        if($validator->fails()){
            Log::warning("Group::blacklist - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        // Vérifier si l'ID est présent et valide
        $group = Group::where('uid', $uid)->first();
        if (!$group) {
            Log::warning("Group::blacklist - Aucun Groupe trouvé pour l'ID : {$uid}");
            return $this->sendSuccess(__('message.nodata'));
        }
        try {
            DB::beginTransaction();
            // Récupérer tous les contacts en une seule requête
            $contactIds = Contact::whereIn('uid', $request->contacts)
                ->where('user_id', Auth::user()->id)
                ->where('publipostage', 0)
                ->pluck('id');
            if ($contactIds->isEmpty()) {
                DB::rollBack();
                Log::warning("Group::blacklist - Aucun Contacts trouvés : " . json_encode($request->contacts));
                return $this->sendSuccess(__('message.nodata'));
            }
            // Update en masse sur le pivot
            GroupContact::where('group_id', $group->id)
                ->whereIn('contact_id', $contactIds)
                ->update(['blacklist' => $request->status]);
            DB::commit();
            $message = $request->status == 1 ? __('message.addcontact') : __('message.delcontact');
            return $this->sendSuccess($message, [], 201);
        } catch(\Exception $e) {
            DB::rollBack();
            Log::warning("Group::blacklist - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
}
