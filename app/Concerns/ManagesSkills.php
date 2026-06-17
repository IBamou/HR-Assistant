<?php

namespace App\Concerns;

trait ManagesSkills
{
    public function addRequiredSkill(): void
    {
        $skill = trim($this->new_skill ?? '');
        if ($skill !== '' && ! in_array($skill, $this->required_skills)) {
            $this->required_skills[] = $skill;
            $this->new_skill = null;
        }
    }

    public function removeRequiredSkill(int $index): void
    {
        if (isset($this->required_skills[$index])) {
            unset($this->required_skills[$index]);
            $this->required_skills = array_values($this->required_skills);
        }
    }

    public function addSoftSkill(): void
    {
        $skill = trim($this->new_soft_skill ?? '');
        if ($skill !== '' && ! in_array($skill, $this->soft_skills)) {
            $this->soft_skills[] = $skill;
            $this->new_soft_skill = null;
        }
    }

    public function removeSoftSkill(int $index): void
    {
        if (isset($this->soft_skills[$index])) {
            unset($this->soft_skills[$index]);
            $this->soft_skills = array_values($this->soft_skills);
        }
    }
}
