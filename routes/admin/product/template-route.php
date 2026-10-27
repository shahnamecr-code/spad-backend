<?php

Route::prefix('template')->group(function(){
    Route::get('', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'index']);
    Route::post('', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'store']);
    Route::delete('delete/{template?}',[\App\Http\Controllers\Admin\ProductTemplatesController::class,'delete']);
    Route::get('{template}/get_name',[\App\Http\Controllers\Admin\ProductTemplatesController::class, 'get_name']);
    Route::get('{template}/attributes/', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'getTemplateAttributes']);
    Route::get('default_attributes/{template?}', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'getTemplateDefaultAttributes']);
    Route::get('{template}/variable_attributes', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'getTemplateVariableAttributes']);
    Route::post('{template}/variable_attributes', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'saveTemplateVariableAttributes']);
    Route::post('search_templates', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'search_templates']);
    Route::get('categories/{template}', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'getTemplateCategories']);
    Route::post('categories/{template}', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'syncTemplateCategories']);
    Route::post('{template}/edit', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'update']);
    Route::get('{template}', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'show']);
    Route::get('{template}/group_attributes', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'get_group_attributes']);
    Route::post('{template}/group_attributes', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'save_group_attributes']);
    Route::post('{template}/custom_attributes', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'update_custom_attributes']);
//        template actions
    Route::post('change-status/{template}',[\App\Http\Controllers\Admin\ProductTemplatesController::class,'updateStatus']);

    Route::get('{template}/units',[\App\Http\Controllers\Admin\ProductTemplatesController::class, 'get_template_units']);
    Route::post('{template}/add_unit',[\App\Http\Controllers\Admin\ProductTemplatesController::class,'add_template_unit']);
    Route::post('{template}/edit_unit/{productUnit}',[\App\Http\Controllers\Admin\ProductTemplatesController::class,'update_template_unit']);
    Route::get('{template}/is_used',[\App\Http\Controllers\Admin\ProductTemplatesController::class,'shop_has_template']);
    Route::post('{template}/setDefaultUnit/{unit}',[\App\Http\Controllers\Admin\ProductTemplatesController::class,'setDefaultUnit']);

    Route::get('products/{template}', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'get_template_products']);
    Route::post('products/{template}/product', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'add_product_template']);
    Route::post('products/{template}/product/{product}', [\App\Http\Controllers\Admin\ProductTemplatesController::class, 'update_template_product_default']);

});
