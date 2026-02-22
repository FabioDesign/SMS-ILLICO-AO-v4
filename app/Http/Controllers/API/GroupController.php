<?php

namespace App\Http\Controllers\API;

use \Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\{Group, GroupContact};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{App, Auth, DB, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class GroupController extends BaseController
{
    //Liste des groupes
    /**
    * @OA\Get(
    *   path="/api/groups?num=1&limit=10&search=''",
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
        //User
        $user = Auth::user();
		App::setLocale($user->lg);
        try {
            $num = isset($request->num) ? (int) $request->num:1;
            $limit = isset($request->limit) ? (int) $request->limit:10;
            $search = isset($request->search) ? (int) $request->search:'';
            // Code to list groupes
            $query = Group::select('uid', 'label');
            if ($search) $query->where('label', 'LIKE', '%'.$search.'%');
            $query->orderByDesc('created_at')
            ->get();
            $total = $query->count();
            $groupes = $query->paginate($limit, ['*'], 'page', $num);
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
            Log::warning("Group::index - Erreur d'affichage de groupes: " . $e->getMessage());
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
        //User
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
            Log::warning("Group::store - Validator : " . $validator->errors()->first() . " - ".json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Création de la reclamation
        $set = [
            'user_id' => $user->id,
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
            Log::warning("Group::store : " . $e->getMessage() . " " . json_encode($set));
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
        //User
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
            Log::warning("Group::update - Validator : " . $validator->errors()->first() . " - ".json_encode($request->all()));
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
            Log::warning("Group::update : " . $e->getMessage() . " " . json_encode($set));
            return $this->sendError(__('message.error'));
        }
	}
    // Retirer un contact d'un Groupe
    /**
    *   @OA\Delete(
    *   path="/api/groups/delgroup",
    *   tags={"Groups"},
    *   operationId="delGroup",
    *   description="Retirer un contact d'un Group",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=201, description="Contact retiré avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function delgroup($uid): JsonResponse {
        //User
        $user = Auth::user();
		App::setLocale($user->lg);
        try {
            // Vérification si le Contact est attribué à une demande
            $contact = Contact::where('uid', $uid)->first();
            // Suppression
            GroupContact::where('contact_id', $contact->id)->delete();
            if (!$deleted) {
                Log::warning("Group::delgroup - Tentative de suppression d'un Groupe inexistante : " . $uid);
                return $this->sendError(__('message.error'), [], 403);
            }
            return $this->sendSuccess(__('message.delgroup'), [], 201);
        } catch(\Exception $e) {
            Log::warning("Group::destroy - Erreur lors de la suppression d'un Groupe : " . $e->getMessage());
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
        //User
        $user = Auth::user();
		App::setLocale($user->lg);
        try {
            // Vérification si le Groupe est attribué à une demande
            $group = Group::where('uid', $uid)->first();
            // Suppression
            $deleted = Group::destroy($group->id);
            if (!$deleted) {
                Log::warning("Group::destroy - Tentative de suppression d'un Groupe inexistante : " . $uid);
                return $this->sendError(__('message.error'), [], 403);
            }
            GroupGroup::where('group_id', $group->id)->delete();
            return $this->sendSuccess(__('message.delgroup'), [], 201);
        } catch(\Exception $e) {
            Log::warning("Group::destroy - Erreur lors de la suppression d'un Groupe : " . $e->getMessage());
            return $this->sendError(__('message.error'));
        }
    }
}
