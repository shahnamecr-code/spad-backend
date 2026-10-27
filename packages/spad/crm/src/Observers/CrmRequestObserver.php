<?php

namespace Spad\Crm\Observers;

use Illuminate\Support\Facades\Log;
use Spad\Crm\Models\CrmRequest;

class CrmRequestObserver
{

    public $afterCommit = true;
    /**
     * Handle the City "deleted" event.
     *
     * @param  CrmRequest  $crmRequest
     * @return void
     */
    public function deleted(CrmRequest $crmRequest)
    {
        $crmRequest->properties()->delete();
    }
}
