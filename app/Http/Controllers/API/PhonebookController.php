<?php

namespace App\Http\Controllers\API;

use Excel;
use \Carbon\Carbon;
use Illuminate\Support\Str;
use App\Imports\ContactImport;
use Illuminate\Validation\Rule;
use Illuminate\Http\{Request, JsonResponse};
use App\Models\{Contact, GroupContact, Prefix};
use Illuminate\Support\Facades\{App, Auth, DB, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class PhonebookController extends BaseController
{
    //Liste des contacts
    /**
    * @OA\Get(
    *   path="/api/phonebooks?num=1&limit=10&search=''",
    *   tags={"Phonebooks"},
    *   operationId="listContact",
    *   description="Liste des contacts",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Liste des contacts."),
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
            // Code to list contacts
            $query = Contact::select('uid', 'label', 'number', 'gender', 'date_at', 'field1', 'field2', 'field3');
            if ($search) $query->where('label', 'LIKE', '%'.$search.'%');
            $query->where('status', 0)
            ->where('blacklist', 0)
            ->where('publipostage', 0)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
            $total = $query->count();
            $contacts = $query->paginate($limit, ['*'], 'page', $num);
            // Vérifier si les données existent
            if ($contacts->isEmpty()) {
                Log::warning("Contact::index - Aucun Contact trouvé.");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Transformer les données
            $data = $contacts->map(fn($data) => [
                'uid' => $data->uid,
                'label' => $data->label,
                'number' => $data->number,
                'gender' => $data->gender,
                'date_at' => Carbon::parse($data->date_at)->format('d/m/Y'),
                'field1' => $data->field1,
                'field2' => $data->field2,
                'field3' => $data->field3,
            ]);
            return $this->sendSuccess(__('message.listcontact'), $data);
        } catch (\Exception $e) {
            Log::warning("Contact::index - Erreur d'affichage de Contacts: " . $e->getMessage());
            return $this->sendError(__('message.error'));
        }
    }
    //Enregistrement
    /**
    * @OA\Post(
    *   path="/api/phonebooks",
    *   tags={"Phonebooks"},
    *   operationId="storeContact",
    *   description="Enregistrement d'un Contact",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"label", "number"},
    *         @OA\Property(property="label", type="string"),
    *         @OA\Property(property="number", type="integer"),
    *         @OA\Property(property="gender", type="string"),
    *         @OA\Property(property="date_at", type="date"),
    *         @OA\Property(property="field1", type="string"),
    *         @OA\Property(property="field2", type="string"),
    *         @OA\Property(property="field3", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Contact enregisté avec succès."),
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
            'label' => 'required',
            'number' => [
                'required',
                'digits:9',
                'numeric',
                Rule::unique('contacts')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id)->where('publipostage', 0)->where('status', 0);
                }),
            ],
            'gender' => 'present',
            'date_at' => 'nullable|date|date_format:Y-m-d',
            'field1' => 'present',
            'field2' => 'present',
            'field3' => 'present',
        ]);
        //Error field
        if ($validator->fails()) {
            Log::warning("Contact::store - Validator : " . $validator->errors()->first() . " - ".json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Vérifier du préfixe téléphonique
        $prefix = Prefix::where('label', substr($request->number, 0, 2))->first();
        if (!$prefix) {
            Log::warning("Contact::store - Validator number : ".json_encode($request->all()));
            return $this->sendError(__('message.numbernot'), [], 422);
        }
        // Création de la reclamation
        $set = [
            'user_id' => $user->id,
            'label' => $request->label,
            'number' => $request->number,
            'gender' => $request->gender,
            'date_at' => $request->date_at,
            'field1' => $request->field1,
            'field2' => $request->field2,
            'field3' => $request->field3,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            Contact::create($set);
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.addcontact'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Contact::store : " . $e->getMessage() . " " . json_encode($set));
            return $this->sendError(__('message.error'));
        }
    }
    // Modification
    /**
    * @OA\Put(
    *   path="/api/phonebooks/{uid}",
    *   tags={"Phonebooks"},
    *   operationId="editContact",
    *   description="Modification d'un Contact",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"label", "number"},
    *         @OA\Property(property="label", type="string"),
    *         @OA\Property(property="number", type="integer"),
    *         @OA\Property(property="gender", type="string"),
    *         @OA\Property(property="date_at", type="date"),
    *         @OA\Property(property="field1", type="string"),
    *         @OA\Property(property="field2", type="string"),
    *         @OA\Property(property="field3", type="string"),
    *      )
    *   ),
    *   @OA\Response(response=201, description="Contact modifié avec succès."),
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
            'label' => 'required',
            'number' => [
                'required',
                'digits:9',
                'numeric',
                Rule::unique('contacts')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id)->where('publipostage', 0)->where('status', 0);
                }),
            ],
            'gender' => 'present',
            'date_at' => 'nullable|date|date_format:Y-m-d',
            'field1' => 'present',
            'field2' => 'present',
            'field3' => 'present',
        ]);
        //Error field
        if ($validator->fails()) {
            Log::warning("Contact::update - Validator : " . $validator->errors()->first() . " - ".json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        // Vérifier du préfixe téléphonique
        $prefix = Prefix::where('label', substr($request->number, 0, 2))->first();
        if (!$prefix) {
            Log::warning("Contact::update - Validator number : ".json_encode($request->all()));
            return $this->sendError(__('message.numbernot'), [], 422);
        }
        // Vérifier si l'ID est présent et valide
        $contact = Contact::where('uid', $uid)->first();
        if (!$contact) {
            Log::warning("Contact::update - Aucun Contact trouvé pour l'ID : " . $uid);
            return $this->sendSuccess(__('message.nodata'));
        }
        // Création de la reclamation
        $set = [
            'label' => $request->label,
            'number' => $request->number,
            'gender' => $request->gender,
            'date_at' => $request->date_at,
            'field1' => $request->field1,
            'field2' => $request->field2,
            'field3' => $request->field3,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $contact->update($set);
            // Valider la transaction
            DB::commit();
            return $this->sendSuccess(__('message.editcontact'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Contact::update : " . $e->getMessage() . " " . json_encode($set));
            return $this->sendError(__('message.error'));
        }
	}
    // Suppression d'un Contact
    /**
    *   @OA\Delete(
    *   path="/api/phonebooks/{uid}",
    *   tags={"Phonebooks"},
    *   operationId="deleteContact",
    *   description="Suppression d'un Contact",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=201, description="Contact supprimé avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function destroy($uid): JsonResponse {
        //User
        $user = Auth::user();
		App::setLocale($user->lg);
        try {
            // Vérification si le Contact est attribué à une demande
            $contact = Contact::where('uid', $uid)->first();
            // Suppression
            $deleted = Contact::destroy($contact->id);
            if (!$deleted) {
                Log::warning("Contact::destroy - Tentative de suppression d'un Contact inexistante : " . $uid);
                return $this->sendError(__('message.error'), [], 403);
            }
            $find = GroupContact::where('contact_id', $contact->id)->first();
            if ($find) {
                GroupContact::where('contact_id', $contact->id)->delete();
            }
            return $this->sendSuccess(__('message.delcontact'), [], 201);
        } catch(\Exception $e) {
            Log::warning("Contact::destroy - Erreur lors de la suppression d'un Contact : " . $e->getMessage());
            return $this->sendError(__('message.error'));
        }
    }
    //Importation
    /**
    * @OA\Post(
    *   path="/api/phonebooks/imports",
    *   tags={"Phonebooks"},
    *   operationId="importContact",
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
    public function imports(Request $request): JsonResponse {
        //User
        $user = Auth::user();
		App::setLocale($user->lg);
        //Validator
        $validator = Validator::make($request->all(), [
            'files' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);
        //Error field
        if ($validator->fails()) {
            Log::warning("Contact::store - Validator : " . $validator->errors()->first() . " - ".json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        $import = new ContactImport($user);
    
        try {
            Excel::import($import, $request->file('files'));
            
            $errors = $import->getErrors();
            
            if (!empty($errors)) {
                return $this->sendError("Certaines lignes n'ont pas pu être importées", $errors, 422);
            }
            
            return $this->sendSuccess('Import réussi', [], 201);
        } catch (\Exception $e) {
            return $this->sendError("Erreur lors de l'import", $e->getMessage(), 400);
        }
    }
}
