<?php

namespace App\Http\Resources;

use App\Models\EmployRequest;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminEmployRequestList extends JsonResource
{
    public function toArray($request)
    {
        $study_degrees=EmployRequest::STUDY_DEGREES;
        $job_demands=EmployRequest::JOB_DEMANDS;
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'city'=>$this->province->name." - ". $this->city->name,
            'address'=>$this->address,
            'postal_code'=>$this->postal_code,
            'contact'=>$this->mobile. " - ".$this->second_mobile,
            'study_field'=>(isset($study_degrees[$this->study_degree])?$study_degrees[$this->study_degree]:''). " - ".$this->study_field,
            'job_demand'=>isset($job_demands[$this->job_demand])?$job_demands[$this->job_demand]:'',
            'guarantee'=>$this->guarantee,
            'work_experience'=>$this->work_experience,
        ];
    }
}
