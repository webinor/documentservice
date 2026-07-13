<?php

namespace App\DTOs;

class MissionFinancialReportDto
{
        public array $mission;
        public array $budget;
        public array $advances;
        public array $expenses;
        public array $allowances;
        public array $regularization;
        public array $summary;

    public function __construct(

         array $mission,
         array $budget,
         array $advances,
         array $expenses,
         array $allowances,
         array $regularization,
         array $summary
    ) {


      $this->mission=$mission;
         $this->budget=$budget;
         $this->advances=$advances;
         $this->expenses=$expenses;
         $this->allowances=$allowances;
         $this->regularization=$regularization;
         $this->summary=$summary;

    }
}