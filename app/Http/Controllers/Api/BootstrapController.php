<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\RebootProtocolQuiz;
use Illuminate\Http\JsonResponse;

class BootstrapController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'locale' => app()->getLocale(),
            'landing' => [
                'core_message' => __('landing.core_message'),
                'hero_title_1' => __('landing.hero_title_1'),
                'hero_title_2' => __('landing.hero_title_2'),
                'hero_typed_prefix' => __('landing.hero_typed_prefix'),
                'hero_typed_words' => __('landing.hero_typed_words'),
                'hero_subtitle' => __('landing.hero_subtitle'),
                'hero_emotional_line' => __('landing.hero_emotional_line'),
                'cta_step1' => __('landing.cta_step1'),
                'cta_step1_note' => __('landing.cta_step1_note'),
                'steps_title' => __('landing.steps_title'),
                'steps_subtitle' => __('landing.steps_subtitle'),
                'steps' => [
                    ['title' => __('landing.step1_title'), 'description' => __('landing.step1_desc')],
                    ['title' => __('landing.step2_title'), 'description' => __('landing.step2_desc')],
                    ['title' => __('landing.step3_title'), 'description' => __('landing.step3_desc')],
                ],
                'flow' => [
                    __('landing.flow_checkin'),
                    __('landing.flow_protocol'),
                    __('landing.flow_missions'),
                ],
                'panel' => [
                    'title' => __('landing.panel_scan_title'),
                    'current_state' => __('landing.panel_current_state'),
                    'current_state_value' => __('landing.panel_current_state_value'),
                    'main_risk' => __('landing.panel_main_risk'),
                    'main_risk_value' => __('landing.panel_main_risk_value'),
                    'ghost_mode' => __('landing.panel_nc'),
                    'ghost_mode_value' => __('landing.panel_nc_value'),
                    'rebuild_index' => __('landing.panel_rebuild_index'),
                    'rebuild_value' => __('landing.panel_rebuild_value'),
                    'action' => __('landing.panel_action'),
                    'action_value' => __('landing.panel_action_value'),
                ],
                'terminal_bar' => __('landing.terminal_bar'),
                'emergency_title' => __('landing.panel_emergency_title'),
                'emergency_line_1' => __('landing.panel_emergency_line_1'),
                'emergency_line_2' => __('landing.panel_emergency_line_2'),
            ],
            'quiz' => [
                'featured_slug' => RebootProtocolQuiz::SLUG,
                'checkin_title' => __('landing.checkin_title'),
                'checkin_subtitle' => __('landing.checkin_subtitle'),
                'checkin_cta' => __('landing.checkin_cta'),
            ],
            'auth' => [
                'login_title' => __('auth.login_title'),
                'login_subtitle' => __('auth.login_subtitle'),
                'register_title' => __('auth.register_title'),
                'register_subtitle' => __('auth.register_subtitle'),
                'verify_title' => __('auth.verify_title'),
            ],
        ]);
    }
}
