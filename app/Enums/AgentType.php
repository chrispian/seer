<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum AgentType: string
{
    case BackendEngineer = 'backend-engineer';
    case FrontendEngineer = 'frontend-engineer';
    case InfrastructureSpecialist = 'infrastructure-specialist';
    case QaEngineer = 'qa-engineer';
    case SeoSpecialist = 'seo-specialist';
    case ProjectManager = 'project-manager';
    case ProjectCoordinator = 'project-manager-coordinator';
    case SeniorEngineerReviewer = 'senior-engineer-code-reviewer';
    case DevRelSpecialist = 'devrel-specialist';
    case TechWriterDevRel = 'tech-writer-devrel';
    case TechWriterUserDocs = 'tech-writer-user-docs';
    case CopywriterWebsite = 'copywriter-website';
    case UxDesigner = 'ux-designer';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::BackendEngineer => 'Backend Engineer',
            self::FrontendEngineer => 'Frontend Engineer',
            self::InfrastructureSpecialist => 'Infrastructure Specialist',
            self::QaEngineer => 'QA Engineer',
            self::SeoSpecialist => 'SEO Specialist',
            self::ProjectManager => 'Project Manager',
            self::ProjectCoordinator => 'Project Coordinator',
            self::SeniorEngineerReviewer => 'Senior Engineer – Code Reviewer',
            self::DevRelSpecialist => 'Developer Relations Specialist',
            self::TechWriterDevRel => 'Technical Writer – DevRel',
            self::TechWriterUserDocs => 'Technical Writer – User Docs',
            self::CopywriterWebsite => 'Copywriter – Marketing Website',
            self::UxDesigner => 'UX Designer',
            self::Custom => 'Custom',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::BackendEngineer => 'Full-stack Laravel and PHP implementation focus.',
            self::FrontendEngineer => 'React, TypeScript, and UI implementation focus.',
            self::InfrastructureSpecialist => 'Deployments, CI/CD, and platform reliability.',
            self::QaEngineer => 'Quality assurance, testing, and validation workflows.',
            self::SeoSpecialist => 'Search optimization and analytics-driven content support.',
            self::ProjectManager => 'High-level planning, status tracking, and coordination.',
            self::ProjectCoordinator => 'Hands-on sprint execution and follow-through.',
            self::SeniorEngineerReviewer => 'Code review, architecture guidance, and mentorship.',
            self::DevRelSpecialist => 'Developer relations content and advocacy.',
            self::TechWriterDevRel => 'Technical content tailored for developer audiences.',
            self::TechWriterUserDocs => 'User-focused documentation and guides.',
            self::CopywriterWebsite => 'Marketing and product copy for web surfaces.',
            self::UxDesigner => 'User research, flows, and interaction patterns.',
            self::Custom => 'Custom agent profile for specialized workflows.',
        };
    }

    public function defaultMode(): AgentMode
    {
        return match ($this) {
            self::BackendEngineer,
            self::FrontendEngineer,
            self::InfrastructureSpecialist => AgentMode::Implementation,
            self::QaEngineer,
            self::SeoSpecialist => AgentMode::Analysis,
            self::ProjectManager,
            self::ProjectCoordinator => AgentMode::Coordination,
            self::SeniorEngineerReviewer => AgentMode::Review,
            self::DevRelSpecialist => AgentMode::Enablement,
            self::TechWriterDevRel,
            self::TechWriterUserDocs,
            self::CopywriterWebsite => AgentMode::Enablement,
            self::UxDesigner => AgentMode::Planning,
            self::Custom => AgentMode::Implementation,
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $type) => $type->value, self::cases());
    }

    public static function fromLabel(string $label): ?self
    {
        $normalized = Str::slug($label);

        foreach (self::cases() as $case) {
            if (Str::slug($case->label()) === $normalized) {
                return $case;
            }
        }

        return null;
    }
}
