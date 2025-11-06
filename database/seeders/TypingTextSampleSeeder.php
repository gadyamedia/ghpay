<?php

namespace Database\Seeders;

use App\Models\TypingTextSample;
use Illuminate\Database\Seeder;

class TypingTextSampleSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            [
                'title' => 'Quick Brown Fox - Easy',
                'difficulty' => 'easy',
                'content' => 'The quick brown fox jumps over the lazy dog. This pangram contains every letter of the English alphabet at least once. It is commonly used for testing typewriters and computer keyboards, displaying examples of fonts, and other applications involving text.',
                'is_active' => true,
            ],
            [
                'title' => 'Technology and Innovation - Easy',
                'difficulty' => 'easy',
                'content' => 'Technology continues to evolve at a rapid pace. From smartphones to artificial intelligence, innovations are transforming how we live and work. These advancements bring both opportunities and challenges that society must address together.',
                'is_active' => true,
            ],
            [
                'title' => 'Professional Communication - Medium',
                'difficulty' => 'medium',
                'content' => 'Effective communication in the workplace requires clarity, professionalism, and attention to detail. Whether drafting emails, creating reports, or participating in meetings, the ability to convey information accurately and efficiently is crucial for success in any professional environment.',
                'is_active' => true,
            ],
            [
                'title' => 'Data Analysis Overview - Medium',
                'difficulty' => 'medium',
                'content' => 'Data analysis involves examining, cleaning, transforming, and modeling data to discover useful information, draw conclusions, and support decision-making. Organizations increasingly rely on data-driven insights to optimize operations, understand customer behavior, and maintain competitive advantages in their respective markets.',
                'is_active' => true,
            ],
            [
                'title' => 'Programming Fundamentals - Medium',
                'difficulty' => 'medium',
                'content' => 'Programming requires logical thinking, problem-solving skills, and attention to detail. Developers must understand algorithms, data structures, and design patterns to create efficient, maintainable code. Version control systems like Git enable collaboration among team members working on shared codebases.',
                'is_active' => true,
            ],
            [
                'title' => 'Advanced Technical Documentation - Hard',
                'difficulty' => 'hard',
                'content' => 'Comprehensive technical documentation serves as a critical resource for developers, system administrators, and end-users. Well-structured documentation includes API references, architecture diagrams, deployment procedures, troubleshooting guides, and best practices. Maintaining accurate, up-to-date documentation requires ongoing effort but significantly reduces onboarding time and operational overhead.',
                'is_active' => true,
            ],
            [
                'title' => 'Software Development Lifecycle - Hard',
                'difficulty' => 'hard',
                'content' => 'The software development lifecycle encompasses requirements gathering, system design, implementation, testing, deployment, and maintenance phases. Agile methodologies emphasize iterative development, continuous integration, and frequent stakeholder collaboration. DevOps practices bridge development and operations teams, automating infrastructure provisioning, configuration management, and continuous delivery pipelines to accelerate release cycles.',
                'is_active' => true,
            ],
            [
                'title' => 'Cybersecurity Best Practices - Hard',
                'difficulty' => 'hard',
                'content' => 'Implementing robust cybersecurity measures requires a multi-layered approach combining technical controls, security policies, and user awareness training. Organizations must conduct regular vulnerability assessments, implement intrusion detection systems, enforce strong authentication mechanisms, encrypt sensitive data both in transit and at rest, and maintain comprehensive incident response procedures to mitigate potential security breaches.',
                'is_active' => true,
            ],
        ];

        foreach ($samples as $sample) {
            TypingTextSample::create($sample);
        }
    }
}
