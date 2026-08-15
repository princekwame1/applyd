<?php

namespace Database\Seeders;

use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use Illuminate\Database\Seeder;

/**
 * The digital-skills intake form, ready to share.
 *
 * Two of its questions are conditional, which is the point of seeding it: the
 * job-title question only comes up for people who said they're employed, and
 * the leadership follow-up only for people who said yes. Everything here is
 * ordinary data — edit or delete any of it from the dashboard afterwards.
 *
 * Safe to run more than once: the form is matched on its slug and each question
 * on its key, so re-running updates wording rather than duplicating rows. It
 * deliberately does **not** touch answers already collected.
 *
 *   php artisan db:seed --class=DigitalSkillsQuestionnaireSeeder
 */
class DigitalSkillsQuestionnaireSeeder extends Seeder
{
    public const SLUG = 'digital-skills-training';

    public function run(): void
    {
        $questionnaire = Questionnaire::updateOrCreate(
            ['slug' => self::SLUG],
            [
                'title' => 'Digital Skills Training — Sign-up',
                'description' => "A few questions about your background and what you'd like to get out of the training, "
                    .'so we can pitch the sessions at the right level. It takes about three minutes.',
                'success_message' => "Thanks — that's everything we need. We'll be in touch with the session details before we start.",
                'submit_label' => 'Send my answers',
                // Left as a draft on purpose: read it through, adjust the
                // wording, then publish when you're ready to share the link.
                'is_published' => false,
                'sort_order' => (Questionnaire::max('sort_order') ?? 0) + 1,
            ],
        );

        foreach ($this->questions() as $order => $question) {
            QuestionnaireQuestion::updateOrCreate(
                [
                    'questionnaire_id' => $questionnaire->id,
                    'key' => $question['key'],
                ],
                array_merge([
                    'options' => null,
                    'settings' => null,
                    'visible_when' => null,
                    'help_text' => null,
                    'placeholder' => null,
                    'is_required' => true,
                    'is_active' => true,
                    'sort_order' => $order + 1,
                ], $question),
            );
        }
    }

    /**
     * In the order they're asked. A conditional question always follows the one
     * it depends on — a rule may only point backwards.
     */
    private function questions(): array
    {
        return [
            [
                'key' => 'education_level',
                'type' => 'radio',
                'label' => 'What is your highest level of education?',
                'options' => ['High School', 'Diploma', "Bachelor's", "Master's", 'PhD', 'Other'],
            ],
            [
                'key' => 'field_of_study',
                'type' => 'short_text',
                'label' => 'What did you study, or what is your area of specialisation?',
                'placeholder' => 'e.g. Accounting, Nursing, Civil Engineering',
            ],
            [
                'key' => 'occupation',
                'type' => 'short_text',
                'label' => 'What is your current occupation or profession?',
                'placeholder' => 'e.g. Teacher, Trader, Software developer',
            ],
            [
                'key' => 'employment_status',
                'type' => 'radio',
                'label' => 'Are you currently employed, self-employed, a student, or looking for work?',
                'options' => ['Employed', 'Self-employed', 'Student', 'Looking for work'],
            ],
            [
                'key' => 'job_title',
                'type' => 'short_text',
                'label' => 'What is your current job title or position?',
                'placeholder' => 'e.g. Operations Manager',
                // Only asked of people who said they're employed — the whole
                // reason the original wording started with "If employed,".
                'visible_when' => [
                    'key' => 'employment_status',
                    'operator' => 'in',
                    'values' => ['Employed'],
                ],
            ],
            [
                'key' => 'leadership_role',
                'type' => 'radio',
                'label' => 'Are you currently in a managerial or leadership role?',
                'options' => ['Yes', 'No'],
            ],
            [
                'key' => 'leadership_detail',
                'type' => 'long_text',
                'label' => 'What is your role, and how many people do you manage?',
                'placeholder' => 'e.g. I lead a team of six in the finance office.',
                'visible_when' => [
                    'key' => 'leadership_role',
                    'operator' => 'in',
                    'values' => ['Yes'],
                ],
            ],
            [
                'key' => 'tools_used',
                'type' => 'checkbox',
                'label' => 'Which of these tools do you currently use?',
                'help_text' => 'Tick as many as apply.',
                'options' => ['Google Forms', 'Google Sheets', 'Google Docs', 'Google Drive', 'Google Keep', 'None of these'],
            ],
            [
                'key' => 'digital_skill_level',
                'type' => 'radio',
                'label' => 'How would you rate your current digital skills?',
                'options' => ['Beginner', 'Intermediate', 'Advanced'],
            ],
            [
                'key' => 'intended_use',
                'type' => 'radio',
                'label' => 'What do you mainly want to use these tools for?',
                'options' => ['Work', 'Business', 'Studies', 'Research', 'Personal use', 'Other'],
            ],
            [
                'key' => 'desired_skills',
                'type' => 'long_text',
                'label' => 'What would you like to be able to do by the end of the training?',
                'help_text' => 'Be as specific as you like — it shapes what we cover.',
                'placeholder' => 'e.g. Build a form that collects survey data straight into a spreadsheet.',
            ],
        ];
    }
}
