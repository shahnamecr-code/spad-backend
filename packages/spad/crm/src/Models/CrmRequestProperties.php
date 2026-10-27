<?php

namespace Spad\Crm\Models;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmRequestProperties extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded=[];

    protected $casts=[
        'text_value'=>'string',
        'json_value'=>'array',
        'integer_value'=>'integer',
        'boolean_value'=>'boolean'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class,'property_slug','slug');
    }
}
