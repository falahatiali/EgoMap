<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Missions\MissionApiPresenter;
use App\Services\Missions\MissionWorkspacePresenter;
use App\Support\LocaleConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\MissionEngine\Enums\MissionEnrollmentStatus;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Services\MissionEnrollmentService;

class MissionController extends Controller
{
    public function index(Request $request, MissionApiPresenter $presenter): JsonResponse
    {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $locale = LocaleConfig::resolve($request->header('Accept-Language'));

        $templates = MissionTemplate::query()
            ->with('category')
            ->where('status', MissionTemplateStatus::Published)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->get();

        $enrollments = MissionEnrollment::query()
            ->where('user_id', $user->id)
            ->whereIn('template_id', $templates->pluck('id'))
            ->latest('updated_at')
            ->get()
            ->keyBy('template_id');

        $activeEnrollments = MissionEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', MissionEnrollmentStatus::Active)
            ->latest('updated_at')
            ->get();

        return response()->json([
            'labels' => $presenter->labels($locale),
            'templates' => $templates
                ->map(fn (MissionTemplate $template): array => $presenter->presentTemplateCard(
                    $template,
                    $locale,
                    $enrollments->get($template->id),
                ))
                ->values()
                ->all(),
            'active_enrollments' => $activeEnrollments
                ->map(fn (MissionEnrollment $enrollment): array => $presenter->presentEnrollmentSummary($enrollment, $locale))
                ->values()
                ->all(),
        ]);
    }

    public function show(
        Request $request,
        string $slug,
        MissionApiPresenter $presenter,
    ): JsonResponse {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $locale = LocaleConfig::resolve($request->header('Accept-Language'));

        $template = MissionTemplate::query()
            ->with(['category', 'capabilities.capabilityType', 'phases'])
            ->where('slug', $slug)
            ->where('status', MissionTemplateStatus::Published)
            ->firstOrFail();

        $enrollment = MissionEnrollment::query()
            ->where('user_id', $user->id)
            ->where('template_id', $template->id)
            ->latest('updated_at')
            ->first();

        return response()->json([
            'labels' => $presenter->labels($locale),
            'template' => $presenter->presentTemplate($template, $locale, $enrollment),
        ]);
    }

    public function enroll(
        Request $request,
        string $slug,
        MissionEnrollmentService $enrollmentService,
        MissionApiPresenter $presenter,
        MissionWorkspacePresenter $workspacePresenter,
    ): JsonResponse {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $locale = LocaleConfig::resolve($request->header('Accept-Language'));

        $template = MissionTemplate::query()
            ->where('slug', $slug)
            ->where('status', MissionTemplateStatus::Published)
            ->firstOrFail();

        $existing = MissionEnrollment::query()
            ->where('user_id', $user->id)
            ->where('template_id', $template->id)
            ->where('status', MissionEnrollmentStatus::Active)
            ->first();

        if ($existing !== null) {
            return response()->json([
                'enrollment' => $presenter->presentEnrollmentSummary($existing, $locale),
                'workspace' => $workspacePresenter->present($existing->fresh(), $user, $locale),
            ]);
        }

        $enrollment = $enrollmentService->enroll($user, $template);

        return response()->json([
            'enrollment' => $presenter->presentEnrollmentSummary($enrollment, $locale),
            'workspace' => $workspacePresenter->present($enrollment->fresh(), $user, $locale),
        ], 201);
    }

    public function workspace(
        Request $request,
        string $uuid,
        MissionWorkspacePresenter $workspacePresenter,
    ): JsonResponse {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $locale = LocaleConfig::resolve($request->header('Accept-Language'));

        $enrollment = MissionEnrollment::query()
            ->where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json($workspacePresenter->present($enrollment, $user, $locale));
    }
}
