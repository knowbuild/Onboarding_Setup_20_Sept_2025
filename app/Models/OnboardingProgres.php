<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingProgres extends Model
{
     protected $guarded=[];

         public function onboardingStep()
    {
        return $this->belongsTo(OnboardingStep::class, 'onboarding_step_id');
    }
}
  