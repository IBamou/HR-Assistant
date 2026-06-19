## Context

AnalyseCVJob is currently a placeholder that creates an empty Analysis record with `status: pending`. The extraction pipeline (Docling → LlamaParse → LocalPdf) and CandidateInfoExtractor (Groq) are complete. No actual scoring against offer requirements exists. The analyses table already has all needed columns (matching_score, recommendation, extracted_skills, missing_skills, strengths, gaps, justification, status).

## Goals / Non-Goals

**Goals:**
- Replace AnalyseCVJob placeholder with real AI scoring via Groq
- Return structured scoring: matching_score, extracted_skills, missing_skills, strengths, gaps, recommendation, justification
- Display analysis results on the offer show page with badges and a detail modal
- Follow existing patterns (Agent+Promptable, AiClient, Job architecture)

**Non-Goals:**
- No new migrations or database schema changes
- No new routes or navigation — analysis viewed inline on offers.show
- No real-time or polling — analysis written once by the job
- No changes to the existing extraction pipeline or CandidateInfoExtractor

## Decisions

1. **Single attempt job (no release)** — All data is local (CV text in JSON column, offer in DB). No polling or retry needed. Single attempt, success or fail.

2. **Recommandation enum with French labels** — Following ProcessStatus enum pattern. The three cases reflect HR workflow: Convoquer, Attente, Rejeter. Label() returns French labels for badge display.

3. **Agent + Promptable pattern** — Same as CandidateInfoExtractor. CvScorer implements Agent, uses Promptable trait, instructions() returns (new CvScorerPrompt)->build().

4. **Prompt with combined CV + offer context** — The prompt receives both CV extracted_text and offer requirements/description. The AI scores the match between them, not just the CV in isolation.

5. **Modal rather than separate page** — Consistent with existing patterns (archive modal in same view). Analysis detail shown via Flux modal triggered by clicking the application row.

6. **Score badges with color thresholds** — Emerald (≥70, Convoquer), Amber (40-69, Attente), Rose (<40, Rejeter). No gauge/bar — keep it simple with a large badge.

## Risks / Trade-offs

- **[Risk] Large prompt may exceed token limits** — CV text + offer description combined could be long. → **Mitigation**: Use the same truncation safeguard (30000 chars) already in ExtractCandidateInfoJob.
- **[Risk] AI may produce invalid JSON** — The prompt instructs valid JSON only. → **Mitigation**: Parse response with try/catch, set status to Failed on parse error.
- **[Risk] No retry on failure** — Single attempt means transient AI errors fail the analysis permanently. → **Mitigation**: Acceptable for v1; user can re-trigger analysis manually if needed.
