<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class TalentoMatrizSkillsController extends Controller
{
    public function __invoke(): Response
    {
        $skills = Skill::query()->with(['users' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();

        $users = User::query()
            ->whereHas('skills')
            ->with(['skills' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'avatar']);

        return Inertia::render('talento/MatrizSkills', [
            'skills' => $skills,
            'users_with_skills' => $users,
        ]);
    }
}
