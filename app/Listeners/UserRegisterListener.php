<?php

namespace App\Listeners;

use App\Events\UserRegisterEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UserRegisterListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserRegisterEvent  $event
     * @return void
     */
    public function handle(UserRegisterEvent $event)
    {
        $user = $event->user;
        $templateId = config('sms.welcome_template');

        // Welcome SMS is optional. Registration must never fail just because
        // a welcome template has not been configured in SMS.ir yet.
        if (!$templateId) {
            return;
        }

        try {
            sms()->send('hi', function ($sms) use ($user, $templateId) {
                $sms->to($user->mobile);
                $sms->template($templateId, [
                    config('sms.welcome_parameter', 'NAME') => $user->full_name,
                ]);
            });
        } catch (\Throwable $exception) {
            Log::error('SMS.ir welcome message failed', [
                'user_id' => $user->id ?? null,
                'mobile' => $user->mobile ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
