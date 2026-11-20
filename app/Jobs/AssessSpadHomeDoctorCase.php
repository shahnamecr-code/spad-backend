<?php

namespace App\Jobs;

use App\Models\SpadHomeDoctorCase;
use App\Models\SpadHomeDoctorFollowUp;
use App\Services\HomeDoctor\Assessment;
use App\Services\HomeDoctor\Safety;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class AssessSpadHomeDoctorCase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 1;
    public $timeout = 65;

    private $caseId;
    private $attemptId;
    private $followUpId;

    public function __construct(string $caseId, string $attemptId, ?string $followUpId = null)
    {
        $this->caseId = $caseId;
        $this->attemptId = $attemptId;
        $this->followUpId = $followUpId;
    }

    private function target()
    {
        if ($this->followUpId !== null) {
            return SpadHomeDoctorFollowUp::query()->whereKey($this->followUpId)->where('case_id', $this->caseId);
        }

        return SpadHomeDoctorCase::query()->whereKey($this->caseId)->where('analysis_attempt', $this->attemptId);
    }

    public function handle(Assessment $assessment): void
    {
        $started = $this->target()->where('status', 'queued')->update([
            'status' => 'analyzing',
            'analysis_started_at' => now(),
        ]);
        if (!$started) {
            return;
        }
        $case = SpadHomeDoctorCase::find($this->caseId);
        $followUp = $this->followUpId === null ? null : $this->target()->first();
        if (!$case || ($this->followUpId !== null && !$followUp)) {
            return;
        }

        $text = implode(' ', array_merge([$case->description, $followUp ? $followUp->question : ''], $case->answers ?? []));
        $result = Safety::referral($text) ?? $assessment->run($case, $followUp ? $followUp->question : null);
        $state = !$result ? 'unavailable' : ($result['source'] === 'safety_rule' ? 'referral' : 'analyzed');
        $this->target()->where('status', 'analyzing')->update([
            'status' => $state,
            'result' => $result ? json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null,
        ]);
    }

    public function failed(\Throwable $error): void
    {
        $this->target()->whereIn('status', ['queued', 'analyzing'])->update(['status' => 'unavailable']);
    }
}
