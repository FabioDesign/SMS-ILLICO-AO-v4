<?php

namespace App\Http\Controllers\API;

use Illuminate\Validation\Rule;
use Illuminate\Http\{Request, JsonResponse};
use App\Models\{Contact, Group, GroupContact};
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
    public function index(Request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Code to list groupes
            $groupes = Group::select('uid', 'label')
            ->where('user_id', Auth::user()->id)
            ->orderByDesc('created_at')
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
            ]);
            return $this->sendSuccess(__('message.listgroup'), $data);
        } catch (\Exception $e) {
            Log::warning("Group::index - Erreur : " . $e->getMessage());
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
            Log::warning("Group::store - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Création de la reclamation
        $set = [
            'user_id' => Auth::user()->id,
            'label' => $request->label,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            Group::create($set);
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.addgroup'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Group::store - Erreur : " . $e->getMessage() . " " . json_encode($set));
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
    public function update(request $request, $uid): JsonResponse {
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
            Log::warning("Group::update - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        // Vérifier si l'ID est présent et valide
        $group = Group::where('uid', $uid)->first();
        if (!$group) {
            Log::warning("Group::update - Aucun Groupe trouvé pour l'ID : " . $uid);
            return $this->sendSuccess(__('message.nodata'));
        }
        // Création de la reclamation
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
            Log::warning("Group::update - Erreur : " . $e->getMessage() . " " . json_encode($set));
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
    public function destroy($uid): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            // Vérification si le Groupe est attribué à une demande
            $group = Group::where('uid', $uid)->first();
            // Suppression
            $deleted = Group::destroy($group->id);
            if (!$deleted) {
                Log::warning("Group::destroy - Tentative de suppression d'un Groupe inexistante : " . $uid);
                return $this->sendError(__('message.error'), [], 403);
            }
            $find = GroupContact::where('group_id', $group->id)->first();
            if ($find) {
                GroupContact::where('group_id', $group->id)->delete();
            }
            return $this->sendSuccess(__('message.delgroup'), [], 201);
        } catch(\Exception $e) {
            Log::warning("Group::destroy - Erreur : " . $e->getMessage() . " " . $uid);
            return $this->sendError(__('message.error'));
        }
    }
    // Ajout de Contact dans un Groupe
    /**
    *   @OA\Post(
    *   path="/api/groups/add/{uid}",
    *   tags={"Groups"},
    *   operationId="addGroup",
    *   description="Ajout de ou de plusieurs Contacts dans un Groupe",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"contacts"},
    *         @OA\Property(property="contacts", type="array", @OA\Items(
    *               example="['910102034', '920102034', '930102034']"
    *           )
    *         ),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Contacts supprimés avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function addcontact(Request $request, string $uid): JsonResponse
    {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'contacts' => 'required|array',
            'contacts.*' => 'required|integer'
        ]);
        if ($validator->fails()) {
            Log::warning("Group::addcontact - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all())
            );
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }

        $group = Group::where('uid', $uid)->first();
        if (!$group) {
            Log::warning("Group::addcontact - Aucun Groupe trouvé pour l'ID : " . $uid);
            return $this->sendSuccess(__('message.nodata'));
        }

        try {
            DB::beginTransaction();
            // Récupérer tous les contacts en une seule requête
            $contacts = Contact::whereIn('number', $request->contacts)
                ->where('user_id', Auth::user()->id)
                ->where('publipostage', 0)
                ->pluck('id');

            if ($contacts->isEmpty()) {
                DB::rollBack();
                Log::warning("Group::addcontact - Aucun Contacts trouvés : " . json_encode($request->contacts));
                return $this->sendSuccess(__('message.nodata'));
            }

            // Préparer les insertions en masse
            $data = $contacts->map(function ($contactId) use ($group) {
                return [
                    'group_id'   => $group->id,
                    'contact_id' => $contactId,
                ];
            })->toArray();
            GroupContact::insertOrIgnore($data);
            DB::commit();
            return $this->sendSuccess(__('message.addcontact'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::warning("Group::addcontact - Erreur : " . $e->getMessage() . " " . json_encode($request->all())
            );
            return $this->sendError(__('message.error'));
        }
    }
    // Suppression d'un contact d'un Groupe
    /**
    *   @OA\Delete(
    *   path="/api/groups/del/{uid}",
    *   tags={"Groups"},
    *   operationId="delGroup",
    *   description="Suppression d'un contact d'un Group",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"contacts"},
    *         @OA\Property(property="contacts", type="array", @OA\Items(
    *               example="['910102034', '920102034', '930102034']"
    *           )
    *         ),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Contact retiré avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function delcontact(Request $request, string $uid): JsonResponse
    {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator        
        $validator = Validator::make($request->all(), [
            'contacts' => 'required|array',
            'contacts.*' => 'required|integer'
        ]);
        if ($validator->fails()) {
            Log::warning("Group::addcontact - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all())
            );
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }

        $group = Group::where('uid', $uid)->first();
        if (!$group) {
            Log::warning("Group::delcontact - Aucun Groupe trouvé pour l'ID : " . $uid);
            return $this->sendSuccess(__('message.nodata'));
        }

        try {
            DB::beginTransaction();
            // Récupérer tous les contacts en une seule requête
            $contactIds = Contact::whereIn('number', $request->contacts)
                ->where('user_id', Auth::user()->id)
                ->where('publipostage', 0)
                ->pluck('id');
            if ($contactIds->isEmpty()) {
                DB::rollBack();
                Log::warning("Group::addcontact - Aucun Contacts trouvés : " . json_encode($request->contacts));
                return $this->sendSuccess(__('message.nodata'));
            }

            // Suppression en masse sécurisée (IMPORTANT : filtrer par group_id)
            GroupContact::where('group_id', $group->id)
                ->whereIn('contact_id', $contactIds)
                ->delete();
            DB::commit();
            return $this->sendSuccess(__('message.delgroup'), [], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::warning("Group::delcontact - Erreur : " . $e->getMessage() . " " . json_encode($request->all())
            );
            return $this->sendError(__('message.error'));
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
    *               example="['910102034', '920102034', '930102034']"
    *           )
    *         ),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Contacts supprimés avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function blacklist(request $request, $uid): JsonResponse {
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
            Log::warning("Group::blacklist - Validator : " . $validator->errors()->first() . " - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        // Vérifier si l'ID est présent et valide
        $group = Group::where('uid', $uid)->first();
        if (!$group) {
            Log::warning("Group::blacklist - Aucun Groupe trouvé pour l'ID : " . $uid);
            return $this->sendSuccess(__('message.nodata'));
        }
        try {
            DB::beginTransaction();
            // Récupérer tous les contacts en une seule requête
            $contactIds = Contact::whereIn('number', $request->contacts)
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
                ->update(['blacklist'  => $request->status]);
            DB::commit();
            return $this->sendSuccess(__('message.addcontact'), [], 201);
        } catch(\Exception $e) {
            DB::rollBack();
            Log::warning("Group::blacklist - Erreur : " . $e->getMessage() . " " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
}
