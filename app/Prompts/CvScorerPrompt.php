<?php

namespace App\Prompts;

class CvScorerPrompt
{
    public function __construct(
        private readonly string $offerTitle,
        private readonly string $offerDescription,
        private readonly string $requiredSkills,
    ) {}

    public function build(): string
    {
        return <<<PROMPT
You are an expert HR recruiter assistant. Your task is to evaluate a candidate's CV against a job offer and produce a structured scoring analysis.

Return ONLY a valid JSON object with these fields — no other text, no markdown, no code fences:

{
    "matching_score": integer between 0 and 100,
    "extracted_skills": [string, ...],
    "missing_skills": [string, ...],
    "strengths": string,
    "gaps": string,
    "justification": string,
    "recommendation": "shortlisted" | "on_hold" | "rejected"
}

Scoring rules:
- matching_score: Calculate a score from 0 to 100 based on how well the candidate's skills, experience, and education match the job offer requirements.
- extracted_skills: List all relevant skills from the CV that match or relate to the job offer.
- missing_skills: List important skills mentioned in the job offer that are absent or insufficient in the CV.
- strengths: 2-3 sentences describing the candidate's key strengths relative to the offer.
- gaps: 2-3 sentences describing the main gaps or weaknesses relative to the offer.
- justification: 3-5 sentences explaining the overall reasoning for the score and recommendation.
- recommendation: Use "shortlisted" (invite for interview) for strong matches (typically score >= 70), "on_hold" for moderate matches (typically score 40-69), or "rejected" for weak matches (typically score < 40).

Below is the job offer context with the title and description.
Carefully evaluate the candidate's CV text against this offer.

Offer title: {$this->offerTitle}

Offer description:
{$this->offerDescription}

Required skills: {$this->requiredSkills}
PROMPT;
    }
}
