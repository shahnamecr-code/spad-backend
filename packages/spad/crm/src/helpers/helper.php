<?php

use App\Models\Property;

function userShowCategoryCrmLayout(\App\Models\Category $category, $request=null){
    $searchItems=$category->crmFields;
    $allowed_properties = [];
    $layouts = $category->layout;
    foreach ($layouts as $layout) {
        if ($layout->layout) {
            foreach ($layout->layout as $item) {
                getLayoutAllProperties($item, $allowed_properties);
            }
        }
    }
    $properties=Property::whereIn('slug', $allowed_properties)->get()->pluck(null,'slug')->toArray();
    $items=[];
    if ($searchItems){
        foreach ($searchItems as $groupItem){
            if (isset($groupItem['filters']) && count($groupItem['filters'])>0){
                $group=[
                    'label'=>$groupItem['label']??'',
                    'icon'=>(isset($groupItem['icon']) && $groupItem['icon'])?$groupItem['icon']:false,
                    'items'=>[]
                ];
                foreach ($groupItem['filters'] as $searchItem){
                    $property=$properties[$searchItem['property_id']];
                    $new_item=[
                        'label'=>$property['title'],
                        'separator'=>$property['separator'],
                        'unit'=>$property['unit']?getUnitLabel($property['unit']):'',
                        'code'=>$searchItem['property_id'],
                        'search_type'=>$searchItem['search_type'],
                        'range_search'=>$searchItem['range_search'],
                        'property_type'=>$property['type'],
                        'search_operator'=>$searchItem['search_operator'],
                        'auto_complete'=> $searchItem['auto_complete'] ?? false,
                    ];
                    if ($property['type']=='geo'){
                        $new_item['search_type']='geo';
                    }
                    switch ($searchItem['search_type']){
                        case 'text':
                            break;
                        case 'select':
                        case 'multi-select':
                        case 'radio':
                        case 'checkbox':
                            if (isset($searchItem['search_enum'])){
                                $enum=$searchItem['search_enum'];
                                if (isset($searchItem['search_enumNames'])){
                                    $enumNames=$searchItem['search_enumNames'];
                                }else{
                                    $enumNames=$enum;
                                }
                            }else{
                                $enum=(array)$property['enum'];
                                $enumNames=(array)$property['enumNames']??$enum;
                            }
                            $options=[];
                            foreach ($enum as $index=>$enumItem){
                                $options[]=[
                                    'label'=>(isset($enumNames[$index]))?$enumNames[$index]:$enumItem,
                                    'value'=>$enumItem,
                                ];
                            }
                            $new_item['options']=$options;
                            break;
                    }
                    $group['items'][]=$new_item;
                }
                $items[]=$group;
            }
        }
    }
    return $items;
}

function userShowCrmRequestSingle($request_property,$crmField){
    $data=[
        'title'=>$request_property['property']['title'],
        'icon'=>$request_property['property']['icon'],
//        'property'=>$request_property,
        'range_search'=>$crmField['range_search'],
//        'crm'=>$crmField
    ];


    $data['unit']=getUnitLabel($request_property['property']['unit']);

/*    $enum=[];
    $enumNames=[];
    if (isset($crmField['search_enum'])){
        $enum=$crmField['search_enum'];
        if (isset($crmField['search_enumNames'])){
            $enumNames=$crmField['search_enumNames'];
        }else{
            $enumNames=$enum;
        }
    }elseif(isset($request_property['property']) & isset($request_property['property']['enum'])){
        $enum=$request_property['property']['enum'];
        if (isset($request_property['property']['enumNames'])){
            $enumNames=$request_property['property']['enumNames'];
        }else{
            $enumNames=$enum;
        }
    }
    $options=array_combine($enum,$enumNames);*/
    if ($request_property['property']['type']=='geo'){
        return false;
    }

    if ($crmField['range_search']){
        if (isset($request_property['json_value'])){
            $data['range']=$request_property['json_value'];
        }
    }else{
        switch ($crmField['search_type']){
            case 'text':
            case 'select':
                $data['type']='exact';
                $data['exact']=$request_property['text_value'];
                break;
            case 'multi-select':
                $data['type']='in';
                $data['in']=$request_property['json_value'];
                break;
            case 'switch':
                $data['type']='has';
                $data['has']=$request_property['boolean_value'];
                break;
        }
    }

    return $data;
}

function crmEditPageProperties($property,$crm_field){
    if($crm_field['range_search']){
        return $property->json_value;
    }
    if ($property->property->type=='geo'){
        return $property->json_value;
    }
    switch ($property->type){
        case 'text':
        case 'select':
            return $property->text_value;
        case 'switch':
            return $property->boolean_value;
        case 'multi-select':
            return $property->json_value;
    }
}
