<?php

namespace App\Http\Controllers\API;

use Excel;
use \Carbon\Carbon;
use App\Imports\ContactImport;
use Illuminate\Validation\Rule;
use App\Models\{Contact, Prefix};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{App, Auth, DB, Log, Validator};
use App\Http\Controllers\API\BaseController as BaseController;

class PublipostageController extends BaseController
{
    //Liste des contacts
    /**
    * @OA\Get(
    *   path="/api/publipostage?num=1&limit=10&search=''",
    *   tags={"Publipostage"},
    *   operationId="listPub",
    *   description="Liste des contacts",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Liste des contacts."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function index(Request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        try {
            $num = isset($request->num) ? (int) $request->num:1;
            $limit = isset($request->limit) ? (int) $request->limit:10;
            $search = isset($request->search) ? (int) $request->search:'';
            // Code to list contacts
            $contacts = Contact::select('uid', 'label', 'number', 'gender', 'date_at', 'field1', 'field2', 'field3')
            ->when(($search != ''), fn($q) => $q->where('label', 'LIKE', '%'.$search.'%'))
            ->where('user_id', Auth::user()->id)
            ->where('publipostage', 1)
            ->orderBy('label')
            ->paginate($limit, ['*'], 'page', $num);
            // Vérifier si les données existent
            if ($contacts->isEmpty()) {
                Log::warning("Publipostage::index - Aucun Contact trouvé.");
                return $this->sendSuccess(__('message.nodata'));
            }
            // Transformer les données
            $data = $contacts->map(fn($data) => [
                'uid' => $data->uid,
                'label' => $data->label,
                'number' => $data->number,
                'gender' => $data->gender,
                'date_at' => $data->date_at != null ? Carbon::parse($data->date_at)->format('d/m/Y'):'',
                'field1' => $data->field1 ?? '',
                'field2' => $data->field2 ?? '',
                'field3' => $data->field3 ?? '',
            ]);
            return $this->sendSuccess(__('message.listcontact'), [
                'lists' => $data,
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'total'  => $contacts->total(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Publipostage::index - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'));
        }
    }
    //Enregistrement
    /**
    * @OA\Post(
    *   path="/api/publipostage",
    *   tags={"Publipostage"},
    *   operationId="storePub",
    *   description="Enregistrement d'un Publipostage",
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
    *   @OA\Response(response=201, description="Contact enregisté avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function store(Request $request): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'label' => 'required',
            'number' => [
                'required',
                'digits:9',
                'numeric',
                Rule::unique('contacts')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id)->where('publipostage', 1);
                }),
            ],
            'gender' => 'required|in:M,F',
            'date_at' => 'nullable|date|date_format:Y-m-d',
            'field1' => 'present',
            'field2' => 'present',
            'field3' => 'present',
        ]);
        // Error field
        if ($validator->fails()) {
            Log::warning("Publipostage::store - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        // Vérifier préfixe
        $prefix = substr($request->number, 0, 2);
        if (!Prefix::where('label', $prefix)->exists()) {
            Log::warning("Publipostage::store - Validator number : " . json_encode($request->all()));
            return $this->sendError(__('message.numbernot'), [], 422);
        }
        // Data to save
        $set = [
            'publipostage' => 1,
            'user_id' => Auth::user()->id,
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
            Log::warning("Publipostage::store - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    // Modification
    /**
    * @OA\Put(
    *   path="/api/publipostage/{uid}",
    *   tags={"Publipostage"},
    *   operationId="editPub",
    *   description="Modification d'un Publipostage",
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
    *   @OA\Response(response=201, description="Contact modifié avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function update(request $request, $uid): JsonResponse {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'label' => 'required',
            'number' => [
                'required',
                'digits:9',
                'numeric',
                Rule::unique('contacts')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id)->where('publipostage', 1);
                }),
            ],
            'gender' => 'required|in:M,F',
            'date_at' => 'nullable|date|date_format:Y-m-d',
            'field1' => 'present',
            'field2' => 'present',
            'field3' => 'present',
        ]);
        // Error field
        if($validator->fails()){
            Log::warning("Publipostage::update - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        // Vérifier préfixe
        $prefix = substr($request->number, 0, 2);
        if (!Prefix::where('label', $prefix)->exists()) {
            Log::warning("Publipostage::update - Validator number : " . json_encode($request->all()));
            return $this->sendError(__('message.numbernot'), [], 422);
        }
        // Vérifier si l'ID est présent et valide
        $contact = Contact::where('uid', $uid)->first();
        if (!$contact) {
            Log::warning("Publipostage::update - Aucun Contact trouvé pour l'ID : {$uid}");
            return $this->sendSuccess(__('message.nodata'));
        }
        // Data to save
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
            Log::warning("Publipostage::update - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
	}
    // Suppression d'un Contact
    /**
    *   @OA\Delete(
    *   path="/api/publipostage/delete",
    *   tags={"Publipostage"},
    *   operationId="deletePub",
    *   description="Suppression d'un ou de plusieurs Contacts",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"contacts"},
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
    public function destroy(Request $request): JsonResponse
    {
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator        
        $validator = Validator::make($request->all(), [
            'contacts' => 'required|array',
            'contacts.*' => 'required|integer'
        ]);
        if ($validator->fails()) {
            Log::warning("Publipostage::destroy - Validator : {$validator->errors()->first()} - " . json_encode($request->all())
            );
            return $this->sendError(__('message.fielderr'), $validator->errors(), 422);
        }
        try {
            DB::beginTransaction();
            // Récupérer tous les contacts en une seule requête
            $contactIds = Contact::whereIn('uid', $request->contacts)
                ->where('user_id', Auth::user()->id)
                ->where('publipostage', 1)
                ->pluck('id');
            if ($contactIds->isEmpty()) {
                DB::rollBack();
                Log::warning("Publipostage::destroy - Aucun Contacts trouvés : " . json_encode($request->contacts));
                return $this->sendSuccess(__('message.nodata'));
            }
            // Supprimer tous les contacts en masse
            Contact::whereIn('id', $contactIds)->delete();
            DB::commit();
            return $this->sendSuccess(__('message.delcontact'), [], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::warning("Publipostage::destroy - Erreur : {$e->getMessage()} " . json_encode($request->all()));
            return $this->sendError(__('message.error'));
        }
    }
    //Importation
    /**
    * @OA\Post(
    *   path="/api/publipostage/imports",
    *   tags={"Publipostage"},
    *   operationId="importPub",
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
        // Language
        App::setLocale(Auth::user()->lg);
        // Validator
        $validator = Validator::make($request->all(), [
            'files' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);
        // Error field
        if ($validator->fails()) {
            Log::warning("Publipostage::imports - Validator : {$validator->errors()->first()} - " . json_encode($request->all()));
            return $this->sendError(__('message.fielderr'), $validator->errors()->first(), 422);
        }
        $import = new ContactImport(Auth::user(), 1);
        try {
            Excel::import($import, $request->file('files'));
            return $this->sendSuccess(__('message.impcontact'), [
                'imported' => $import->getImportedCount(),
                'total' => $import->getTotalRows(),
                'errors' => $import->getErrors(),
            ], 201);
        } catch (\Exception $e) {
            Log::warning("Publipostage::imports - Erreur : {$e->getMessage()}");
            return $this->sendError(__('message.error'), [], 400);
        }
    }
}
