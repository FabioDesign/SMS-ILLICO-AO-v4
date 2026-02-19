<?php

namespace App\Http\Controllers\API;

use \Carbon\Carbon;
use App\Models\Contact;
use Illuminate\Support\Str;
use Illuminate\Http\{Request, JsonResponse};
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
            return $this->sendError(__('message.displayerr'));
        }
    }
    //Détail d'un Contact
    /**
    * @OA\Get(
    *   path="/api/phonebooks/{uid}",
    *   tags={"Phonebooks"},
    *   operationId="showContact",
    *   description="Détail d'un Contact",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Détail d'un Contact."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function show($uid): JsonResponse {
        //User
        $user = Auth::user();
		App::setLocale($user->lg);
        try {
            // Vérifier si l'ID est présent et valide
            $query = Contact::select('label', 'number', 'gender', 'date_at', 'field1', 'field2', 'field3', 'blacklist', 'publipostage')
            ->where('uid', $uid)
            ->first();
            if (!$query) {
                Log::warning("Contact::show - Aucun Contact trouvé pour l'ID : " . $uid);
                return $this->sendSuccess(__('message.nodata'));
            }
            // Retourner les détails du Contact avec les files
            return $this->sendSuccess('Détails sur le Contact', [
                'label' => $query->label,
                'number' => $query->number,
                'gender' => $query->gender,
                'date_at' => Carbon::parse($query->date_at)->format('d/m/Y'),
                'field1' => $query->field1,
                'field2' => $query->field2,
                'field3' => $query->field3,
                'blacklist' => $query->blacklist,
                'publipostage' => $query->publipostage,
            ]);
        } catch(\Exception $e) {
            Log::warning("Contact::show - Erreur d'affichage de Contacts : ".$e->getMessage());
            return $this->sendError(__('message.displayerr'));
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
    *   @OA\Response(response=200, description="Contact enregisté avec succès."),
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
            'number' => 'required|number|unique:contacts,number,' . $user->id . ',user_id',
            'gender' => 'present',
            'date_at' => 'present|date_format:Y-m-d',
            'field1' => 'present',
            'field2' => 'present',
            'field3' => 'present',
        ]);
        //Error field
        if($validator->fails()){
            Log::warning("Contact::store - Validator : " . $validator->errors()->first() . " - ".json_encode($request->all()));
            return $this->sendError('Champs invalides.', $validator->errors(), 422);
        }
        // Création de la reclamation
        $set = [
            'label' => $request->label,
            'fr' => $request->fr,
            'code' => $request->code,
            'created_user' => $user->id,
            'amount' => $request->amount ?? '',
            'number' => $request->number ?? '',
            'period_id' => $request->period_id ?? 0,
            'description_en' => $request->description_en,
            'description_fr' => $request->description_fr,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $Contact = Contact::create($set);
            // Valider la transaction
            DB::commit();
            // Si des fichiers sont fournies, les associer au Contact
            if ($request->has('docs') && is_array($request->docs)) {
                foreach ($request->docs as $docs) {
                    $file = Str::of($docs)->explode('|');
                    $requestdoc = Requestdoc::where('uid', $file[0])->first();
                    // Enregistrer le fichier
                    File::firstOrCreate([
                        'requestdoc_id' => $requestdoc->id,
                        'Contact_id' => $Contact->id,
                        'required' => $file[1],
                    ]);
                }
            }
            return $this->sendSuccess("Contact enregistré avec succès.", [
                'code' => $request->code,
                'en' => $request->en,
                'fr' => $request->fr,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Contact::store : " . $e->getMessage() . " " . json_encode($set));
            return $this->sendError("Erreur lors de l'enregistrement du Contact.");
        }
    }
    // Modification
    /**
    * @OA\Put(
    *   path="/api/phonebooks/{uid}",
    *   tags={"Phonebooks"},
    *   operationId="editDocs",
    *   description="Modification d'un Contact",
    *   security={{"bearer":{}}},
    *   @OA\RequestBody(
    *      required=true,
    *      @OA\JsonContent(
    *         required={"code", "en", "fr", "description_en", "description_fr", "docs", "status"},
    *         @OA\Property(property="code", type="string"),
    *         @OA\Property(property="en", type="string"),
    *         @OA\Property(property="fr", type="string"),
    *         @OA\Property(property="amount", type="string"),
    *         @OA\Property(property="number", type="string"),
    *         @OA\Property(property="period_id", type="integer"),
    *         @OA\Property(property="description_en", type="string"),
    *         @OA\Property(property="description_fr", type="string"),
    *         @OA\Property(property="status", type="integer"),
    *         @OA\Property(property="docs", type="array", @OA\Items(
    *               @OA\Property(property="requestdoc_id", type="integer"),
    *               @OA\Property(property="required", type="integer"),
    *               example="[1|1, 2|1, 3|0]"
    *           )
    *         ),
    *      )
    *   ),
    *   @OA\Response(response=200, description="Contact modifié avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function update(request $request, $uid): JsonResponse {
        //User
        $user = Auth::user();
		App::setLocale($user->lg);
        //Data
        Log::notice("Contact::update - ID User : {$user->id} - Requête : " . json_encode($request->all()));
        //Validator
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:5|unique:contacts,code,' . $uid . ',uid',
            'en' => 'required|string|max:255|unique:contacts,en,' . $uid . ',uid',
            'fr' => 'required|string|max:255|unique:contacts,fr,' . $uid . ',uid',
            'amount' => 'present',
            'number' => 'present',
            'period_id' => 'present',
            'description_en' => 'required',
            'description_fr' => 'required',
            'docs' => 'required|array',
            'status' => 'required|integer|in:0,1',
        ]);
        //Error field
        if($validator->fails()){
            Log::warning("Contact::update - Validator : " . $validator->errors()->first() . " - ".json_encode($request->all()));
            return $this->sendError('Champs invalides.', $validator->errors(), 422);
        }
        // Vérifier si l'ID est présent et valide
        $Contact = Contact::where('uid', $uid)->first();
        if (!$Contact) {
            Log::warning("Contact::update - Aucun Contact trouvé pour l'ID : " . $uid);
            return $this->sendSuccess(__('message.nodata'));
        }
        // Création de la reclamation
        $set = [
            'en' => $request->en,
            'fr' => $request->fr,
            'code' => $request->code,
            'updated_user' => $user->id,
            'status' => $request->status,
            'amount' => $request->amount ?? '',
            'number' => $request->number ?? '',
            'period_id' => $request->period_id ?? 0,
            'description_en' => $request->description_en,
            'description_fr' => $request->description_fr,
        ];
        DB::beginTransaction(); // Démarrer une transaction
        try {
            $Contact->update($set);
            // Valider la transaction
            DB::commit();
            // Si des fichiers sont fournies, les associer au profil
            if ($request->has('docs') && is_array($request->docs)) {
                // Supprimer les fichiers existantes pour ce Contact
                File::where('Contact_id', $Contact->id)->delete();
                foreach ($request->docs as $docs) {
                    $file = Str::of($docs)->explode('|');
                    $requestdoc = Requestdoc::where('uid', $file[0])->first();
                    // Enregistrer le fichier
                    File::firstOrCreate([
                        'requestdoc_id' => $requestdoc->id,
                        'Contact_id' => $Contact->id,
                        'required' => $file[1],
                    ]);
                }
            }
            return $this->sendSuccess("Contact modifié avec succès.", [
                'code' => $request->code,
                'en' => $request->en,
                'fr' => $request->fr,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            Log::warning("Contact::update : " . $e->getMessage() . " " . json_encode($set));
            return $this->sendError("Erreur lors de l'enregistrement du Contact.");
        }
	}
    // Suppression d'un Contact
    /**
    *   @OA\Delete(
    *   path="/api/phonebooks/{uid}",
    *   tags={"Phonebooks"},
    *   operationId="deleteDocs",
    *   description="Suppression d'un Contact",
    *   security={{"bearer":{}}},
    *   @OA\Response(response=200, description="Contact supprimé avec succès."),
    *   @OA\Response(response=400, description="Serveur indisponible."),
    *   @OA\Response(response=404, description="Page introuvable.")
    * )
    */
    public function destroy($uid): JsonResponse {
        //User
        $user = Auth::user();
		App::setLocale($user->lg);
        //Data
        Log::notice("Contact::destroy - ID User : {$user->id} - Requête : " . $uid);
        try {
            // Vérification si le Contact est attribué à une demande
            $Contact = Contact::select('contacts.id', 'Contact_id')
            ->where('contacts.uid', $uid)
            ->leftJoin('demands', 'demands.Contact_id','=','contacts.id')
            ->first();
            if ($Contact->Contact_id != null) {
                Log::warning("Contact::destroy - Tentative de suppression d'un Contact déjà attribué à une demande : " . $uid);
                return $this->sendError("Contact est déjà attribué à une demande.", [], 403);
            }
            // Suppression
            $deleted = Contact::destroy($Contact->id);
            if (!$deleted) {
                Log::warning("Contact::destroy - Tentative de suppression d'un Contact inexistante : " . $uid);
                return $this->sendError("Impossible de supprimer le Contact.", [], 403);
            }
            File::where('Contact_id', $Contact->id)->delete();
            return $this->sendSuccess("Contact supprimé avec succès.");
        } catch(\Exception $e) {
            Log::warning("Contact::destroy - Erreur lors de la suppression d'un Contact : " . $e->getMessage());
            return $this->sendError("Erreur lors de la suppression d'un Contact.");
        }
    }
}
