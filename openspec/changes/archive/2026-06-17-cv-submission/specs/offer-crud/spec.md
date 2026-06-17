## MODIFIED Requirements

### Requirement: View offer details
The system SHALL provide a Show page at /offers/{offer} displaying all offer fields with formatted labels. Required_skills and soft_skills SHALL be displayed as colored badges. The page SHALL include Edit, Archive, and Submit CV buttons. The page SHALL display a list of submitted applications with candidate name and submission date. The Submit CV button SHALL navigate to /offers/{offer}/submit.

#### Scenario: Display offer details
- **WHEN** user navigates to /offers/{offer}
- **THEN** system displays all offer fields with proper formatting

#### Scenario: Display skill badges
- **WHEN** offer has required_skills ["PHP", "Laravel", "MySQL"]
- **THEN** system displays three colored badges for each skill

#### Scenario: Edit button navigation
- **WHEN** user clicks the Edit button
- **THEN** system navigates to /offers/{offer}/edit

#### Scenario: Archive button triggers confirmation
- **WHEN** user clicks the Archive button
- **THEN** system displays a confirmation modal before archiving

#### Scenario: Submit CV button navigation
- **WHEN** user clicks the Submit CV button
- **THEN** system navigates to /offers/{offer}/submit

#### Scenario: Display submitted applications
- **WHEN** offer has submitted applications
- **THEN** system displays a list of applications with candidate name and submission date
