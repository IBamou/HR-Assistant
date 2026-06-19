<?php

namespace App\Console\Commands;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:seed-test-offers')]
#[Description('Seed realistic job offers for testing the CV analysis feature')]
class SeedTestOffers extends Command
{
    public function handle(): void
    {
        $user = User::firstOrCreate([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $userId = $user->id;

        if (! $userId) {
            $this->components->error('No users found. Create a user first.');

            return;
        }

        $offers = [
            [
                'title' => 'Développeur PHP Senior Symfony',
                'description' => "Nous recherchons un développeur PHP senior spécialisé Symfony pour rejoindre notre équipe technique. Vous serez responsable de la conception, du développement et de la maintenance de nos applications web.\n\nMissions principales :\n- Développer et maintenir des applications web complexes avec Symfony\n- Participer à la conception technique et aux choix d'architecture\n- Encadrer les développeurs juniors\n- Assurer la qualité du code via les tests et les revues de code",
                'responsibilities' => "- Concevoir et développer des fonctionnalités back-end avec Symfony/PHP\n- Participer aux daily meetings et aux sprint planning\n- Effectuer des code reviews\n- Rédiger des tests unitaires et fonctionnels\n- Maintenir la documentation technique",
                'required_skills' => ['PHP', 'Symfony', 'MySQL', 'Git', 'Docker', 'REST API', 'PostgreSQL', 'Redis'],
                'soft_skills' => ['Leadership', 'Communication', 'Autonomie', 'Esprit d\'équipe'],
                'min_experience_level' => ExperienceLevel::Senior,
                'education_level' => 'Bac+5 (Master en Informatique)',
                'employment_type' => EmploymentType::FullTime,
                'location' => 'Paris',
            ],
            [
                'title' => 'Laravel Developer Full Stack',
                'description' => "Nous cherchons un développeur Laravel talentueux pour travailler sur notre plateforme SaaS innovante. Vous évoluerez dans une équipe agile et participerez à l'ensemble du cycle de développement.\n\nMissions principales :\n- Développer de nouvelles fonctionnalités sur notre plateforme Laravel\n- Collaborer avec l'équipe front-end pour intégrer les API\n- Optimiser les performances des requêtes et de l'application\n- Contribuer à l'amélioration continue de l'architecture",
                'responsibilities' => "- Développement back-end avec Laravel et Livewire\n- Création et maintenance d'APIs RESTful\n- Écriture de tests avec Pest PHP\n- Participation aux rituels agiles (daily, rétro, sprint planning)\n- Veille technologique et propositions d'amélioration",
                'required_skills' => ['PHP', 'Laravel', 'Livewire', 'MySQL', 'Git', 'REST API', 'JavaScript', 'Alpine.js', 'Tailwind CSS'],
                'soft_skills' => ['Travail d\'équipe', 'Curiosité', 'Rigueur', 'Adaptabilité'],
                'min_experience_level' => ExperienceLevel::Confirmed,
                'education_level' => 'Bac+5 (École d\'ingénieur ou Master)',
                'employment_type' => EmploymentType::FullTime,
                'location' => 'Lyon',
            ],
            [
                'title' => 'Data Engineer Python',
                'description' => "Rejoignez notre pôle Data pour construire et maintenir l'infrastructure de données de l'entreprise. Vous travaillerez sur des projets stimulants à forte volumétrie.\n\nMissions principales :\n- Concevoir et déployer des pipelines de données\n- Optimiser les performances des bases de données\n- Développer des outils de reporting et de visualisation\n- Collaborer avec les data scientists pour industrialiser les modèles",
                'responsibilities' => "- Développer des pipelines ETL avec Python\n- Administrer et optimiser les bases de données SQL et NoSQL\n- Mettre en place des dashboards de monitoring\n- Documenter l'architecture et les processus\n- Participer à l'évolution de la stack technique",
                'required_skills' => ['Python', 'SQL', 'PostgreSQL', 'Apache Spark', 'Docker', 'Airflow', 'Git', 'MongoDB', 'Redshift'],
                'soft_skills' => ['Analyse', 'Rigueur', 'Pédagogie', 'Autonomie'],
                'min_experience_level' => ExperienceLevel::Confirmed,
                'education_level' => 'Bac+5 (Data Science ou Informatique)',
                'employment_type' => EmploymentType::FullTime,
                'location' => 'Toulouse',
            ],
            [
                'title' => 'Développeur Front-end React',
                'description' => "Nous recrutons un développeur front-end React pour renforcer notre équipe produit. Vous participerez à la création d'interfaces utilisateur modernes et performantes.\n\nMissions principales :\n- Développer des composants React réutilisables\n- Optimiser les performances et l'accessibilité\n- Collaborer avec les designers UI/UX\n- Participer à l'architecture front-end",
                'responsibilities' => "- Développement d'interfaces avec React et TypeScript\n- Intégration des maquettes Figma\n- Écriture de tests avec Jest et Testing Library\n- Optimisation du Core Web Vitals\n- Participation aux Daily et Sprint Planning",
                'required_skills' => ['React', 'TypeScript', 'JavaScript', 'HTML/CSS', 'Git', 'REST API', 'Jest', 'Tailwind CSS'],
                'soft_skills' => ['Créativité', 'Travail d\'équipe', 'Sens du détail', 'Communication'],
                'min_experience_level' => ExperienceLevel::Confirmed,
                'education_level' => 'Bac+5 ou équivalent',
                'employment_type' => EmploymentType::FullTime,
                'location' => 'Paris',
            ],
            [
                'title' => 'Stagiaire Développeur Web',
                'description' => "Nous offrons un stage de fin d'études pour un développeur web passionné. Vous serez encadré par des développeurs expérimentés et participerez à des projets concrets.\n\nMissions principales :\n- Participer au développement d'une application web\n- Apprendre les bonnes pratiques de développement\n- Contribuer à l'amélioration du code existant\n- Présenter votre travail lors de démos d'équipe",
                'responsibilities' => "- Développement de fonctionnalités sous la supervision d'un senior\n- Correction de bugs et rédaction de tests\n- Participation aux cérémonies agiles\n- Rédaction de documentation technique\n- Présentation hebdomadaire de l'avancement",
                'required_skills' => ['PHP', 'JavaScript', 'HTML/CSS', 'Git', 'MySQL', 'Laravel'],
                'soft_skills' => ['Motivation', 'Apprentissage rapide', 'Curiosité', 'Rigueur'],
                'min_experience_level' => ExperienceLevel::Junior,
                'education_level' => 'Bac+3 à Bac+5 (en cours)',
                'employment_type' => EmploymentType::Internship,
                'location' => 'Bordeaux',
            ],
            [
                'title' => 'DevOps Engineer Kubernetes',
                'description' => "Nous recherchons un ingénieur DevOps expérimenté pour gérer et faire évoluer notre infrastructure cloud. Vous jouerez un rôle clé dans l'automatisation et l'optimisation de nos déploiements.\n\nMissions principales :\n- Gérer et optimiser les clusters Kubernetes\n- Automatiser les pipelines CI/CD\n- Assurer la disponibilité et la sécurité des infrastructures\n- Migrer les applications legacy vers le cloud",
                'responsibilities' => "- Administration de clusters Kubernetes (EKS)\n- Mise en place et maintenance des pipelines CI/CD (GitHub Actions)\n- Gestion de l'infrastructure en tant que code (Terraform)\n- Monitoring et alerting (Prometheus, Grafana)\n- Gestion des incidents et analyse post-mortem",
                'required_skills' => ['Kubernetes', 'Docker', 'Terraform', 'AWS', 'CI/CD', 'Linux', 'Git', 'Python', 'Bash', 'Prometheus'],
                'soft_skills' => ['Réactivité', 'Autonomie', 'Vigilance', 'Communication technique'],
                'min_experience_level' => ExperienceLevel::Senior,
                'education_level' => 'Bac+5 (Informatique)',
                'employment_type' => EmploymentType::FullTime,
                'location' => 'Remote (France)',
            ],
        ];

        foreach ($offers as $data) {
            Offer::create(array_merge($data, ['user_id' => $userId]));
        }

        $this->components->info(sprintf('Seeded %d test offers for user #%d.', count($offers), $userId));
    }
}
