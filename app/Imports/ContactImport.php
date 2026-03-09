<?php

namespace App\Imports;

use App\Models\Contact;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\{SkipsEmptyRows, ToModel, WithStartRow};

class ContactImport implements ToModel, SkipsEmptyRows, WithStartRow
{
    protected $user;
    protected $errors = [];
    protected $publipostage;
    protected $rowNumber = 0;
    protected $totalRows = 0;
    protected $ContactIds = [];
    protected $importedCount = 0;

    public function __construct($user, $publipostage)
    {
        $this->user = $user;
        $this->publipostage = $publipostage;
    }

    /**
     * Définir à partir de quelle ligne commencer (ignore l'en-tête)
     */
    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        $this->rowNumber++;
        $this->totalRows++;        
        // Ignorer les lignes complètement vides
        if (empty(array_filter($row))) {
            return null;
        }
        // Récupérer les valeurs de la ligne
        $data = [
            'label' => $row[0] ?? null,
            'number' => $row[1] ?? null,
            'gender' => $row[2] ?? null,
            'date_at' => $row[3] ?? null,
            'field1' => $row[4] ?? null,
            'field2' => $row[5] ?? null,
            'field3' => $row[6] ?? null,
        ];
        // Valider les données
        $validator = Validator::make($data, [
            'label' => 'required',
            'number' => [
                'required',
                'digits:9',
                'numeric',
                Rule::unique('contacts')->where(function ($query) {
                    return $query->where('user_id', $this->user->id)
                    ->where('publipostage', $this->publipostage);
                }),
            ],
            'gender' => 'present',
            'date_at' => 'nullable|date|date_format:Y-m-d',
            'field1' => 'present',
            'field2' => 'present',
            'field3' => 'present',
        ]);
        //Error fields
        if ($validator->fails()) {
            $contactId = Contact::where('number', $data['number'])
                ->where('user_id', $this->user->id)
                ->where('publipostage', $this->publipostage)
                ->first();
            if ($contactId) {
                $this->ContactIds[] = $contactId->id;
            }
            $this->errors[] = [
                'row' => $this->rowNumber + 1, // +1 car on a commencé à la ligne 2
                'data' => $data,
                'errors' => $validator->errors()->all()
            ];
            return null;
        }
        // Si validation réussie, incrémenter le compteur
        $this->importedCount++;        
        // Créer le contact
        $contact = new Contact([
            'user_id' => $this->user->id,
            'label' => $data['label'],
            'number' => $data['number'],
            'gender' => $data['gender'],
            'date_at' => $data['date_at'],
            'field1' => $data['field1'],
            'field2' => $data['field2'],
            'field3' => $data['field3'],
            'publipostage' => $this->publipostage,
        ]);
        $contact->save();        
        // Collecter l'ID du contact importé
        $this->ContactIds[] = $contact->id;        
        return $contact;
    }
    // Getters pour les erreurs, le nombre de contacts importés et le nombre total de lignes traitées
    public function getErrors()
    {
        return $this->errors;
    }
    // Récupérer le nombre de contacts importés avec succès
    public function getImportedCount()
    {
        return $this->importedCount;
    }
    // Récupérer le nombre total de lignes traitées (y compris les erreurs)
    public function getTotalRows()
    {
        return $this->totalRows;
    }
    // Récupérer les IDs des contacts importés
    public function getContactIds()
    {
        return $this->ContactIds;
    }
}