<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NumbersController extends AbstractController
{
    #[Route('/numbers', name: 'app_numbers')]
    public function index(): Response
    {
        // Statistical data points regarding Computer Science and Software Engineering jobs
        $csStats = [
            [
                'title' => 'Average Software Engineer Salary (US)',
                'value' => '$125,000',
                'description' => 'The national median annual salary for professional software developers.'
            ],
            [
                'title' => 'Projected Job Growth Rate',
                'value' => '25%',
                'description' => 'Expected employment growth for computer and information technology jobs from 2022 to 2032.'
            ],
            [
                'title' => 'Global Remote Work Share',
                'value' => '58%',
                'description' => 'Percentage of tech companies offering fully remote or hybrid working arrangements.'
            ],
            [
                'title' => 'Most Demanded Language: Python',
                'value' => '48%',
                'description' => 'Percentage of tech job listings requiring or preferring Python proficiency.'
            ],
            [
                'title' => 'Cybersecurity Job Openings',
                'value' => '3.5M',
                'description' => 'Global unfilled cybersecurity positions, representing a massive market demand.'
            ],
            [
                'title' => 'Cloud & DevOps Adoption',
                'value' => '89%',
                'description' => 'Proportion of enterprise companies utilizing cloud infrastructure (AWS/Azure/GCP).'
            ]
        ];

        return $this->render('numbers/index.html.twig', [
            'stats' => $csStats,
        ]);
    }
}