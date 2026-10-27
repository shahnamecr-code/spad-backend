<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable=[
        'title', 'order', 'type','access',
        'searchable', 'search_level', 'search_type', 'show_in_crm','search_enum','search_enumNames','enum','enumNames','search_operator','slug',
        'unit','icon','separator'
    ];

    protected $casts=[
        'order'=>'integer',
        'searchable'=>'boolean',
        'show_in_crm'=>'boolean',
        'enum'=>'array',
        'enumNames'=>'array',
        'search_enum'=>'array',
        'search_enumNames'=>'array',
        'separator'=>'boolean'
    ];

    protected $appends=['unit_name'];

    public function scopeOrder($query)
    {
        return $query->orderBy('order','asc');
    }

    public function getOptionsAttribute()
    {
        if ($this->enum){
            $options=[];
            $enums=$this->enum;
            foreach ($enums as $index=>$enum){
                $value=$enum;
                if ($this->enumNames && isset($this->enumNames[$index])){
                    $label=$this->enumNames[$index];
                }else{
                    $label=$value;
                }
                $options[]=[
                    'label'=>$label,
                    'value'=>$value
                ];
            }
            return $options;
        }
    }

    public function getUnitNameAttribute()
    {
        return getUnitLabel($this->attributes['unit']??'');
    }

    public function icon_file()
    {
        return $this->belongsTo(File::class,'icon');
    }
}
