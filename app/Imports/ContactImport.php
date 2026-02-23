<?php

namespace App\Imports;

use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ContactImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    protected $user;
    protected $errors = [];
    protected $rowNumber = 1;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function model(array $row)
    {
        $this->rowNumber++;
        
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
                    ->where('publipostage', 0)
                    ->where('status', 0);
                }),
            ],
            'gender' => 'present',
            'date_at' => 'nullable|date|date_format:Y-m-d',
            'field1' => 'present',
            'field2' => 'present',
            'field3' => 'present',
        ]);

        if ($validator->fails()) {
            $this->errors[] = [
                'row' => $this->rowNumber + 1, // +1 car on a commencé à la ligne 2
                'data' => $data,
                'errors' => $validator->errors()->all()
            ];
            return null;
        }

        // Si validation réussie, créer le contact
        return new Contact([
            'user_id' => $this->user->id,
            'label' => $data['label'],
            'number' => $data['number'],
            'gender' => $data['gender'],
            'date_at' => $data['date_at'],
            'field1' => $data['field1'],
            'field2' => $data['field2'],
            'field3' => $data['field3'],
            'publipostage' => 0,
            'status' => 0,
        ]);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}