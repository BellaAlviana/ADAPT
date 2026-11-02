<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagramGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'narrative',
        'status',
        'clarification_message',
        'diagram_data',
        'xml_content',
        'validation_notes',
    ];

    protected $casts = [
        'diagram_data' => 'array',
        'validation_notes' => 'array',
    ];
}
