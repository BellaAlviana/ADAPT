<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagramAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_path',
        'narrative',
        'status',
        'clarification_message',
        'findings',
        'revised_diagram_data',
        'revised_xml_content',
    ];

    protected $casts = [
        'findings' => 'array',
        'revised_diagram_data' => 'array',
    ];
}
